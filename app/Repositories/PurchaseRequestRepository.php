<?php

namespace App\Repositories;

use App\Helpers\FileHelper;
use App\Interfaces\PurchaseRequestRepositoryInterface;
use App\Models\Company;
use App\Models\Log;
use App\Models\PurchaseRequest;
use TCPDF;
use TCPDF_FONTS;

class PurchaseRequestRepository implements PurchaseRequestRepositoryInterface
{
    public function __construct() {
        $this->appUrl = env('APP_URL') ?? 'http://localhost';
        $this->fontArial = TCPDF_FONTS::addTTFfont('fonts/arial.ttf', 'TrueTypeUnicode', '', 96);
        $this->fontArialBold = TCPDF_FONTS::addTTFfont('fonts/arialbd.ttf', 'TrueTypeUnicode', '', 96);
        $this->fontArialItalic = TCPDF_FONTS::addTTFfont('fonts/ariali.ttf', 'TrueTypeUnicode', '', 96);
        $this->fontArialBoldItalic = TCPDF_FONTS::addTTFfont('fonts/arialbi.ttf', 'TrueTypeUnicode', '', 96);
        $this->fontArialNarrow = TCPDF_FONTS::addTTFfont('fonts/arialn.ttf', 'TrueTypeUnicode', '', 96);
        $this->fontArialNarrowBold = TCPDF_FONTS::addTTFfont('fonts/arialnb.ttf', 'TrueTypeUnicode', '', 96);
    }

    public function print(array $pageConfig, string $prId): array
    {
        try {
            $company = Company::first();
            $pr = PurchaseRequest::with([
                'funding_source:id,title',
                'section:id,section_name',

                'items' => function ($query) {
                    $query->orderBy('item_sequence');
                },
                'items.unit_issue:id,unit_name',

                'requestor:id,firstname,lastname,position_id,allow_signature,signature',
                'requestor.position:id,position_name',

                'signatory_cash_available:id,user_id',
                'signatory_cash_available.user:id,firstname,middlename,lastname,allow_signature,signature',
                'signatory_cash_available.detail' => function ($query) {
                    $query->where('document', 'pr')
                        ->where('signatory_type', 'cash_availability');
                },

                'signatory_approval:id,user_id',
                'signatory_approval.user:id,firstname,middlename,lastname,allow_signature,signature',
                'signatory_approval.detail' => function ($query) {
                    $query->where('document', 'pr')
                        ->where('signatory_type', 'approved_by');
                }
            ])->find($prId);

            $filename = "PR-{$pr->pr_no}.pdf";
            $blob = $this->generatePurchaseRequestDoc($filename, $pageConfig, $pr, $company);

            return [
                'success' => true,
                'blob' => $blob,
                'filename' => $filename
            ];
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'message' => $th->getMessage(),
                'blob' => '',
                'filename' => ''
            ];
        }
    }

    private function generatePurchaseRequestDoc(
        string $filename, array $pageConfig, PurchaseRequest $data, Company $company
    ): string
    {
        $pdf = new TCPDF($pageConfig['orientation'], $pageConfig['unit'], $pageConfig['dimension']);

        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor(env('APP_NAME'));
        $pdf->SetTitle($filename);
        $pdf->SetSubject('Purchase Request');
        $pdf->SetMargins(
            $pdf->getPageWidth() * 0.07,
            $pdf->getPageHeight() * 0.05,
            $pdf->getPageWidth() * 0.07
        );
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        // $pdf->SetAutoPageBreak(TRUE, 0.4);

        $pdf->AddPage();

        $pageWidth = $pdf->getPageWidth() * 0.86;

        $x = $pdf->GetX();
        $y = $pdf->GetY();

        try {
            if ($company->company_logo) {
                $imagePath = FileHelper::getPublicPath(
                    $company->company_logo
                );
                $pdf->Image(
                    $imagePath,
                    $x + ($x * 0.15),
                    $y + ($y * 0.09),
                    w: $pageConfig['orientation'] === 'P'
                        ? $x - ($x * 0.04)
                        : $y + ($y * 0.4),
                    type: 'PNG',
                    resize: true,
                    dpi: 500,
                );
            }
        } catch (\Throwable $th) {}

        $pdf->setCellHeightRatio(0.5);
        $pdf->SetLineStyle(['width' => $pdf->getPageWidth() * 0.002]);
        $pdf->Cell(0, 0, '', 'LTR', 1, 'C');
        $pdf->setCellHeightRatio(1.25);
        $pdf->SetFont($this->fontArialBold, '', 14);
        $pdf->Cell(0, 0, 'PURCHASE REQUEST', 'LR', 1, 'C');
        $pdf->SetFont($this->fontArialBold, 'BU', 10);
        $pdf->Cell(0, 0, "{$company->municipality}, {$company->province}", 'LR', 1, 'C');
        $pdf->SetFont($this->fontArialBold, 'B', 10);
        $pdf->Cell(0, 0, $company->company_type, 'LR', 1, 'C');

        $pdf->setCellHeightRatio(1.6);
        $pdf->Cell($pageWidth * 0.12, 0, 'Department:', 'LT', 0, 'L');
        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->Cell($pageWidth * 0.27, 0, $company->company_name, 'T', 0, 'L');
        $pdf->SetFont($this->fontArialBold, 'B', 10);
        $pdf->Cell($pageWidth * 0.07, 0, 'PR No.', 'LT', 0, 'L');
        $pdf->SetFont($this->fontArial, 'U', 10);
        $pdf->Cell($pageWidth * 0.17, 0, $data->pr_no, 'T', 0, 'L');
        $pdf->SetFont($this->fontArialBold, 'B', 10);
        $pdf->Cell($pageWidth * 0.055, 0, 'Date:', 'T', 0, 'L');
        $pdf->SetFont($this->fontArial, 'U', 10);
        $pdf->Cell(0, 0, date_format(date_create($data->pr_date), 'F j, Y'), 'TR', 1, 'L');

        $pdf->SetFont($this->fontArialBold, 'B', 10);
        $pdf->Cell($pageWidth * 0.08, 0, 'Section:', 'L', 0, 'L');
        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->Cell($pageWidth * 0.31, 0, $data->section->section_name, '', 0, 'L');
        $pdf->SetFont($this->fontArialBold, 'B', 10);
        $pdf->Cell($pageWidth * 0.075, 0, 'SAI No.', 'L', 0, 'L');
        $pdf->SetFont($this->fontArial, 'U', 10);
        $pdf->Cell($pageWidth * 0.165, 0, $data->sai_no, '', 0, 'L');
        $pdf->SetFont($this->fontArialBold, 'B', 10);
        $pdf->Cell($pageWidth * 0.055, 0, 'Date:', '', 0, 'L');
        $pdf->SetFont($this->fontArial, 'U', 10);
        $pdf->Cell(0, 0, date_format(date_create($data->sai_date), 'F j, Y'), 'R', 1, 'L');

        $pdf->SetFont($this->fontArialBold, 'B', 10);
        $pdf->Cell($pageWidth * 0.39, 0, '', 'L', 0, 'L');
        $pdf->Cell($pageWidth * 0.11, 0, 'ALOBS No.', 'L', 0, 'L');
        $pdf->SetFont($this->fontArial, 'U', 10);
        $pdf->Cell($pageWidth * 0.13, 0, $data->alobs_no, '', 0, 'L');
        $pdf->SetFont($this->fontArialBold, 'B', 10);
        $pdf->Cell($pageWidth * 0.055, 0, 'Date:', '', 0, 'L');
        $pdf->SetFont($this->fontArial, 'U', 10);
        $pdf->Cell(0, 0, date_format(date_create($data->alobs_date), 'F j, Y'), 'R', 1, 'L');

        $htmlTable = '
            <table border="1" cellpadding="2"><thead><tr>
                <th
                    width="11%"
                    align="center"
                >QTY</th>
                <th
                    width="8%"
                    align="center"
                >Unit of Issue</th>
                <th
                    width="47%"
                    align="center"
                >DESCRIPTION</th>
                <th
                    width="8%"
                    align="center"
                >Stock No.</th>
                <th
                    width="13%"
                    align="center"
                >Estimated Unit Cost</th>
                <th
                    width="13%"
                    align="center"
                >Estimated Cost</th>
            </tr></thead></table>
        ';

        $pdf->SetFont($this->fontArialBold, 'B', 10);
        $pdf->writeHTML($htmlTable, ln: false);
        $pdf->Ln(0);

        $htmlTable = '<table border="1" cellpadding="2"><tbody>';

        foreach ($data->items ?? [] as $item) {
            $description = trim(str_replace("\r", '<br />', $item->description));
            $description = str_replace("\n", '<br />', $description);

            $htmlTable .= '
                <tr>
                    <td
                        width="11%"
                        align="center"
                    >'. $item->quantity .'</td>
                    <td
                        width="8%"
                        align="center"
                    >'. $item->unit_issue->unit_name .'</td>
                    <td
                        width="47%"
                        align="left"
                    >'. $description .'</td>
                    <td
                        width="8%"
                        align="center"
                    >'. $item->stock_no .'</td>
                    <td
                        width="13%"
                        align="right"
                    >'. number_format($item->estimated_unit_cost, 2) .'</td>
                    <td
                        width="13%"
                        align="right"
                    >'. number_format($item->estimated_cost, 2) .'</td>
                </tr>
            ';
        }

        $htmlTable .= '</tbody></table>';

        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->writeHTML($htmlTable, ln: false);
        $pdf->Ln(0);

        $x = $pdf->GetX();
        $y = $pdf->GetY();

        $purpose = trim(str_replace("\r", '<br />', $data->purpose));
        $purpose = str_replace("\n", '<br />', $purpose);
        $purpose = $purpose . (
            isset($data->funding_source->title) && !empty($data->funding_source->title)
                ? " (Charged to {$data->funding_source->title})" : ''
        );
        $html = '
            <div style="border: 1px solid black;">
                <table cellpadding="2">
                    <tr>
                        <td style="color: red; font-size: 9px; font-style: italic;" width="9%">Purpose:</td>
                        <td width="91%" style="font-weight: bold; text-align: justify; font-size: 10px;">'. $purpose .'</td>
                    </tr>
                </table>
            </div>
        ';
        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->writeHTML($html, ln: false);
        $pdf->Ln(0);

        $pdf->SetFont($this->fontArialBold, 'B', 10);
        $pdf->Cell($pageWidth * 0.19, 0, '', 'LT', 0);
        $pdf->Cell($pageWidth * 0.27, 0, 'REQUESTED BY:', 'LT', 0, 'L');
        $pdf->Cell($pageWidth * 0.27, 0, 'CASH AVAILABILITY:', 'LT', 0, 'L');
        $pdf->Cell(0, 0, 'APPROVED BY:', 'LTR', 1, 'L');

        $pdf->Cell($pageWidth * 0.19, 0, '', 'L', 0);
        $pdf->Cell($pageWidth * 0.27, 0, '', 'LT', 0, 'L');
        $pdf->Cell($pageWidth * 0.27, 0, '', 'LT', 0, 'L');
        $pdf->Cell(0, 0, '', 'LTR', 1, 'L');

        $pdf->Cell($pageWidth * 0.19, 0, 'Signature:', 'LT', 0);

        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $xIncrement = $x * 0.25;
        $yIncrement = $x * 0.12;
        $signatureWidth =  $pageConfig['orientation'] === 'P'
            ? $x - ($x * 0.63)
            : $x - ($x * 0.69);

        $pdf->Cell($pageWidth * 0.27, 0, '', 'L', 0, 'L');

        try {
            if ($pageConfig['show_signatures']
                && $data->requestor->allow_signature
                && $data->requestor->signature) {
                $imagePath = FileHelper::getPublicPath(
                    $data->requestor->signature
                );
                $pdf->Image(
                    $imagePath,
                    $x + $xIncrement,
                    $y - $yIncrement,
                    w: $signatureWidth,
                    type: 'PNG',
                    resize: true,
                    dpi: 500
                );
            }
        } catch (\Throwable $th) {}

        $x = $pdf->GetX();
        $y = $pdf->GetY();

        $pdf->Cell($pageWidth * 0.27, 0, '', 'L', 0, 'L');

        try {
            if ($pageConfig['show_signatures']
                && $data->signatory_cash_available->user->allow_signature
                && $data->signatory_cash_available->user->signature) {
                $imagePath = FileHelper::getPublicPath(
                    $data->signatory_cash_available->user->signature
                );
                $pdf->Image(
                    $imagePath,
                    $x + $xIncrement,
                    $y - $yIncrement,
                    w: $signatureWidth,
                    type: 'PNG',
                    resize: true,
                    dpi: 500,
                );
            }
        } catch (\Throwable $th) {}

        $x = $pdf->GetX();
        $y = $pdf->GetY();

        $pdf->Cell(0, 0, '', 'LR', 1, 'L');

        try {
            if ($pageConfig['show_signatures']
                && $data->signatory_approval->user->allow_signature
                && $data->signatory_approval->user->signature) {
                $imagePath = FileHelper::getPublicPath(
                    $data->signatory_approval->user->signature
                );
                $pdf->Image(
                    $imagePath,
                    $x + $xIncrement,
                    $y - $yIncrement,
                    w: $signatureWidth,
                    type: 'PNG',
                    resize: true,
                    dpi: 500,
                );
            }
        } catch (\Throwable $th) {}

        $pdf->SetFont($this->fontArialBold, 'B', 10);
        $pdf->Cell($pageWidth * 0.19, 0, 'Printed Name:', 'LT', 0);
        $pdf->SetFont($this->fontArialBold, 'B', 9);
        $pdf->Cell($pageWidth * 0.27, 0, strtoupper($data->requestor->fullname), 'LT', 0, 'C');
        $pdf->Cell($pageWidth * 0.27, 0, strtoupper($data->signatory_cash_available->user->fullname), 'LT', 0, 'C');
        $pdf->Cell(0, 0, strtoupper($data->signatory_approval->user->fullname), 'LTR', 1, 'C');

        $pdf->SetFont($this->fontArialBold, 'B', 10);
        $pdf->Cell($pageWidth * 0.19, 0, 'Designation:', 'LTB', 0);
        $pdf->Cell($pageWidth * 0.27, 0, $data->requestor->position->position_name, 'LTB', 0, 'C');
        $pdf->Cell($pageWidth * 0.27, 0, $data->signatory_cash_available->detail->position, 'LTB', 0, 'C');
        $pdf->Cell(0, 0, $data->signatory_approval->detail->position, 'LTRB', 1, 'C');

        $pdfBlob = $pdf->Output($filename, 'S');
        $pdfBase64 = base64_encode($pdfBlob);

        return $pdfBase64;
    }
}
