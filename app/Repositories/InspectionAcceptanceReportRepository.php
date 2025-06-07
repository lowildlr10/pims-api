<?php

namespace App\Repositories;

use App\Enums\InspectionAcceptanceReportStatus;
use App\Helpers\FileHelper;
use App\Interfaces\InspectionAcceptanceReportInterface;
use App\Models\Company;
use App\Models\InspectionAcceptanceReport;
use App\Models\InspectionAcceptanceReportItem;
use App\Models\Location;
use App\Models\Log;
use App\Models\PurchaseRequestItem;
use Exception;
use Illuminate\Support\Collection;
use TCPDF;
use TCPDF_FONTS;

class InspectionAcceptanceReportRepository implements InspectionAcceptanceReportInterface
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

    public function storeUpdate(array $data, ?InspectionAcceptanceReport $inspectionAcceptanceReport = NULL): InspectionAcceptanceReport
    {
        if (!empty($inspectionAcceptanceReport)) {
            $inspectionAcceptanceReport->update($data);
        } else {
            $inspectionAcceptanceReport = InspectionAcceptanceReport::create(
                array_merge(
                    $data,
                    [
                        'iar_no' => $this->generateNewIarNumber(),
                        'status' => InspectionAcceptanceReportStatus::DRAFT,
                        'status_timestamps' => json_encode((Object) [])
                    ]
                )
            );

            $this->storeUpdateItems(
                collect(isset($data['items']) && !empty($data['items']) ? $data['items'] : []),
                $inspectionAcceptanceReport
            );
        }

        return $inspectionAcceptanceReport;
    }

    private function storeUpdateItems(Collection $items, InspectionAcceptanceReport $inspectionAcceptanceReport): void
    {
        foreach ($items as $item) {
            InspectionAcceptanceReportItem::where('inspection_acceptance_report_id', $inspectionAcceptanceReport->id)
                ->where('po_item_id', $item['po_item_id'])
                ->delete();

            InspectionAcceptanceReportItem::create([
                'inspection_acceptance_report_id' => $inspectionAcceptanceReport->id,
                'pr_item_id' => $item['pr_item_id'],
                'po_item_id' => $item['po_item_id']
            ]);
        }
    }

    private function generateNewIarNumber(): string
    {
        $month = date('m');
        $year = date('Y');
        $sequence = InspectionAcceptanceReport::whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->count() + 1;

        return "{$year}-{$sequence}-{$month}";
    }

    public function print(array $pageConfig, string $iarId): array
    {
        try {
            $company = Company::first();
            $iar = InspectionAcceptanceReport::with([
                'supplier:id,supplier_name',

                'items' => function($query) {
                    $query->orderBy(
                        PurchaseRequestItem::select('item_sequence')
                            ->whereColumn(
                                'inspection_acceptance_report_items.pr_item_id', 'purchase_request_items.id'
                            ),
                        'asc'
                    );
                },
                'items.pr_item:id,unit_issue_id,item_sequence,quantity,stock_no',
                'items.pr_item.unit_issue:id,unit_name',
                'items.po_item:id,description,brand_model,unit_cost,total_cost',

                'signatory_inspection:id,user_id',
                'signatory_inspection.user:id,firstname,middlename,lastname,allow_signature,signature',
                'signatory_inspection.detail' => function ($query) {
                    $query->where('document', 'iar')
                        ->where('signatory_type', 'inspection');
                },

                'acceptance:id,firstname,middlename,lastname,allow_signature,signature,position_id,designation_id',
                'acceptance.position:id,position_name',
                'acceptance.designation:id,designation_name',

                'purchase_request:id,section_id',
                'purchase_request.section:id,section_name',
                'purchase_order:id,po_no,po_date'
            ])->find($iarId);

            $filename = "IAR-{$iar->iar_no}.pdf";
            $blob = $this->generateInspectionAccepantceReportDoc($filename, $pageConfig, $iar, $company);

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

    private function generateInspectionAccepantceReportDoc(
        string $filename, array $pageConfig, InspectionAcceptanceReport $data, Company $company
    ): string
    {
        $supplier = $data->supplier;
        $purchaseOrder = $data->purchase_order;
        $purchaseRequest = $data->purchase_request;
        $section = $purchaseRequest->section;
        $items = $data->items;
        $signatoryInspection = $data->signatory_inspection;
        $acceptance = $data->acceptance;

        $pdf = new TCPDF($pageConfig['orientation'], $pageConfig['unit'], $pageConfig['dimension']);

        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor(env('APP_NAME'));
        $pdf->SetTitle($filename);
        $pdf->SetSubject('Purchase Order');
        $pdf->SetMargins(
            $pdf->getPageWidth() * 0.08,
            $pdf->getPageHeight() * 0.05,
            $pdf->getPageWidth() * 0.08
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

        // $pdf->setCellHeightRatio(1.6);
        $pdf->Cell(0, 0, '', 'LTR', 1, 'C');
        $pdf->SetFont($this->fontArialBold, 'B', 11);
        $pdf->Cell(0, 0, 'INSPECTION AND ACCEPTANCE REPORT', 'LR', 1, 'C');
        $pdf->Cell(0, 0, '', 'LR', 1, 'C');
        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->Cell(0, 0, strtoupper("{$company->municipality}, {$company->province}"), 'LR', 1, 'C');
        $pdf->Cell(0, 0, $company->company_type, 'LR', 1, 'C');
        $pdf->Cell(0, 0, '', 'LR', 1, 'C');

        $pdf->setCellHeightRatio(1.5);
        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->Cell($pageWidth * 0.1, 0, 'Supplier:', 'L', 0, 'L');
        $pdf->Cell($pageWidth * 0.36, 0, $supplier->supplier_name, 'B', 0, 'L');
        $pdf->Cell($pageWidth * 0.13, 0, 'IAR No.', '', 0, 'R');
        $pdf->Cell($pageWidth * 0.17, 0, $data->iar_no, 'B', 0, 'L');
        $pdf->Cell($pageWidth * 0.08, 0, 'Date:', '', 0, 'R');
        $pdf->Cell(0, 0, date_format(date_create($data->iar_date), 'm/d/Y'), 'BR', 1, 'L');

        $pdf->Cell($pageWidth * 0.1, 0, 'PO No.', 'L', 0, 'L');
        $pdf->Cell($pageWidth * 0.24, 0, $purchaseOrder->po_no, 'B', 0, 'L');
        $pdf->Cell($pageWidth * 0.12, 0, 'Date:', '', 0, 'L');
        $pdf->Cell($pageWidth * 0.13, 0, date_format(date_create($data->purchase_order->po_date), 'm/d/Y'), 'B', 0, 'L');
        $pdf->Cell($pageWidth * 0.11, 0, 'Invoice No.', '', 0, 'R');
        $pdf->Cell($pageWidth * 0.06, 0, $data->invoice_no, 'B', 0, 'L');
        $pdf->Cell($pageWidth * 0.08, 0, 'Date:', '', 0, 'R');
        $pdf->Cell(0, 0, date_format(date_create($data->invoice_date), 'm/d/Y'), 'BR', 1, 'L');

        $pdf->Cell($pageWidth * 0.31, 0, 'Requesting Office/Dept.', 'L', 0, 'L');
        $pdf->Cell($pageWidth * 0.5, 0, $section->section_name, 'B', 0, 'L');
        $pdf->Cell(0, 0, '', 'R', 1, 'L');

        $pdf->setCellHeightRatio(1.25);
        $pdf->Cell(0, 0, '', 'LR', 1, 'C');

        $htmlTable = '
            <table border="1" cellpadding="2"><thead><tr>
                <th
                    width="10%"
                    align="center"
                >Item No.</th>
                <th
                    width="9%"
                    align="center"
                >Unit</th>
                <th
                    width="53%"
                    align="center"
                >Description</th>
                <th
                    width="28%"
                    align="center"
                >Quantity</th>
            </tr></thead></table>
        ';

        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->writeHTML($htmlTable, ln: false);
        $pdf->Ln(0);

        $htmlTable = '<table border="1" cellpadding="2"><tbody>';

        foreach ($items ?? [] as $item) {
            $prItem = $item->pr_item;
            $poItem = $item->po_item;
            $description = trim(str_replace("\r", '<br />', $poItem->description));
            $description = str_replace("\n", '<br />', $description);

            $htmlTable .= '
                <tr>
                    <td
                        width="10%"
                        align="center"
                    >'. $prItem->stock_no .'</td>
                    <td
                        width="9%"
                        align="center"
                    >'. $prItem->unit_issue->unit_name .'</td>
                    <td
                        width="53%"
                        align="left"
                    >'. $description .'</td>
                    <td
                        width="28%"
                        align="center"
                    >'. $prItem->quantity .'</td>
                </tr>
            ';
        }

        $htmlTable .= '</tbody></table>';
        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->writeHTML($htmlTable, ln: false);
        $pdf->Ln(0);

        $pdf->SetFont($this->fontArial, '', 2);
        $pdf->Cell(0, 0, '', 'LR', 1, 'C');

        $pdf->SetFont($this->fontArialBold, 'B', 11);
        $pdf->Cell($pageWidth * 0.1, 0, '', 'LT', 0, 'L');
        $pdf->Cell(0, 0, 'INSPECTION', 'TR', 1, 'L');

        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->Cell($pageWidth * 0.19, 0, 'Date Inspected:', 'L', 0, 'C');
        $pdf->Cell($pageWidth * 0.38, 0, date_format(date_create($data->inspected_date), 'm/d/Y'), 'B', 0, 'L');
        $pdf->Cell(0, 0, '', 'R', 1, 'C');

        $pdf->Cell(0, 0, '', 'LR', 1, 'C');

        $pdf->Cell($pageWidth * 0.58, 0, '', 'L', 0, 'L');
        $pdf->Cell($pageWidth * 0.09, 0, '', '', 0, 'L');
        $pdf->SetFont($this->fontArialBold, 'BU', 11);
        $pdf->Cell($pageWidth * 0.23, 0, strtoupper($signatoryInspection?->user->fullname), '', 0, 'C');
        $pdf->Cell(0, 0, '', 'R', 1, 'L');

        $pdf->Cell($pageWidth * 0.1, 0, '', 'L', 0, 'L');
        $pdf->SetFont($this->fontArial, '', 16);
        $pdf->Cell($pageWidth * 0.04, 0, $data->inspected ? 'x' : '', 'LTRB', 0, 'C');
        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->Cell($pageWidth * 0.44, 0, 'Inspected, verified and found OK', 0, 0, 'L');
        $pdf->Cell($pageWidth * 0.09, 0, '', '', 0, 'L');
        $pdf->Cell($pageWidth * 0.23, 0, strtoupper($signatoryInspection?->detail?->position), '', 0, 'C');
        $pdf->Cell(0, 0, '', 'R', 1, 'L');

        $pdf->Cell($pageWidth * 0.1, 0, '', 'L', 0, 'L');
        $pdf->Cell($pageWidth * 0.04, 0, '', 0, 0, 'L');
        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->Cell($pageWidth * 0.44, 0, 'as to quantity and specifications', 0, 0, 'L');
        $pdf->Cell($pageWidth * 0.09, 0, '', '', 0, 'L');
        $pdf->Cell($pageWidth * 0.23, 0, 'Inspection Officer', 'T', 0, 'C');
        $pdf->Cell(0, 0, '', 'R', 1, 'L');

        $pdf->Cell(0, 0, '', 'LR', 1, 'L');

        $pdf->SetFont($this->fontArial, '', 2);
        $pdf->Cell(0, 0, '', 'LTR', 1, 'C');

        $pdf->SetFont($this->fontArialBold, 'B', 11);
        $pdf->Cell($pageWidth * 0.1, 0, '', 'LT', 0, 'L');
        $pdf->Cell(0, 0, 'ACCEPTANCE', 'TR', 1, 'L');

        $pdf->Cell(0, 0, '', 'LR', 1, 'C');

        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->Cell($pageWidth * 0.19, 0, 'Date Received:', 'L', 0, 'C');
        $pdf->Cell($pageWidth * 0.38, 0, date_format(date_create($data->received_date), 'm/d/Y'), 'B', 0, 'L');
        $pdf->Cell(0, 0, '', 'R', 1, 'C');

        $pdf->Cell(0, 0, '', 'LR', 1, 'L');

        $pdf->Cell($pageWidth * 0.1, 0, '', 'L', 0, 'L');
        $pdf->SetFont($this->fontArial, '', 16);
        $pdf->Cell($pageWidth * 0.04, 0, !is_null($data->acceptance_completed) && $data->acceptance_completed ? 'x' : '', 'LTRB', 0, 'C');
        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->Cell($pageWidth * 0.73, 0, "   Complete", 0, 0, 'L');
        $pdf->Cell(0, 0, '', 'R', 1, 'L');

        $pdf->Cell($pageWidth * 0.1, 0, '', 'L', 0, 'L');
        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->Cell($pageWidth * 0.04, 0, '', 0, 0, 'C');
        $pdf->Cell($pageWidth * 0.53, 0, '', 0, 0, 'L');
        $pdf->SetFont($this->fontArialBold, 'BU', 11);
        $pdf->Cell($pageWidth * 0.23, 0, strtoupper($acceptance?->fullname), '', 0, 'C');
        $pdf->Cell(0, 0, '', 'R', 1, 'L');

        $pdf->Cell($pageWidth * 0.1, 0, '', 'L', 0, 'L');
        $pdf->SetFont($this->fontArial, '', 16);
        $pdf->Cell($pageWidth * 0.04, 0, !is_null($data->acceptance_completed) && !$data->acceptance_completed ? 'x' : '', 'LTRB', 0, 'C');
        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->Cell($pageWidth * 0.44, 0, '   Partial', 0, 0, 'L');
        $pdf->Cell($pageWidth * 0.09, 0, '', '', 0, 'L');
        $pdf->SetFont($this->fontArial, 'U', 10);
        $pdf->Cell($pageWidth * 0.23, 0, strtoupper($acceptance?->position->position_name), '', 0, 'C');
        $pdf->Cell(0, 0, '', 'R', 1, 'L');

        $pdf->SetFont($this->fontArial, '', 30);
        $pdf->Cell(0, 0, '', 'LRB', 1, 'L');

        $pdfBlob = $pdf->Output($filename, 'S');
        $pdfBase64 = base64_encode($pdfBlob);

        return $pdfBase64;
    }
}
