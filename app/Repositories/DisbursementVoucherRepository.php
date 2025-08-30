<?php

namespace App\Repositories;

use App\Enums\DisbursementVoucherStatus;
use App\Helpers\FileHelper;
use App\Helpers\StatusTimestampsHelper;
use App\Interfaces\DisbursementVoucherInterface;
use App\Models\Company;
use App\Models\DisbursementVoucher;
use App\Models\InspectionAcceptanceReport;
use App\Models\PurchaseRequestItem;
use TCPDF;
use TCPDF_FONTS;

class DisbursementVoucherRepository implements DisbursementVoucherInterface
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

    public function storeUpdate(array $data, ?DisbursementVoucher $disbursementVoucher = null): DisbursementVoucher
    {
        if (! empty($disbursementVoucher)) {
            $disbursementVoucher->update($data);
        } else {
            $disbursementVoucher = DisbursementVoucher::create(
                array_merge(
                    $data,
                    [
                        'dv_no' => $this->generateNewDvNumber(),
                        'status' => DisbursementVoucherStatus::DRAFT,
                        'status_timestamps' => StatusTimestampsHelper::generate(
                            'draft_at', null
                        ),
                    ]
                )
            );
        }

        return $disbursementVoucher;
    }

    private function generateNewDvNumber(): string
    {
        $month = date('m');
        $year = date('Y');
        $sequence = DisbursementVoucher::whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->count() + 1;

        return "{$year}-{$sequence}-{$month}";
    }

    public function print(array $pageConfig, string $dvId): array
    {
        try {
            $company = Company::first();
            $dv = DisbursementVoucher::with([
                'payee:id,supplier_name,tin_no',
                'responsibility_center:id,code',
                'purchase_order:id,po_no,total_amount',
                'obligation_request:id,obr_no',
                'signatory_accountant:id,user_id',
                'signatory_accountant.user:id,firstname,middlename,lastname,allow_signature,signature',
                'signatory_accountant.detail' => function ($query) {
                    $query->where('document', 'dv')
                        ->where('signatory_type', 'accountant');
                },
                'signatory_treasurer:id,user_id',
                'signatory_treasurer.user:id,firstname,middlename,lastname,allow_signature,signature',
                'signatory_treasurer.detail' => function ($query) {
                    $query->where('document', 'dv')
                        ->where('signatory_type', 'treasurer');
                },
                'signatory_head:id,user_id',
                'signatory_head.user:id,firstname,middlename,lastname,allow_signature,signature',
                'signatory_head.detail' => function ($query) {
                    $query->where('document', 'dv')
                        ->where('signatory_type', 'head');
                }
            ])->find($dvId);

            $filename = "DV-{$dv->dv_no}.pdf";
            $blob = $this->generateDisbursementVoucherDoc($filename, $pageConfig, $dv, $company);

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

    private function generateDisbursementVoucherDoc(
        string $filename, array $pageConfig, DisbursementVoucher $data, Company $company
    ): string {
        $province = $company?->province ?? '';
        $municipality = $company?->municipality ?? '';
        $dvNo = $data->dv_no;
        $modePayment = $data->mode_payment;
        $payee = $data->payee->supplier_name;
        $tinNo = $data->payee->tin_no;
        $obrNo = $data->obligation_request->obr_no;
        $office = $data->office;
        $address = $data->address;
        $responsibilityCenter = $data->responsibility_center->code;
        $explanation = trim(str_replace("\r", '<br />', $data->explanation));
        $explanation = str_replace("\n", '<br />', $explanation);
        $amount = number_format($data->total_amount, 2);
        $accountantCertifiedChoices = $data->accountant_certified_choices;
        $accountantName = $data->signatory_accountant?->user?->fullname ?? '';
        $accountantPosition = $data->signatory_accountant?->detail?->position ?? '';
        $accountantSignedDate = $data->accountant_signed_date 
            ? date_format(date_create($data->accountant_signed_date), 'M j, Y') 
            : '';
        $treasurerName = $data->signatory_treasurer?->user?->fullname ?? '';
        $treasurerPosition = $data->signatory_treasurer?->detail?->position ?? '';
        $treasurerSignedDate = $data->treasurer_signed_date 
            ? date_format(date_create($data->treasurer_signed_date), 'M j, Y') 
            : '';
        $headName = $data->signatory_head?->user?->fullname ?? '';
        $headPosition = $data->signatory_head?->detail?->position ?? '';
        $headSignedDate = $data->head_signed_date 
            ? date_format(date_create($data->head_signed_date), 'M j, Y') 
            : '';
        $checkNo = $data->check_no ?? '';
        $bankName = $data->bank_name ?? '';
        $checkDate = $data->check_date 
            ? date_format(date_create($data->check_date), 'M j, Y') 
            : '';
        $receivedName = $data->received_name ?? '';
        $receivedDate = $data->received_date
            ? date_format(date_create($data->received_date), 'M j, Y') 
            : '';
        $orOtherDocument = $data->or_other_document ?? '';
        $jevNo = $data->jev_no ?? '';
        $jevDate = $data->jev_date
            ? date_format(date_create($data->jev_date), 'M j, Y') 
            : '';

        $pdf = new TCPDF($pageConfig['orientation'], $pageConfig['unit'], $pageConfig['dimension']);

        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor(env('APP_NAME'));
        $pdf->SetTitle($filename);
        $pdf->SetSubject('Obligation Request');
        $pdf->SetMargins(
            $pdf->getPageWidth() * 0.05,
            $pdf->getPageHeight() * 0.05,
            $pdf->getPageWidth() * 0.05
        );
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        // $pdf->SetAutoPageBreak(TRUE, 0.4);

        $pdf->AddPage();

        $pageWidth = $pdf->getPageWidth() * 0.9;

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
                        ? $x + ($x * 0.8)
                        : $y + ($y * 0.8),
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
                    $x + ($x * 2.5),
                    $y + ($y * 0.2),
                    w: $pageConfig['orientation'] === 'P'
                        ? $x + ($x * 0.8)
                        : $y + ($y * 0.8),
                    type: 'PNG',
                    resize: true,
                    dpi: 500,
                );
            }
        } catch (\Throwable $th) {}

        $pdf->setCellHeightRatio(1.6);
        $pdf->SetFont('Times', '', 9);
        $pdf->Cell($pageWidth * 0.86, 0, '', 'LT', 0, 'C');
        $pdf->Cell(0, 0, 'Annex B', 'TR', 1, 'L');
        $pdf->SetFont('Times', '', 11);
        $pdf->Cell(0, 0, 'Republic of the Philippines', 'LR', 1, 'C');
        $pdf->SetFont('Times', 'B', 11);
        $pdf->Cell(0, 0, "Province of $province", 'LR', 1, 'C');
        $pdf->SetFont('Times', 'B', 14);
        $pdf->Cell(0, 0, $municipality, 'LRB', 1, 'C');

        $pdf->SetFont($this->fontArialBold, 'B', 14);
        $pdf->setCellHeightRatio(1.25);
        $deptHeight = $pdf->getStringHeight($pageWidth * 0.7, "\nDISBURSEMENT VOUCHER\n");
        
        $pdf->SetFont('Times', '', 14);
        $pdf->MultiCell(
            $pageWidth * 0.7, 
            $deptHeight, 
            'DISBURSEMENT VOUCHER', 
            'L', 'C', 0, 0,
            maxh: $deptHeight,
            valign: 'M'
        );
        $pdf->SetFont('Times', 'B', 12);
        $pdf->MultiCell(0, $deptHeight, "No. $dvNo", 'LR', 'L', 0, 1);

        $pdf->setCellHeightRatio(1.6);
        $pdf->SetFont('Times', '', 10);
        $deptHeight = $pdf->getStringHeight($pageWidth * 0.13, 'Mode of Payment');
        $pdf->MultiCell(
            $pageWidth * 0.13, $deptHeight, 'Mode of Payment', 'LT', 'C', 0, 0,
        );
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->setCellHeightRatio(1.25);
        $pdf->SetFont('Times', '', 9);
        $pdf->Cell(0, $deptHeight / 3, '', 'LTR');
        $pdf->setXY($x, $y + ($deptHeight / 3));
        $pdf->Cell(
            $pageWidth * 0.06, $deptHeight / 3, '', 'L',
            ln: 0,
        );
        $pdf->SetFont('zapfdingbats', 'B', 9);
        $pdf->Cell(
            $pageWidth * 0.02, $deptHeight / 3, $modePayment === 'check' ? '3' : '', 1,
            align: 'C'
        );
        $pdf->SetFont('Times', '', 9);
        $pdf->Cell(
            $pageWidth * 0.20, $deptHeight / 3, '  Check', 0
        );
        $pdf->SetFont('zapfdingbats', 'B', 9);
        $pdf->Cell(
            $pageWidth * 0.02, $deptHeight / 3, $modePayment === 'cash' ? '3' : '', 1,
            align: 'C'
        );
        $pdf->SetFont('Times', '', 9);
        $pdf->Cell(
            $pageWidth * 0.20, $deptHeight / 3, '  Cash', 0
        );
        $pdf->SetFont('zapfdingbats', 'B', 9);
        $pdf->Cell(
            $pageWidth * 0.02, $deptHeight / 3, $modePayment === 'other' ? '3' : '', 1,
            align: 'C'
        );
        $pdf->SetFont('Times', '', 9);
        $pdf->Cell(
            0, $deptHeight / 3, 'Other', 'R',
            ln: 1
        );
        $pdf->MultiCell(
            0, $deptHeight / 3, '', 'LR',
            ln: 1,
            x: $x,
            y: $y + (($deptHeight / 3) * 2)
        );

        $pdf->SetFont('Times', '', 10);
        $deptHeight = $pdf->getStringHeight($pageWidth * 0.37, $payee);
        $pdf->SetFont('Times', '', 9);
        $deptTitleHeight = $pdf->getStringHeight($pageWidth * 0.25, 'Obligation Request No.');
        $pdf->SetFont('Times', '', 10);
        $pdf->MultiCell(
            $pageWidth * 0.13, $deptHeight + $deptTitleHeight, 'Payee', 'LT', 'C', 0, 0,
            maxh: $deptHeight + $deptTitleHeight,
            valign: 'M'
        );
        $pdf->SetFont('Times', 'B', 10);
        $pdf->MultiCell(
            $pageWidth * 0.37, $deptHeight + $deptTitleHeight, $payee, 'LT', 'L', 0, 0,
            maxh: $deptHeight + $deptTitleHeight,
            valign: 'M'
        );
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->SetFont('Times', '', 9);
        $pdf->Cell(
            $pageWidth * 0.25, $deptTitleHeight, 'TIN/Employee No.', 'LT'
        );
        $pdf->Cell(
            $pageWidth * 0.25, $deptTitleHeight, 'Obligation Request No.', 'LTR'
        );
        $pdf->SetFont('Times', '', 10);
        $pdf->MultiCell(
            $pageWidth * 0.25, $deptHeight, $tinNo, 'L', 'L', 0, 0,
            x: $x,
            y: $y + $deptTitleHeight,
            maxh: $deptHeight,
            valign: 'M'
        );
        $pdf->SetFont('Times', 'B', 10);
        $pdf->MultiCell(
            $pageWidth * 0.25, $deptHeight, $obrNo, 'LR', 'L',
            maxh: $deptHeight,
            valign: 'M'
        );

        $pdf->SetFont('Times', '', 11);
        $deptHeight = $pdf->getStringHeight($pageWidth * 0.37, $address);
        $pdf->SetFont('Times', '', 9);
        $deptTitleHeight1 = $pdf->getStringHeight($pageWidth * 0.5, 'Responsibility Center');
        $deptTitleHeight2 = $pdf->getStringHeight($pageWidth * 0.37, 'Office/Unit/Project:');
        $deptTitleHeight3 = $pdf->getStringHeight($pageWidth * 0.13, 'Code:');
        $deptOfficeHeight = $pdf->getStringHeight($pageWidth * 0.34, $office);
        $deptCodeHeight = $pdf->getStringHeight($pageWidth * 0.16, $responsibilityCenter);

        $subTitleHeight = max($deptTitleHeight2, $deptTitleHeight3);
        $officeCodeHeight = max($deptOfficeHeight, $deptCodeHeight);
        $addressHeight = max(
            $deptHeight, 
            $deptTitleHeight1 + $subTitleHeight + $officeCodeHeight
        );

        $pdf->SetFont('Times', '', 10);
        $pdf->MultiCell(
            $pageWidth * 0.13, $addressHeight, 'Address', 'LT', 'C', 0, 0,
            maxh: $addressHeight,
            valign: 'M'
        );
        $pdf->SetFont('Times', '', 11);
        $pdf->MultiCell(
            $pageWidth * 0.37, $addressHeight, $address, 'LT', 'L',
            ln: 0,
            maxh: $addressHeight,
            valign: 'M'
        );
        $pdf->SetFont('Times', '', 9);
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->MultiCell(
            0, $deptTitleHeight1, 'Responsibility Center', 'LTR', 'C',
            ln: 0,
            maxh: $deptTitleHeight1,
            valign: 'M'
        );
        $pdf->MultiCell(
            $pageWidth * 0.34, $subTitleHeight, 'Office/Unit/Project:', 'LT', 'L',
            ln: 0,
            x: $x,
            y: $y + $deptTitleHeight1,
            maxh: $subTitleHeight,
            valign: 'M'
        );
        $pdf->MultiCell(
            0, $subTitleHeight, 'Code:', 'LTR', 'L',
            ln: 0,
            maxh: $subTitleHeight,
            valign: 'M'
        );
        $pdf->MultiCell(
            $pageWidth * 0.34, $officeCodeHeight, $office, 'L', 'L',
            ln: 0,
            x: $x,
            y: $y + $deptTitleHeight1 + $subTitleHeight,
            maxh: $officeCodeHeight,
            valign: 'M'
        );
        $pdf->MultiCell(
            0, $officeCodeHeight, $responsibilityCenter, 'LR', 'L',
            maxh: $officeCodeHeight,
            valign: 'M'
        );

        $htmlTable = '
            <table border="1" cellpadding="2"><thead><tr>
                <th
                    width="84%"
                    align="center"
                >EXPLANATION</th>
                <th
                    width="16%"
                    align="center"
                >Amount</th>
            </tr></thead></table>
        ';

        $pdf->setCellHeightRatio(1.25);
        $pdf->SetFont('Times', 'B', 10);
        $pdf->writeHTML($htmlTable, ln: false);
        $pdf->Ln(0);

        $htmlTable = '
            <table 
                style="border-left: 1px solid black; border-right: 1px solid black;"
                cellpadding="2"
            ><tbody><tr>
                <td
                    style="border-right: 1px solid black"
                    width="84%"
                >'. $explanation .'</td>

                <td
                    width="16%"
                    align="right"
                >P'. $amount .'</td>
            </tr>
            <tr>
                <td
                    style="border-right: 1px solid black"
                    width="84%"
                ></td>
                <td
                    width="16%"
                    align="right"
                ></td>
            </tr></tbody></table>
        ';

        $pdf->SetFont('Times', '', 11);
        $pdf->writeHTML($htmlTable, ln: false);
        $pdf->Ln(0);

        $htmlTable = '
            <table 
                style="border-left: 1px solid black; border-right: 1px solid black;"
                cellpadding="2"
            ><tbody><tr>
                <td width="50%"></td>
                <td
                    style="border-bottom: 1px solid black; font-size: 10px;"
                    width="34%"
                >Amount Due</td>
                <td
                    style="border-left: 1px solid black; border-right: 1px solid black; border-bottom: 1px solid black; font-weight: bold;"
                    width="16%"
                    align="right"
                >P'. $amount .'</td>
            </tr>
            <tr>
                <td
                    colspan="3"
                    style="border-left: 1px solid black; border-right: 1px solid black;"
                    width="100%"
                ></td>
            </tr></tbody></table>
        ';

        $pdf->SetFont('Times', '', 11);
        $pdf->writeHTML($htmlTable, ln: false);

        $x = $pdf->GetX();
        $y = $pdf->GetY();

        try {
            if ($company->company_logo) {
                $imagePath = 'images/arrow-left.png';
                $pdf->Image(
                    $imagePath,
                    $pageWidth * 0.74,
                    $y - ($deptHeight * 2.4),
                    h: $deptHeight,
                    type: 'PNG',
                    resize: true,
                    dpi: 500,
                );
            }
        } catch (\Throwable $th) {}

        $pdf->Ln(0);

        $pdf->setCellHeightRatio(1.6);
        $pdf->SetFont('Times', '', 10);
        $pdf->Cell($pageWidth * 0.03, 0, 'A', 1, 0, 'C');
        $pdf->setCellHeightRatio(1.7);
        $pdf->SetFont('Times', 'BI', 9);
        $pdf->Cell($pageWidth * 0.47, 0, " Certified:", 'LT', 0);
        $pdf->setCellHeightRatio(1.6);
        $pdf->SetFont('Times', '', 10);
        $pdf->Cell($pageWidth * 0.03, 0, 'B', 1, 0, 'C');
        $pdf->setCellHeightRatio(1.7);
        $pdf->SetFont('Times', 'BI', 9);
        $pdf->Cell(0, 0, ' Certified:', 'LTR', 1);

        $pdf->setCellHeightRatio(1.25);
        $pdf->Cell($pageWidth * 0.05, 0, '', 'L', 0, 'C');
        $pdf->setCellHeightRatio(1.4);
        $pdf->SetFont('zapfdingbats', 'B', 9);
        $pdf->Cell($pageWidth * 0.025, 0, $accountantCertifiedChoices['allotment_obligated'] ? '3' : '', 1, 0, 'C');
        $pdf->setCellHeightRatio(1.25);
        $pdf->SetFont('Times', 'I', 9);
        $pdf->Cell($pageWidth * 0.425, 0, ' Allotment obligated for the purpose as indicated above', 0, 0);
        $pdf->SetFont('Times', '', 9);
        $pdf->Cell(0, 0, 'Funds Available', 'LR', 1, 'C');

        $pdf->setCellHeightRatio(0.6);
        $pdf->Cell($pageWidth * 0.5, 0, '', 'L');
        $pdf->Cell(0, 0, '', 'LR', 1);

        $pdf->setCellHeightRatio(1.25);
        $pdf->Cell($pageWidth * 0.05, 0, '', 'L', 0, 'C');
        $pdf->setCellHeightRatio(1.4);
        $pdf->SetFont('zapfdingbats', 'B', 9);
        $pdf->Cell($pageWidth * 0.025, 0, $accountantCertifiedChoices['document_complete'] ? '3' : '', 1, 0, 'C');
        $pdf->setCellHeightRatio(1.25);
        $pdf->SetFont('Times', 'I', 9);
        $pdf->Cell($pageWidth * 0.425, 0, 'Supporting documents complete and proper', 0, 0);
        $pdf->SetFont('Times', '', 9);
        $pdf->Cell(0, 0, '', 'LR', 1, 'C');

        $pdf->setCellHeightRatio(0.6);
        $pdf->Cell($pageWidth * 0.5, 0, '', 'L');
        $pdf->Cell(0, 0, '', 'LR', 1);

        $pdf->setCellHeightRatio(1.6);
        $pdf->SetFont('Times', 'I', 9);
        $pdf->Cell($pageWidth * 0.13, 0, 'Signature', 'LT');
        $pdf->Cell($pageWidth * 0.37, 0, '', 'LT');
        $pdf->Cell($pageWidth * 0.13, 0, 'Signature', 'LT');
        $pdf->Cell(0, 0, '', 'LTR', 1);

        $pdf->setCellHeightRatio(1.25);
        $pdf->SetFont('Times', 'I', 9);

        $signatoryHeight = max(
            $pdf->getStringHeight($pageWidth * 0.25, $accountantName),
            $pdf->getStringHeight($pageWidth * 0.25, $treasurerName)
        );
        $signedDateHeight = max(
            $pdf->getStringHeight($pageWidth * 0.12, $accountantSignedDate),
            $pdf->getStringHeight($pageWidth * 0.12, $treasurerSignedDate)
        );
        $dateTitleHeight = $pdf->getStringHeight($pageWidth * 0.12, 'Date');

        $pdf->MultiCell(
            $pageWidth * 0.13, $signatoryHeight + $signedDateHeight, 'Printed Name', 'LT', 'L',
            ln: 0,
            maxh: $signatoryHeight + $signedDateHeight,
            valign: 'M'
        );
        $pdf->SetFont('Times', 'BI', 9);
        $pdf->MultiCell(
            $pageWidth * 0.25, $signatoryHeight + $signedDateHeight, $accountantName, 'LT', 'C',
            ln: 0,
            maxh: $signatoryHeight + $signedDateHeight,
            valign: 'M'
        );
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->SetFont('Times', 'I', 9);
        $pdf->MultiCell(
            $pageWidth * 0.12, $dateTitleHeight, 'Date', 'LT', 'L',
            ln: 0,
            maxh: $dateTitleHeight,
            valign: 'M'
        );
        $pdf->MultiCell(
            $pageWidth * 0.12, $signedDateHeight, $accountantSignedDate, 'L', 'L',
            ln: 0,
            x: $x,
            y: $y + $dateTitleHeight
        );
        $pdf->MultiCell(
            $pageWidth * 0.13, $signatoryHeight + $signedDateHeight, 'Printed Name', 'LT', 'L',
            ln: 0,
            x: $x + ($pageWidth * 0.12),
            y: $y,
            maxh: $signatoryHeight + $signedDateHeight,
            valign: 'M'
        );
        $pdf->SetFont('Times', 'BI', 9);
        $pdf->MultiCell(
            $pageWidth * 0.25, $signatoryHeight + $signedDateHeight, $treasurerName, 'LT', 'C',
            ln: 0,
            maxh: $signatoryHeight + $signedDateHeight,
            valign: 'M'
        );
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->SetFont('Times', 'I', 9);
        $pdf->MultiCell(
            $pageWidth * 0.12, $dateTitleHeight, 'Date', 'LTR', 'L',
            ln: 0,
            maxh: $dateTitleHeight,
            valign: 'M'
        );
        $pdf->MultiCell(
            $pageWidth * 0.12, $signedDateHeight, $treasurerSignedDate, 'LR', 'L',
            x: $x,
            y: $y + $dateTitleHeight
        );

        $pdf->SetFont('Times', 'I', 9);
        $positionHeight = max(
            $pdf->getStringHeight($pageWidth * 0.37, $accountantPosition),
            $pdf->getStringHeight($pageWidth * 0.37, $treasurerPosition)
        );
        $positionTitleHeight = max(
            $pdf->getStringHeight($pageWidth * 0.37, 'Head, Accounting Unit/ Authorized Representative'),
            $pdf->getStringHeight($pageWidth * 0.37, 'Treasurer/Authorized Representative')
        );

        $pdf->MultiCell(
            $pageWidth * 0.13, $positionHeight + $positionTitleHeight, 'Position', 'LT', 'L',
            ln: 0,
            maxh: $positionHeight + $positionTitleHeight,
            valign: 'M'
        );
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->MultiCell(
            $pageWidth * 0.37, $positionHeight, $accountantPosition, 'LT', 'C',
            ln: 0,
            maxh: $positionHeight,
            valign: 'M'
        );
        $pdf->MultiCell(
            $pageWidth * 0.37, $positionTitleHeight, 'Head, Accounting Unit/ Authorized Representative', 'LT', 'C',
            ln: 0,
            x: $x,
            y: $y + $positionHeight,
            maxh: $positionTitleHeight,
            valign: 'M'
        );
        $pdf->MultiCell(
            $pageWidth * 0.13, $positionHeight + $positionTitleHeight, 'Position', 'LT', 'L',
            ln: 0,
            x: $x + ($pageWidth * 0.37),
            y: $y,
            maxh: $positionHeight + $positionTitleHeight,
            valign: 'M'
        );
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->MultiCell(
            $pageWidth * 0.37, $positionHeight, $treasurerPosition, 'LTR', 'C',
            ln: 0,
            maxh: $positionHeight,
            valign: 'M'
        );
        $pdf->MultiCell(
            0, $positionTitleHeight, 'Treasurer/Authorized Representative', 'LTR', 'C',
            x: $x,
            y: $y + $positionHeight,
            maxh: $positionTitleHeight,
            valign: 'M'
        );

        // -------- C & D label section --------
        $pdf->setCellHeightRatio(1.6);
        $pdf->SetFont('Times', '', 10);
        $pdf->Cell($pageWidth * 0.03, 0, 'C', 1, 0, 'C');
        $pdf->setCellHeightRatio(1.8);
        $pdf->SetFont('Times', 'BI', 9);
        $pdf->Cell($pageWidth * 0.47, 0, " Approved Payment", 'LT', 0);
        $pdf->setCellHeightRatio(1.6);
        $pdf->SetFont('Times', '', 10);
        $pdf->Cell($pageWidth * 0.03, 0, 'D', 1, 0, 'C');
        $pdf->setCellHeightRatio(1.8);
        $pdf->SetFont('Times', 'BI', 9);
        $pdf->Cell(0, 0, ' Received Payment', 'LTR', 1);

        // -------- C signature & D information section --------
        $pdf->setCellHeightRatio(1.25);
        $pdf->SetFont('Times', 'I', 9);
        $titleHeight = max(
            $pdf->getStringHeight($pageWidth * 0.13, 'Check No.'),
            $pdf->getStringHeight($pageWidth * 0.25, 'Bank Name'),
            $pdf->getStringHeight($pageWidth * 0.12, 'Date')
        );
        $valueHeight = max(
            $pdf->getStringHeight($pageWidth * 0.13, $checkNo),
            $pdf->getStringHeight($pageWidth * 0.25, $bankName),
            $pdf->getStringHeight($pageWidth * 0.12, $checkDate)
        );

        $pdf->MultiCell(
            $pageWidth * 0.13, $titleHeight + $valueHeight, 'Signature', 'LT',
            ln: 0,
            maxh: $titleHeight + $valueHeight,
            valign: 'M'
        );
        $pdf->MultiCell($pageWidth * 0.37, $titleHeight + $valueHeight, '', 'LT', ln: 0);
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->MultiCell(
            $pageWidth * 0.13, $titleHeight, 'Check No.', 'LT', 'L', 
            ln: 0,
            maxh: $titleHeight,
            valign: 'M'
        );
        $pdf->SetFont('Times', '', 9);
        $pdf->MultiCell(
            $pageWidth * 0.25, $titleHeight, 'Bank Name', 'LT', 'L', 
            ln: 0,
            maxh: $titleHeight,
            valign: 'M'
        );
        $pdf->SetFont('Times', 'I', 9);
        $pdf->MultiCell(
            $pageWidth * 0.12, $titleHeight, 'Date', 'LTR', 'L', 
            ln: 0,
            maxh: $titleHeight,
            valign: 'M'
        );
        $pdf->MultiCell(
            $pageWidth * 0.13, $valueHeight, $checkNo, 'L', 'L', 
            ln: 0,
            x: $x,
            y: $y + $titleHeight,
            maxh: $valueHeight,
            valign: 'M'
        );
        $pdf->SetFont('Times', '', 9);
        $pdf->MultiCell(
            $pageWidth * 0.25, $valueHeight, $bankName, 'L', 'L', 
            ln: 0,
            maxh: $valueHeight,
            valign: 'M'
        );
        $pdf->SetFont('Times', 'I', 9);
        $pdf->MultiCell(
            $pageWidth * 0.12, $valueHeight, $checkDate, 'LR', 'L', 
            maxh: $valueHeight,
            valign: 'M'
        );

        // -------- C signatory & D signatory section --------
        $pdf->setCellHeightRatio(1.6);
        $pdf->SetFont('Times', 'I', 9);
        $titleHeight = $pdf->getStringHeight($pageWidth * 0.13, 'Signature');
        $pdf->setCellHeightRatio(1.25);
        $subTitleHeight = $pdf->getStringHeight($pageWidth * 0.12, 'Date');
        $subValueHeight = $pdf->getStringHeight($pageWidth * 0.12, $checkDate);
        $valueHeight = max(
            $pdf->getStringHeight($pageWidth * 0.25, $receivedName),
            $subTitleHeight + $subValueHeight
        );
        $pdf->SetFont('Times', 'I', 9);
        $dateTitleHeight = $pdf->getStringHeight($pageWidth * 0.12, 'Date');
        $signatoryDateHeight = max(
            $pdf->getStringHeight($pageWidth * 0.12, $headSignedDate),
            ($titleHeight + $valueHeight) - $dateTitleHeight
        );
        $pdf->SetFont('Times', 'BI', 9);
        $signatoryHeight = max(
            $pdf->getStringHeight($pageWidth * 0.25, $headName),
            $titleHeight + $valueHeight
        );

        $pdf->SetFont('Times', 'I', 9);
        $pdf->MultiCell(
            $pageWidth * 0.13, $signatoryHeight, 'Printed Name', 'LT', 'L',
            ln: 0,
            maxh: $signatoryHeight,
            valign: 'M'
        );
        $pdf->SetFont('Times', 'BI', 9);
        $pdf->MultiCell(
            $pageWidth * 0.25, $signatoryHeight, $headName, 'LT', 'C',
            ln: 0,
            maxh: $signatoryHeight,
            valign: 'M'
        );
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->SetFont('Times', 'I', 9);
        $pdf->MultiCell(
            $pageWidth * 0.12, $dateTitleHeight, 'Date', 'LT', 'L',
            ln: 0,
            maxh: $dateTitleHeight,
            valign: 'M'
        );
        $pdf->MultiCell(
            $pageWidth * 0.12, $signatoryDateHeight, $headSignedDate, 'L', 'L',
            ln: 0,
            x: $x,
            y: $y + $dateTitleHeight,
            maxh: $signatoryDateHeight,
            valign: 'M'
        );
        $pdf->setXY(
            $pdf->GetX(),
            $pdf->GetY() - $dateTitleHeight
        );
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->setCellHeightRatio(1.6);
        $pdf->MultiCell(
            $pageWidth * 0.13, $titleHeight, 'Signature', 'LT', 'L',
            ln: 0,
            maxh: $titleHeight,
            valign: 'M'
        );
        $pdf->MultiCell(
            $pageWidth * 0.37, $titleHeight, '', 'LTR', 'L',
            ln: 0,
            maxh: $titleHeight,
            valign: 'M'
        );
        $pdf->setCellHeightRatio(1.25);
        $pdf->MultiCell(
            $pageWidth * 0.13, $valueHeight, 'Printed Name', 'LT', 'L',
            ln: 0,
            x: $x,
            y: $y + $titleHeight,
            maxh: $valueHeight,
            valign: 'M'
        );
        $pdf->SetFont('Times', 'B', 8);
        $pdf->MultiCell(
            $pageWidth * 0.25, $valueHeight, $receivedName, 'LT', 'C',
            ln: 0,
            maxh: $valueHeight,
            valign: 'M'
        );
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->SetFont('Times', 'I', 9);
        $pdf->MultiCell(
            $pageWidth * 0.12, $subTitleHeight, 'Date', 'LTR', 'L',
            ln: 0,
            maxh: $subTitleHeight,
            valign: 'M'
        );
        $pdf->MultiCell(
            $pageWidth * 0.12, $subValueHeight, $receivedDate, 'LR', 'L',
            x: $x,
            y: $y + $subTitleHeight,
            maxh: $subValueHeight,
            valign: 'M'
        );

        // -------- C signatory position & D information section --------
        $pdf->setCellHeightRatio(1.25);
        $pdf->SetFont('Times', 'I', 9);
        $titleHeight = max(
            $pdf->getStringHeight($pageWidth * 0.25, 'OR/Other Documents'),
            $pdf->getStringHeight($pageWidth * 0.13, 'JEV No.'),
            $pdf->getStringHeight($pageWidth * 0.12, 'Date'),
        );
        $pdf->SetFont('Times', 'BI', 10);
        $pHeight = $pdf->getStringHeight($pageWidth * 0.37, $headPosition);
        $positionHeight = max(
            $pHeight,
            $titleHeight
        );
        $pdf->SetFont('Times', 'I', 9);
        $valueHeight = max(
            $pdf->getStringHeight($pageWidth * 0.13, $orOtherDocument),
            $pdf->getStringHeight($pageWidth * 0.25, $jevNo),
            $pdf->getStringHeight($pageWidth * 0.12, $jevDate),
        );
        $positionTitleHeight = max(
            $pdf->getStringHeight($pageWidth * 0.37, 'Agency Head / Authorized Representative'),
            $valueHeight
        );

        $pdf->MultiCell(
            $pageWidth * 0.13, $positionHeight + $positionTitleHeight, 'Position', 'LTB', 'L',
            ln: 0,
            maxh: $positionHeight + $positionTitleHeight,
            valign: 'M'
        );
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->setCellHeightRatio(1.1);
        $pdf->SetFont('Times', 'BI', 10);
        $pdf->MultiCell(
            $pageWidth * 0.37, $positionHeight, $headPosition, 'LT', 'C',
            ln: 0,
            maxh: $positionHeight,
            valign: 'M'
        );
        $pdf->SetFont('Times', 'I', 9);
        $pdf->MultiCell(
            $pageWidth * 0.37, $positionTitleHeight, 'Agency Head / Authorized Representative', 'LTB', 'C',
            ln: 0,
            x: $x,
            y: $y + $positionHeight,
            maxh: $positionTitleHeight,
            valign: 'M'
        );
        $pdf->setCellHeightRatio(1.25);
        $x = $pdf->GetX();
        $y = $pdf->GetY() - $positionHeight;
        $pdf->MultiCell(
            $pageWidth * 0.25, $titleHeight, 'OR/Other Documents', 'LT', 'L',
            ln: 0,
            x: $x,
            y: $y,
            maxh: $titleHeight,
            valign: 'M'
        );
        $pdf->MultiCell(
            $pageWidth * 0.13, $titleHeight, 'JEV No.', 'LT', 'L',
            ln: 0,
            maxh: $titleHeight,
            valign: 'M'
        );
        $pdf->MultiCell(
            $pageWidth * 0.12, $titleHeight, 'Date', 'LTR', 'L',
            ln: 0,
            maxh: $titleHeight,
            valign: 'M'
        );
        $valueHeight = max(
            $positionHeight + $positionTitleHeight - $titleHeight,
            $valueHeight
        );
        $pdf->MultiCell(
            $pageWidth * 0.25, $valueHeight, $orOtherDocument, 'LB', 'L',
            ln: 0,
            x: $x,
            y: $y + $titleHeight,
            maxh: $valueHeight,
            valign: 'M'
        );
        $pdf->MultiCell(
            $pageWidth * 0.13, $valueHeight, $jevNo, 'LB', 'L',
            ln: 0,
            maxh: $valueHeight,
            valign: 'M'
        );
        $pdf->MultiCell(
            $pageWidth * 0.12, $valueHeight, $jevDate, 'LRB', 'L',
            maxh: $valueHeight,
            valign: 'M'
        );

        $pdfBlob = $pdf->Output($filename, 'S');
        $pdfBase64 = base64_encode($pdfBlob);

        return $pdfBase64;
    }
}
