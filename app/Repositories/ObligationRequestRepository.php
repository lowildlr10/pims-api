<?php

namespace App\Repositories;

use App\Enums\ObligationRequestStatus;
use App\Helpers\FileHelper;
use App\Helpers\StatusTimestampsHelper;
use App\Interfaces\ObligationRequestInterface;
use App\Models\Company;
use App\Models\ObligationRequest;
use App\Models\ObligationRequestAccount;
use App\Models\ObligationRequestFpp;
use Illuminate\Support\Collection;
use TCPDF;
use TCPDF_FONTS;

class ObligationRequestRepository implements ObligationRequestInterface
{
    protected string $appUrl;

    protected string $fontArial;

    protected string $fontArialBold;

    protected string $fontArialItalic;

    protected string $fontArialBoldItalic;

    protected string $fontArialNarrow;

    protected string $fontArialNarrowBold;

    public function __construct()
    {
        $this->appUrl = env('APP_URL') ?? 'http://localhost';
        $this->fontArial = TCPDF_FONTS::addTTFfont('fonts/arial.ttf', 'TrueTypeUnicode', '', 96);
        $this->fontArialBold = TCPDF_FONTS::addTTFfont('fonts/arialbd.ttf', 'TrueTypeUnicode', '', 96);
        $this->fontArialItalic = TCPDF_FONTS::addTTFfont('fonts/ariali.ttf', 'TrueTypeUnicode', '', 96);
        $this->fontArialBoldItalic = TCPDF_FONTS::addTTFfont('fonts/arialbi.ttf', 'TrueTypeUnicode', '', 96);
        $this->fontArialNarrow = TCPDF_FONTS::addTTFfont('fonts/arialn.ttf', 'TrueTypeUnicode', '', 96);
        $this->fontArialNarrowBold = TCPDF_FONTS::addTTFfont('fonts/arialnb.ttf', 'TrueTypeUnicode', '', 96);
    }

    public function storeUpdate(array $data, ?ObligationRequest $obligationRequest = null): ObligationRequest
    {
        if (! empty($obligationRequest)) {
            $obligationRequest->update($data);
        } else {
            $obligationRequest = ObligationRequest::create(
                array_merge(
                    $data,
                    [
                        'obr_no' => $this->generateNewObrNumber(),
                        'status' => ObligationRequestStatus::DRAFT,
                        'status_timestamps' => StatusTimestampsHelper::generate(
                            'draft_at', null
                        ),
                    ]
                )
            );
        }

        $this->storeUpdateFpps(
            collect(isset($data['fpps']) && ! empty($data['fpps']) ? $data['fpps'] : []),
            $obligationRequest
        );

        $this->storeUpdateAccounts(
            collect(isset($data['accounts']) && ! empty($data['accounts']) ? $data['accounts'] : []),
            $obligationRequest
        );

        return $obligationRequest;
    }

    private function storeUpdateFpps(Collection $fpps, ObligationRequest $obligationRequest): void
    {
        ObligationRequestFpp::where('obligation_request_id', $obligationRequest->id)->delete();
        
        foreach ($fpps as $fppId) {
            ObligationRequestFpp::create([
                'obligation_request_id' => $obligationRequest->id,
                'fpp_id' => $fppId
            ]);
        }
    }

    private function storeUpdateAccounts(Collection $accounts, ObligationRequest $obligationRequest): void
    {
        ObligationRequestAccount::where('obligation_request_id', $obligationRequest->id)->delete();

        foreach ($accounts as $key => $account) {
            ObligationRequestAccount::create([
                'item_sequence' => $key,
                'obligation_request_id' => $obligationRequest->id,
                'account_id' => $account['account_id'],
                'amount' => $account['amount'],
            ]);
        }
    }

    private function generateNewObrNumber(): string
    {
        $month = date('m');
        $year = date('Y');
        $sequence = ObligationRequest::whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->count() + 1;

        return "{$year}-{$sequence}-{$month}";
    }

    public function print(array $pageConfig, string $obrId): array
    {
        try {
            $company = Company::first();
            $obr = ObligationRequest::with([
                'payee:id,supplier_name',
                'responsibility_center:id,code',
                'purchase_order:id,po_no,total_amount',
                'signatory_budget:id,user_id',
                'signatory_budget.user:id,firstname,middlename,lastname,allow_signature,signature',
                'signatory_budget.detail' => function ($query) {
                    $query->where('document', 'obr')
                        ->where('signatory_type', 'budget');
                },
                'signatory_head:id,user_id',
                'signatory_head.user:id,firstname,middlename,lastname,allow_signature,signature',
                'signatory_head.detail' => function ($query) {
                    $query->where('document', 'obr')
                        ->where('signatory_type', 'head');
                },
                'fpps',
                'fpps.fpp',
                'accounts',
                'accounts.account'
            ])->find($obrId);

            $filename = "OBR-{$obr->obr_no}.pdf";
            $blob = $this->generateObligationRequestDoc($filename, $pageConfig, $obr, $company);

            return [
                'success' => true,
                'blob' => $blob,
                'filename' => $filename,
            ];
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'message' => $th->getMessage(),
                'blob' => '',
                'filename' => '',
            ];
        }
    }

    private function generateObligationRequestDoc(
        string $filename, array $pageConfig, ObligationRequest $data, Company $company
    ): string {
        $province = strtoupper($company?->province) ?? '';
        $municipality = strtoupper($company?->municipality) ?? '';
        $funding = $data?->funding ?? [];
        $isFundingGeneral = $funding['general'] ?? false;
        $isFundingMdf20 = $funding['mdf_20'] ?? false;
        $isFundingGfMdrRmf5 = $funding['gf_mdrrmf_5'] ?? false;
        $isFundingSef = $funding['sef'] ?? false;
        $obrNo = $data->obr_no;
        $payee = $data->payee->supplier_name;
        $office = $data->office;
        $address = $data->address;
        $responsibilityCenter = $data->responsibility_center->code;
        $particulars = trim(str_replace("\r", '<br />', $data->particulars));
        $particulars = str_replace("\n", '<br />', $particulars);
        $fpps = implode(
            '<br />', 
            $data->fpps->map(fn($fpp) => $fpp->fpp->code)
                ->toArray() 
                ?? []
        );
        $accounts = implode(
            '<br />', 
            $data->accounts->map(fn($account) => $account->account->code)
                ->toArray() 
                ?? []
        );
        $amount = number_format($data->total_amount, 2);
        $complianceStatus = $data->compliance_status;
        $headName = $data->signatory_head?->user?->fullname ?? '';
        $headPosition = $data->signatory_head?->detail?->position ?? '';
        $headSignedDate = date_format(date_create($data->head_signed_date), 'F j, Y');
        $budgetName = $data->signatory_budget?->user?->fullname ?? '';
        $budgetPosition = $data->signatory_budget?->detail?->position ?? '';
        $budgetSignedDate = date_format(date_create($data->budget_signed_date), 'F j, Y');

        $pdf = new TCPDF($pageConfig['orientation'], $pageConfig['unit'], $pageConfig['dimension']);

        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor(env('APP_NAME'));
        $pdf->SetTitle($filename);
        $pdf->SetSubject('Obligation Request');
        $pdf->SetMargins(
            $pdf->getPageWidth() * 0.04,
            $pdf->getPageHeight() * 0.05,
            $pdf->getPageWidth() * 0.04
        );
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        // $pdf->SetAutoPageBreak(TRUE, 0.4);

        $pdf->AddPage();

        $pageWidth = $pdf->getPageWidth() * 0.92;

        $x = $pdf->GetX();
        $y = $pdf->GetY();

        try {
            if ($company->company_logo) {
                $imagePath = FileHelper::getPublicPath(
                    $company->company_logo
                );
                $pdf->Image(
                    $imagePath,
                    $x + ($x * 0.3),
                    $y + ($y * 0.2),
                    w: $pageConfig['orientation'] === 'P'
                        ? $x + ($x * 1.5)
                        : $y + ($y * 1.5),
                    type: 'PNG',
                    resize: true,
                    dpi: 500,
                );
            }
        } catch (\Throwable $th) {}

        try {
            if ($company->company_logo) {
                $imagePath = 'images/bagong-ph-logo.png';
                $pdf->Image(
                    $imagePath,
                    $x + ($x * 3.2),
                    $y + ($y * 0.2),
                    w: $pageConfig['orientation'] === 'P'
                        ? $x + ($x * 1.5)
                        : $y + ($y * 1.5),
                    type: 'PNG',
                    resize: true,
                    dpi: 500,
                );
            }
        } catch (\Throwable $th) {}

        $pdf->setXY($x + ($x * 15.4), $y + ($y * 0.1));

        $pdf->setCellHeightRatio(2.3);
        $pdf->SetFont($this->fontArialBold, 'B', 10);
        $pdf->Cell($pageWidth * 0.01, 0, '', 'LT', 0);
        $pdf->Cell($pageWidth * 0.31, 0, 'FUNDING:', 'TR', 1);
        $pdf->setX($x + ($x * 15.4));
        $pdf->setCellHeightRatio(1.6);
        $pdf->Cell($pageWidth * 0.015, 0, '', 'L', 0);
        $pdf->SetFont('zapfdingbats', 'B', 10);
        $pdf->Cell($pageWidth * 0.03, 0, $isFundingGeneral ? '3' : '', 1, 0, 'C');
        $pdf->SetFont($this->fontArialBold, 'B', 10);
        $pdf->Cell($pageWidth * 0.1, 0, 'GENERAL', 0, 0);
        $pdf->SetFont('zapfdingbats', 'B', 10);
        $pdf->Cell($pageWidth * 0.03, 0, $isFundingGfMdrRmf5 ? '3' : '', 1, 0, 'C');
        $pdf->SetFont($this->fontArialBold, 'B', 9);
        $pdf->setCellHeightRatio(1.8);
        $pdf->Cell($pageWidth * 0.145, 0, 'GF - 5% MDRRMF', 'R', 1);
        $pdf->setX($x + ($x * 15.4));
        $pdf->Cell($pageWidth * 0.32, 0, '', 'LR', 1);
        $pdf->setX($x + ($x * 15.4));
        $pdf->setCellHeightRatio(1.6);
        $pdf->SetFont($this->fontArialBold, 'B', 10);
        $pdf->Cell($pageWidth * 0.015, 0, '', 'L', 0);
        $pdf->SetFont('zapfdingbats', 'B', 10);
        $pdf->Cell($pageWidth * 0.03, 0, $isFundingMdf20 ? '3' : '', 1, 0, 'C');
        $pdf->SetFont($this->fontArialBold, 'B', 10);
        $pdf->Cell($pageWidth * 0.1, 0, '20% MDF', 0, 0);
        $pdf->SetFont('zapfdingbats', 'B', 10);
        $pdf->Cell($pageWidth * 0.03, 0, $isFundingSef ? '3' : '', 1, 0, 'C');
        $pdf->SetFont($this->fontArialBold, 'B', 10);
        $pdf->Cell($pageWidth * 0.145, 0, 'SEF', 'R', 1);
        $pdf->setX($x + ($x * 15.4));
        $pdf->Cell($pageWidth * 0.32, 0, '', 'LRB', 1);

        $pdf->setXY($x, $y);

        $pdf->setCellHeightRatio(1.25);
        $pdf->SetFont('Times', '', 12);
        $pdf->Cell(0, 0, 'Republic of the Philippines', 'LTR', 1, 'C');
        $pdf->SetFont('Times', '', 10);
        $pdf->Cell(0, 0, '', 'LR', 1, 'C');
        $pdf->SetFont('Times', 'B', 14);
        $pdf->Cell(0, 0, "PROVINCE OF $province", 'LR', 1, 'C');
        $pdf->SetFont('Times', '', 10);
        $pdf->Cell(0, 0, '', 'LR', 1, 'C');
        $pdf->SetFont('Times', 'B', 18);
        $pdf->setFontSpacing(2);
        $pdf->Cell(0, 0, $municipality, 'LR', 1, 'C');
        $pdf->setFontSpacing(0);
        $pdf->SetFont('Times', '', 8);
        $pdf->Cell(0, 0, '', 'LR', 1, 'C');
        $pdf->Cell(0, 0, '', 'LRB', 1, 'C');
        $pdf->setCellHeightRatio(0.2);
        $pdf->Cell(0, 0, '', 'LRB', 1, 'C');
        
        $pdf->setCellHeightRatio(1.6);
        $pdf->SetFont('Times', '', 10);
        $pdf->SetFont('Times', 'B', 20);
        $pdf->Cell($pageWidth * 0.55, 0, 'OBLIGATION REQUEST', 'L', 0, 'C');
        $pdf->SetFont($this->fontArialBold, 'B', 20);
        $pdf->Cell(0, 0, "No. $obrNo", 'R', 1, 'R');

        $pdf->setCellHeightRatio(2.5);
        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->Cell($pageWidth * 0.13, 0, 'Payee/Office:', 'LT', 0);
        $pdf->setCellHeightRatio(1.6);
        $pdf->SetFont($this->fontArialBold, 'B', 16);
        $pdf->Cell(0, 0, $payee, 'LTR', 1);
        $pdf->setCellHeightRatio(1.25);
        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->Cell($pageWidth * 0.13, 0, 'Office:', 'LT', 0);
        $pdf->Cell(0, 0, $office, 'LTR', 1);
        $pdf->Cell($pageWidth * 0.13, 0, 'Address:', 'LT', 0);
        $pdf->Cell(0, 0, $address, 'LTR', 1);
        $pdf->setCellHeightRatio(0.2);
        $pdf->Cell(0, 0, '', 1, 1, 'C');

        $htmlTable = '
            <table border="1" cellpadding="2"><thead><tr>
                <th
                    width="13%"
                    align="center"
                >Responsibility Center</th>
                <th
                    width="40.72%"
                    align="center"
                >Particulars</th>
                <th
                    width="12.975%"
                    align="center"
                >F.P.P</th>
                <th
                    width="14.785%"
                    align="center"
                >Account Code</th>
                <th
                    width="18.52%"
                    align="center"
                >Amount</th>
            </tr></thead></table>
        ';

        $pdf->setCellHeightRatio(1.25);
        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->writeHTML($htmlTable, ln: false);
        $pdf->Ln(0);

        $htmlTable = '
            <table 
                style="border-left: 1px solid black; border-right: 1px solid black;"
                cellpadding="2"
            ><tbody><tr>
                <td
                    style="border-right: 1px solid black"
                    width="13%"
                    align="center"
                >'. $responsibilityCenter .'</td>
                <td
                    style="border-right: 1px solid black"
                    width="40.72%"
                >'. $particulars .'</td>
                <td
                    style="border-right: 1px solid black"
                    width="12.975%"
                    align="center"
                >'. $fpps .'</td>
                <td
                    style="border-right: 1px solid black"
                    width="14.785%"
                    align="center"
                >'. $accounts .'</td>
                <td
                    width="18.52%"
                    align="center"
                >'. $amount .'</td>
            </tr>
            <tr>
                <td
                    style="border-right: 1px solid black"
                    width="13%"
                ></td>
                <td
                    style="border-right: 1px solid black"
                    width="40.72%"
                ></td>
                <td
                    style="border-right: 1px solid black"
                    width="12.975%"
                ></td>
                <td
                    style="border-right: 1px solid black"
                    width="14.785%"
                ></td>
                <td
                    width="18.52%"
                ></td>
            </tr></tbody></table>
        ';

        $pdf->SetFont($this->fontArial, '', 12);
        $pdf->writeHTML($htmlTable, ln: false);
        $pdf->Ln(0);

        $htmlTable = '
            <table 
                style="border-left: 1px solid black; border-right: 1px solid black;"
                cellpadding="2"
            ><tbody><tr>
                <td
                    style="border-right: 1px solid black"
                    width="13%"
                    align="center"
                ></td>
                <td
                    style="border-right: 1px solid black"
                    width="40.72%"
                    align="center"
                >TOTAL</td>
                <td
                    style="border-right: 1px solid black"
                    width="12.975%"
                    align="center"
                ></td>
                <td
                    style="border-right: 1px solid black"
                    width="14.785%"
                    align="center"
                ></td>
                <td
                    style="border-top: 2px solid black; border-bottom: 2px solid black;"
                    width="18.52%"
                    align="center"
                >P'. $amount .'</td>
            </tr></tbody></table>
        ';

        $pdf->setCellHeightRatio(1.6);
        $pdf->SetFont($this->fontArialBold, 'B', 12);
        $pdf->writeHTML($htmlTable, ln: false);
        $pdf->Ln(0);

        $htmlTable = '
            <table 
                style="border-left: 1px solid black; border-right: 1px solid black; border-bottom: 1px solid black;"
                cellpadding="2"
            ><tbody><tr>
                <td
                    style="border-right: 1px solid black"
                    width="13%"
                ></td>
                <td
                    style="border-right: 1px solid black"
                    width="40.72%"
                ></td>
                <td
                    style="border-right: 1px solid black"
                    width="12.975%"
                ></td>
                <td
                    style="border-right: 1px solid black"
                    width="14.785%"
                ></td>
                <td
                    width="18.52%"
                ></td>
            </tr></tbody></table>
        ';

        $pdf->setCellHeightRatio(1.25);
        $pdf->SetFont($this->fontArialBold, 'B', 12);
        $pdf->writeHTML($htmlTable, ln: false);
        $pdf->Ln(0);

        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->Cell($pageWidth * 0.025, 0, 'A', 'LRB', 0, 'C');
        $pdf->Cell($pageWidth * 0.5122, 0, " Certified:", 'L', 0);
        $pdf->Cell($pageWidth * 0.025, 0, 'B', 'LRB', 0, 'C');
        $pdf->Cell(0, 0, ' Certified:', 'LR', 1);

        $pdf->Cell($pageWidth * 0.105, 0, '', 'L', 0, 'C');
        $pdf->setCellHeightRatio(1.4);
        $pdf->SetFont('zapfdingbats', 'B', 9);
        $pdf->Cell($pageWidth * 0.025, 0, $complianceStatus['allotment_necessary'] ? '3' : '', 1, 0, 'C');
        $pdf->setCellHeightRatio(1.25);
        $pdf->SetFont($this->fontArial, '', 9);
        $pdf->Cell($pageWidth * 0.4072, 0, ' Charges to appropriation / allotment necessary,', 0, 0);
        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->Cell($pageWidth * 0.085, 0, '', 'L', 0, 'C');
        $pdf->Cell(0, 0, 'Existence of available appropriation', 'R', 1);

        $pdf->Cell($pageWidth * 0.105, 0, '', 'L', 0, 'C');
        $pdf->Cell($pageWidth * 0.025, 0, '', 0, 0);
        $pdf->SetFont($this->fontArial, '', 9);
        $pdf->Cell($pageWidth * 0.4072, 0, ' lawful and under my direct supervision', 0, 0);
        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->Cell(0, 0, '', 'LR', 1);

        $pdf->setCellHeightRatio(1.4);
        $pdf->Cell($pageWidth * 0.105, 0, '', 'L', 0, 'C');
        $pdf->SetFont('zapfdingbats', 'B', 9);
        $pdf->Cell($pageWidth * 0.025, 0, $complianceStatus['document_valid'] ? '3' : '', 1, 0, 'C');
        $pdf->setCellHeightRatio(1.25);
        $pdf->SetFont($this->fontArial, '', 9);
        $pdf->Cell($pageWidth * 0.4072, 0, ' Supporting documents valid, proper and legal', 0, 0);
        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->setCellHeightRatio(1.4);
        $pdf->Cell(0, 0, '', 'LR', 1);

        $pdf->setCellHeightRatio(0.2);
        $pdf->Cell($pageWidth * 0.5372, 0, '', 'LRB', 0);
        $pdf->Cell(0, 0, '', 'LRB', 1);
        $pdf->setCellHeightRatio(1.25);

        $pdf->Cell($pageWidth * 0.13, 0, 'Signature:', 'LB', 0);
        $pdf->Cell($pageWidth * 0.4072, 0, '', 'LB', 0);
        $pdf->Cell($pageWidth * 0.12975, 0, 'Signature:', 'LB', 0);
        $pdf->Cell(0, 0, '', 'LRB', 1);

         // Measure max height needed for signatory name
        $pdf->SetFont($this->fontArialBold, 'B', 14);
        $deptHeight = max(
            $pdf->getStringHeight($pageWidth * 0.4072, $headName),
            $pdf->getStringHeight($pageWidth * 0.33305, $budgetName),
        );

        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->setCellHeightRatio(1.75);
        $pdf->MultiCell($pageWidth * 0.13, $deptHeight, 'Printed Name:', 'LB', 'L', 0, 0);
        $pdf->setCellHeightRatio(1.25);
        $pdf->SetFont($this->fontArialBold, 'B', 14);
        $pdf->MultiCell($pageWidth * 0.4072, $deptHeight, $headName, 'LB', 'C', 0, 0);
        $pdf->setCellHeightRatio(1.75);
        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->MultiCell($pageWidth * 0.13, $deptHeight, 'Printed Name:', 'LB', 'L', 0, 0);
        $pdf->setCellHeightRatio(1.25);
        $pdf->SetFont($this->fontArialBold, 'B', 14);
        $pdf->MultiCell(0, $deptHeight, $budgetName, 'LRB', 'C', 0, 1);

        $pdf->setCellHeightRatio(1.25);
        $pdf->SetFont($this->fontArial, '', 10);

        // ---------------- Position with multi rowspan ----------------
        // Define column widths
        $w1 = 0.13 * $pageWidth;
        $w2 = 0.4072 * $pageWidth;
        $w3 = 0.13 * $pageWidth;
        $w4 = 0.3328 * $pageWidth;

        $headTitle = 'Head, Requesting Office/Authorized Representative';
        $budgetTitle = 'Head, Budget Unit/Authorized Representative';

        $deptSignatoryPositionHeight = max(
            $pdf->getStringHeight($w2, $headPosition),
            $pdf->getStringHeight($w4, $budgetPosition),
        );
        $deptSignatoryTitleHeight = max(
            $pdf->getStringHeight($w2, $headTitle),
            $pdf->getStringHeight($w4, $budgetTitle),
        );
        $positionCellDepthHeight = $deptSignatoryPositionHeight + $deptSignatoryTitleHeight;

        // -------- First row --------
        $y = $pdf->GetY();
        $x = $pdf->GetX();

        // rowspan=2
        $pdf->MultiCell(
            $w1,
            $positionCellDepthHeight,
            "Position:",
            1,
            'L',
            0,
            0,
            x: $x,
            y: $y,
            maxh: $positionCellDepthHeight,
            valign: 'M'
        );
        $pdf->MultiCell(
            $w2,
            $deptSignatoryPositionHeight,
            $headPosition,
            'T',
            'C',
            0,
            0,
        );
        // rowspan=2
        $pdf->MultiCell(
            $w3,
            $positionCellDepthHeight,
            "Position:",
            1,
            'L',
            0,
            0,
            maxh: $positionCellDepthHeight,
            valign: 'M'
        );
        $pdf->MultiCell(
            $w4,
            $deptSignatoryPositionHeight,
            $budgetPosition,
            'TR',
            'C',
            0,
            1,
        );

        // -------- Second row --------
        $pdf->SetFont($this->fontArial, 'I', 9);

        // Cell under headPosition
        $pdf->MultiCell(
            $w2,
            $deptSignatoryTitleHeight,
            "Head, Requesting Office/Authorized Representative",
            'T',
            'C',
            0,
            0,
            x: $x + $w1,
            y: $y + $deptSignatoryPositionHeight
        );

        // Cell under budgetPosition
        $pdf->MultiCell(
            $w4,
            $deptSignatoryTitleHeight,
            "Head, Budget Unit/Authorized Representative",
            'TR',
            'C',
            0,
            1,
            x: $x + $w1 + $w2 + $w3,
            y: $y + $deptSignatoryPositionHeight
        );

        $pdf->setCellHeightRatio(1.6);
        $pdf->Cell($pageWidth * 0.13, 0, 'Date:', 'LTB', 0);
        $pdf->Cell($pageWidth * 0.4072, 0, $headSignedDate, 'LTB', 0, 'C');
        $pdf->Cell($pageWidth * 0.12975, 0, 'Date:', 'LTB', 0);
        $pdf->Cell(0, 0, $budgetSignedDate, 'LTRB', 1, 'C');

        $pdfBlob = $pdf->Output($filename, 'S');
        $pdfBase64 = base64_encode($pdfBlob);

        return $pdfBase64;
    }
}