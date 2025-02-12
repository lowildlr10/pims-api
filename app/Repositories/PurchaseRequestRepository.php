<?php

namespace App\Repositories;

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

            $company = Company::first();
            $pr = PurchaseRequest::with([
                'items',
                'items.unitIssue:id,unit_name'
            ])->find($prId);
            $filename = "PR-{$pr->pr_no}.pdf";

            $blob = $this->generatePurchaseRequestDoc($filename, $pageConfig, $pr, $company);

            return [
                'success' => true,
                'blob' => $blob,
                'filename' => $filename
            ];try {
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
        $pdf->Cell($pageWidth * 0.31, 0, $data->section_name, '', 0, 'L');
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
            $htmlTable .= '
                <tr>
                    <td
                        width="11%"
                        align="center"
                    >'. $item->quantity .'</td>
                    <td
                        width="8%"
                        align="center"
                    >'. $item->unitIssue->unit_name .'</td>
                    <td
                        width="47%"
                        align="left"
                    >'. $item->description .'</td>
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

        $pdf->SetFont($this->fontArial, 'I', 9);
        $pdf->SetTextColor(201, 33, 30);

        $x = $pdf->GetX();
        $y = $pdf->GetY();

        $pdf->MultiCell($pageWidth * 0.1, 0, 'Purpose:', 'L', 'L', 0, 0);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont($this->fontArialBold, 'B', 10);
        $pdf->MultiCell(
            0, 0,
            $data->purpose . (
                !empty($data->funding_source_title)
                    ? " (Charged to {$data->funding_source_title})" : ''
            ),
            'R', 'J', 0, 1, ishtml: true
        );
        $pdf->Cell(0, 0, '', 'LR', 1);
        $pdf->Line($x, $y, $pdf->GetX(), $pdf->GetY());

        $pdf->SetFont($this->fontArialBold, 'B', 10);
        $pdf->Cell($pageWidth * 0.19, 0, '', 'LT', 0);
        $pdf->Cell($pageWidth * 0.27, 0, 'REQUESTED BY:', 'LT', 0, 'L');
        $pdf->Cell($pageWidth * 0.27, 0, 'CASH AVAILABILITY', 'LT', 0, 'L');
        $pdf->Cell(0, 0, 'APPROVED BY:', 'LTR', 1, 'L');

        $pdf->Cell($pageWidth * 0.19, 0, '', 'L', 0);
        $pdf->Cell($pageWidth * 0.27, 0, '', 'LT', 0, 'L');
        $pdf->Cell($pageWidth * 0.27, 0, '', 'LT', 0, 'L');
        $pdf->Cell(0, 0, '', 'LTR', 1, 'L');

        $pdf->Cell($pageWidth * 0.19, 0, 'Signature:', 'LT', 0);
        $pdf->Cell($pageWidth * 0.27, 0, '', 'L', 0, 'L');

        // if ($data->requestor->allow_signature && $data->requestor->signature) {
        //     $pdf->Image(
        //         $data->requestor->signature,
        //         $pdf->GetX(),
        //         $pdf->GetY(),
        //         h: 0.6,
        //         type: 'PNG',
        //         resize: true,
        //         dpi: 500
        //     );
        // }

        $pdf->Cell($pageWidth * 0.27, 0, '', 'L', 0, 'L');

        // if ($data->signatoryCashAvailability->user->allow_signature && $data->signatoryCashAvailability->user->signature) {
        //     $pdf->Image(
        //         $data->signatoryCashAvailability->user->signature,
        //         $pdf->GetX(),
        //         $pdf->GetY(),
        //         h: $pdf->getPageHeight() * 0.05,
        //         type: 'PNG',
        //         resize: true,
        //         dpi: 300
        //     );
        // }

        $pdf->Cell(0, 0, '', 'LR', 1, 'L');

        // if ($data->signatoryApprovedBy->user->allow_signature && $data->signatoryApprovedBy->user->signature) {
        //     $pdf->Image(
        //         $data->signatoryApprovedBy->user->signature,
        //         $pdf->GetX(),
        //         $pdf->GetY(),
        //         h: 0.6,
        //         type: 'PNG',
        //         resize: true,
        //         dpi: 500
        //     );
        // }

        $pdf->SetFont($this->fontArialBold, 'B', 10);
        $pdf->Cell($pageWidth * 0.19, 0, 'Printed Name:', 'LT', 0);
        $pdf->SetFont($this->fontArialBold, 'B', 9);
        $pdf->Cell($pageWidth * 0.27, 0, strtoupper($data->requestor_fullname), 'LT', 0, 'C');
        $pdf->Cell($pageWidth * 0.27, 0, strtoupper($data->cash_availability_fullname), 'LT', 0, 'C');
        $pdf->Cell(0, 0, strtoupper($data->approver_fullname), 'LTR', 1, 'C');

        $pdf->SetFont($this->fontArialBold, 'B', 10);
        $pdf->Cell($pageWidth * 0.19, 0, 'Designation:', 'LTB', 0);
        $pdf->Cell($pageWidth * 0.27, 0, $data->requestor_position, 'LTB', 0, 'C');
        $pdf->Cell($pageWidth * 0.27, 0, $data->cash_availability_position, 'LTB', 0, 'C');
        $pdf->Cell(0, 0, $data->approver_position, 'LTRB', 1, 'C');

        $pdfBlob = $pdf->Output($filename, 'S');
        $pdfBase64 = base64_encode($pdfBlob);

        return $pdfBase64;
    }
}
