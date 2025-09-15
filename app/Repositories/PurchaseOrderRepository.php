<?php

namespace App\Repositories;

use App\Enums\PurchaseOrderStatus;
use App\Helpers\FileHelper;
use App\Helpers\StatusTimestampsHelper;
use App\Interfaces\PurchaseOrderRepositoryInterface;
use App\Jobs\StorePoItems;
use App\Models\Company;
use App\Models\DeliveryTerm;
use App\Models\Location;
use App\Models\PaymentTerm;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequestItem;
use Illuminate\Support\Collection;
use TCPDF;
use TCPDF_FONTS;

class PurchaseOrderRepository implements PurchaseOrderRepositoryInterface
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

    public function storeUpdate(array $data, ?PurchaseOrder $purchaseOrder = null): PurchaseOrder
    {
        if (! empty($purchaseOrder)) {
            $placeDelivery = Location::where('location_name', $data['place_delivery'])->first();
            $deliveryTerm = DeliveryTerm::where('term_name', $data['delivery_term'])->first();
            $paymentTerm = PaymentTerm::where('term_name', $data['payment_term'])->first();

            if (! $placeDelivery) {
                $placeDelivery = Location::create([
                    'location_name' => $data['place_delivery'],
                ]);
            }

            if (! $deliveryTerm) {
                $deliveryTerm = DeliveryTerm::create([
                    'term_name' => $data['delivery_term'],
                ]);
            }

            if (! $paymentTerm) {
                $paymentTerm = PaymentTerm::create([
                    'term_name' => $data['payment_term'],
                ]);
            }

            $purchaseOrder->update(array_merge(
                $data,
                [
                    'place_delivery_id' => $placeDelivery->id,
                    'delivery_term_id' => $deliveryTerm->id,
                    'payment_term_id' => $paymentTerm->id,
                ]
            ));

            $this->storeUpdateItems(
                collect(isset($data['items']) && ! empty($data['items']) ? $data['items'] : []),
                $purchaseOrder,
                false
            );
        } else {
            $purchaseOrder = PurchaseOrder::create(
                array_merge(
                    $data,
                    [
                        'po_no' => $this->generateNewPoNumber(
                            $data['document_type']
                        ),
                        'status' => PurchaseOrderStatus::DRAFT,
                        'status_timestamps' => StatusTimestampsHelper::generate(
                            'draft_at', null
                        ),
                    ]
                )
            );

            $this->storeUpdateItems(
                collect(isset($data['items']) && ! empty($data['items']) ? $data['items'] : []),
                $purchaseOrder
            );
        }

        return $purchaseOrder;
    }

    private function storeUpdateItems(Collection $items, PurchaseOrder $purchaseOrder, bool $queue = true): void
    {
        if ($queue) {
            PurchaseOrderItem::where('purchase_order_id', $purchaseOrder->id)
                ->delete();

            $itemChunks = $items->chunk(50);

            foreach ($itemChunks as $itemChunk) {
                StorePoItems::dispatch($itemChunk, $purchaseOrder);
            }
        } else {
            foreach ($items as $item) {
                $poItem = PurchaseOrderItem::where('purchase_order_id', $purchaseOrder->id)
                    ->where('pr_item_id', $item['pr_item_id'])
                    ->first();

                $poItem->update([
                    'description' => $item['description'],
                ]);
            }
        }
    }

    private function generateNewPoNumber(string $documentType = 'po'): string
    {
        $month = date('m');
        $year = date('Y');
        $sequence = PurchaseOrder::whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->where('document_type', $documentType)
            ->count() + 1;

        return "{$year}-{$sequence}-{$month}";
    }

    public function print(array $pageConfig, string $poId): array
    {
        try {
            $company = Company::first();
            $po = PurchaseOrder::with([
                'supplier:id,supplier_name,address,tin_no',
                'mode_procurement:id,mode_name',
                'place_delivery:id,location_name',
                'delivery_term:id,term_name',
                'payment_term:id,term_name',
                'items' => function ($query) {
                    $query->orderBy(
                        PurchaseRequestItem::select('item_sequence')
                            ->whereColumn(
                                'purchase_order_items.pr_item_id', 'purchase_request_items.id'
                            ),
                        'asc'
                    );
                },
                'items.pr_item:id,unit_issue_id,item_sequence,quantity,description,stock_no',
                'items.pr_item.unit_issue:id,unit_name',
                'signatory_approval:id,user_id',
                'signatory_approval.user:id,firstname,middlename,lastname,allow_signature,signature',
                'signatory_approval.detail' => function ($query) {
                    $query->where('document', 'po')
                        ->where('signatory_type', '	authorized_official');
                },
                'purchase_request:id,purpose',
            ])->find($poId);

            if ($po->document_type === 'po') {
                $filename = "PO-{$po->po_no}.pdf";
                $blob = $this->generatePurchaseOrderDoc($filename, $pageConfig, $po, $company);
            } else {
                $filename = "JO-{$po->po_no}.pdf";
                $blob = $this->generateJobOrderDoc($filename, $pageConfig, $po, $company);
            }

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

    private function generatePurchaseOrderDoc(
        string $filename, array $pageConfig, PurchaseOrder $data, Company $company
    ): string {
        $purchaseRequest = $data->purchase_request;
        $supplier = $data->supplier;
        $modeProcurement = $data->mode_procurement;
        $placeDelivery = $data->place_delivery;
        $deliveryTerm = $data->delivery_term;
        $paymentTerm = $data->payment_term;
        $signatoryApproval = $data->signatory_approval?->user;
        $items = $data->items;

        $pdf = new TCPDF($pageConfig['orientation'], $pageConfig['unit'], $pageConfig['dimension']);

        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor(env('APP_NAME'));
        $pdf->SetTitle($filename);
        $pdf->SetSubject('Purchase Order');
        $pdf->SetMargins(
            $pdf->getPageWidth() * 0.04,
            $pdf->getPageHeight() * 0.05,
            $pdf->getPageWidth() * 0.04
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
                        ? $x + ($x * 0.6)
                        : $y + ($y * 0.4),
                    type: 'PNG',
                    resize: true,
                    dpi: 500,
                );
            }
        } catch (\Throwable $th) {}

        if (config('app.enable_print_bagong_pilipinas_logo')) {
            try {
                if ($company->company_logo) {
                    $imagePath = 'images/bagong-ph-logo.png';
                    $pdf->Image(
                        $imagePath,
                        $x + ($x * 2.4),
                        $y + ($y * 0.09),
                        w: $pageConfig['orientation'] === 'P'
                        ? $x + ($x * 0.6)
                        : $y + ($y * 0.4),
                        type: 'PNG',
                        resize: true,
                        dpi: 500,
                    );
                }
            } catch (\Throwable $th) {}
        }

        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->Cell(0, 0, "Province of {$company->province}", 0, 1, 'C');
        $pdf->SetFont($this->fontArialBold, 'BU', 10);
        $pdf->Cell(0, 0, 'MUNICIPAL GOVERNMENT OF '.strtoupper($company->municipality), 0, 1, 'C');
        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->setCellHeightRatio(1.6);
        $pdf->Cell(0, 0, "{$company->municipality}, {$company->province}", 0, 1, 'C');
        $pdf->setCellHeightRatio(1.25);

        $pdf->Ln();

        // $pdf->SetLineStyle(['width' => $pdf->getPageWidth() * 0.002]);

        $pdf->SetFont($this->fontArial, '', 1);
        $pdf->Cell(0, 0, '', 1, 1, 'C');

        $pdf->SetFont($this->fontArialBold, 'B', 24);
        $pdf->Cell($pageWidth * 0.58, 0, 'PURCHASE ORDER', 'LR', 0, 'C');

        $x = $pdf->GetX();

        $pdf->SetFont($this->fontArial, '', 2);
        $pdf->Cell($pageWidth * 0.096, 0, '', 'T', 0);
        $pdf->SetFont($this->fontArial, '', 0);
        $pdf->Cell(0, 0, '', 'T', 1);
        $pdf->setX($x);
        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->Cell($pageWidth * 0.096, 0, 'Number:', 0, 0, 'R', valign: 'B');
        $pdf->SetFont($this->fontArialBold, 'B', 12);
        $pdf->setTextColor(197, 90, 17);
        $pdf->Cell(0, 0, $data->po_no, 'BR', 1);
        $pdf->setTextColor();
        $pdf->setX($x);
        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->Cell($pageWidth * 0.096, 0, 'Date:', 0, 0, 'R');
        $pdf->Cell(0, 0, date_format(date_create($data->po_date), 'F j, Y'), 'BR', 1);
        $pdf->setX($x);
        $pdf->SetFont($this->fontArial, '', 1.5);
        $pdf->Cell(0, 0, '', 'RB', 1, 'C');

        $pdf->SetFont($this->fontArial, '', 10);
        $h1 = $pdf->getStringHeight($pageWidth * 0.12, 'Supplier');
        $pdf->SetFont($this->fontArialBold, 'B', 12);
        $h2 = $pdf->getStringHeight($pageWidth * 0.88, $supplier->supplier_name);
        $cellHeights = [$h1, $h2];
        $maxHeight = max($cellHeights);

        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->MultiCell(
            $pageWidth * 0.095, $maxHeight, 'Supplier', 'LT', 'L',
            maxh: $maxHeight, valign: 'B', ln: 0
        );
        $pdf->SetFont($this->fontArialBold, 'B', 12);
        $pdf->MultiCell(
            $pageWidth * 0.485, $maxHeight,
            $supplier->supplier_name,
            'TRB', 'L', ln: 0
        );

        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->MultiCell(
            0, $maxHeight, 'Mode of Procurement:', 'RB', 'C',
            maxh: $maxHeight, valign: 'B'
        );

        $pdf->SetFont($this->fontArial, '', 10);
        $h1 = $pdf->getStringHeight($pageWidth * 0.12, 'Address');
        $pdf->SetFont($this->fontArial, '', 9);
        $h2 = $pdf->getStringHeight($pageWidth * 0.88, $supplier->address);
        $cellHeights = [$h1, $h2];
        $maxHeight = max($cellHeights);
        $totalMaxHeight = $maxHeight;

        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->MultiCell(
            $pageWidth * 0.095, $maxHeight, 'Address', 'L', 'L',
            maxh: $maxHeight, valign: 'B', ln: 0
        );
        $pdf->SetFont($this->fontArial, '', 9);
        $pdf->MultiCell(
            $pageWidth * 0.485, $maxHeight,
            $supplier->address, 'RB', 'L',
            maxh: $maxHeight, valign: 'B', ln: 0
        );

        $x = $pdf->GetX();
        $y = $pdf->GetY();

        $pdf->Ln();

        $pdf->SetFont($this->fontArial, '', 10);
        $h1 = $pdf->getStringHeight($pageWidth * 0.12, 'T I N');
        $h2 = $pdf->getStringHeight($pageWidth * 0.88, $supplier->tin_no);
        $cellHeights = [$h1, $h2];
        $maxHeight = max($cellHeights);
        $totalMaxHeight += $maxHeight;

        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->MultiCell(
            $pageWidth * 0.095, $maxHeight, 'T I N', 'L', 'L',
            maxh: $maxHeight, valign: 'B', ln: 0
        );
        $pdf->MultiCell(
            $pageWidth * 0.485, $maxHeight,
            $supplier->tin_no, 'RB', 'L',
            maxh: $maxHeight, valign: 'B'
        );

        $pdf->SetFont($this->fontArial, '', 2);
        $h1 = $pdf->getStringHeight($pageWidth * 0.58, '');
        $h2 = $pdf->getStringHeight($pageWidth * 0.42, '');
        $cellHeights = [$h1, $h2];
        $maxHeight = max($cellHeights);
        $totalMaxHeight += $maxHeight;

        $pdf->Cell($pageWidth * 0.58, $maxHeight, '', 'LRB');
        // $pdf->Cell(0, $maxHeight, '', 'R');

        $pdf->SetXY($x, $y);

        $pdf->SetFont($this->fontArialBold, 'B', 10);
        $pdf->MultiCell(
            0, $totalMaxHeight,
            strtoupper($modeProcurement->mode_name),
            'RB', 'C',
            maxh: $totalMaxHeight, valign: 'M'
        );

        $pdf->SetFont($this->fontArial, '', 1);
        $pdf->Cell(0, 0, '', 'LRB', 1, 'C');

        $pdf->SetFont($this->fontArial, 'I', 10);
        $pdf->Cell($pageWidth * 0.183, 0, 'Gentlement:', 'L', 0, 'C');
        $pdf->Cell(0, 0, '', 'R', 1);

        $pdf->SetFont($this->fontArial, 'I', 5);
        $pdf->Cell(0, 0, '', 'LR', 1);

        $pdf->SetFont($this->fontArial, 'I', 10);
        $pdf->Cell(
            0, 0,
            'Please furnish this office the following articles subject '.
            'to the terms and conditions contained herein:',
            'LR', 1, 'C'
        );

        $pdf->SetFont($this->fontArial, 'I', 8);
        $pdf->Cell(0, 0, '', 'LR', 1);

        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->Cell($pageWidth * 0.183, 0, 'Place of Delivery:', 'LT', 0, 'L');
        $pdf->SetFont($this->fontArialBold, 'B', 10);
        $pdf->Cell($pageWidth * 0.397, 0, ! empty($placeDelivery) ? $placeDelivery->location_name : '', 'TB', 0, 'L');
        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->Cell($pageWidth * 0.185, 0, 'Delivery Term:', 'T', 0, 'L');
        $pdf->SetFont($this->fontArialBold, 'B', 10);
        $pdf->Cell(0, 0, ! empty($deliveryTerm) ? $deliveryTerm->term_name : '', 'TRB', 1, 'L');

        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->Cell($pageWidth * 0.183, 0, 'Date of Delivery:', 'L', 0, 'L');
        $pdf->SetFont($this->fontArialBold, 'B', 10);
        $pdf->Cell(
            $pageWidth * 0.397, 0,
            date_format(date_create($data->delivery_date), 'F j, Y'),
            'B', 0, 'L'
        );
        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->Cell($pageWidth * 0.185, 0, 'Payment Term:', 0, 0, 'L');
        $pdf->SetFont($this->fontArialBold, 'B', 10);
        $pdf->Cell(0, 0, ! empty($paymentTerm) ? $paymentTerm->term_name : '', 'RB', 1, 'L');

        $pdf->SetFont($this->fontArial, 'I', 5);
        $pdf->Cell(0, 0, '', 'LR', 1);

        $htmlTable = '
            <table border="1" cellpadding="2"><thead><tr>
                <th
                    width="8.89%"
                    align="center"
                >Stock No.</th>
                <th
                    width="9.43%"
                    align="center"
                >Unit</th>
                <th
                    width="45.53%"
                    align="center"
                >Description</th>
                <th
                    width="9.88%"
                    align="center"
                >Qty</th>
                <th
                    width="11.15%"
                    align="center"
                >Unit Cost</th>
                <th
                    width="15.12%"
                    align="center"
                >Amount</th>
            </tr></thead></table>
        ';

        $pdf->SetFont($this->fontArialBold, 'B', 9);
        $pdf->writeHTML($htmlTable, ln: false);
        $pdf->Ln(0);

        $htmlTable = '<table border="1" cellpadding="2"><tbody>';

        foreach ($items ?? [] as $item) {
            $prItem = $item->pr_item;
            $description = trim(str_replace("\r", '<br />', $item->description));
            $description = str_replace("\n", '<br />', $description);

            $htmlTable .= '
                <tr>
                    <td
                        width="8.89%"
                        align="center"
                    >'.$prItem->stock_no.'</td>
                    <td
                        width="9.43%"
                        align="center"
                    >'.$prItem->unit_issue->unit_name.'</td>
                    <td
                        width="45.53%"
                        align="left"
                    >'.$description.'</td>
                    <td
                        width="9.88%"
                        align="center"
                    >'.$prItem->quantity.'</td>
                    <td
                        width="11.15%"
                        align="right"
                    >'.number_format($item->unit_cost, 2).'</td>
                    <td
                        width="15.12%"
                        align="right"
                    >'.number_format($item->total_cost, 2).'</td>
                </tr>
            ';
        }

        if (count($items) < 10) {
            for ($counter = 0; $counter <= (10 - count($items)); $counter++) {
                $htmlTable .= '
                    <tr>
                        <td
                            width="8.89%"
                            align="center"
                        ></td>
                        <td
                            width="9.43%"
                            align="center"
                        ></td>
                        <td
                            width="45.53%"
                            align="left"
                        ></td>
                        <td
                            width="9.88%"
                            align="center"
                        ></td>
                        <td
                            width="11.15%"
                            align="right"
                        ></td>
                        <td
                            width="15.12%"
                            align="right"
                        ></td>
                    </tr>
                ';
            }
        }

        $htmlTable .= '</tbody></table>';
        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->writeHTML($htmlTable, ln: false);
        $pdf->Ln(0);

        $purpose = trim(str_replace("\r", '<br />', $purchaseRequest->purpose));
        $purpose = str_replace("\n", '<br />', $purpose);

        $htmlTable = '
            <table border="1" cellpadding="2"><tbody><tr>
                <td
                    width="8.89%"
                    align="center"
                ></td>
                <td
                    width="75.99%"
                    align="center"
                >'.$purpose.'</td>
                <td
                    width="15.12%"
                    align="center"
                ></td>
            </tr></tbody></table>
        ';

        $pdf->SetFont($this->fontArialBold, 'B', 12);
        $pdf->writeHTML($htmlTable, ln: false);
        $pdf->Ln(0);

        $htmlTable = '
            <table border="1" cellpadding="2"><tbody><tr>
                <td
                    width="18.32%"
                    align="center"
                >Total (Amount in words)</td>
                <td
                    width="66.56%"
                    align="center"
                    style="font-weight: bold; font-size: 12px;"
                >'.strtoupper($data->total_amount_words).'</td>
                <td
                    width="15.12%"
                    align="right"
                    style="font-weight: bold; font-size: 14px;"
                >'.number_format($data->total_amount, 2).'</td>
            </tr></tbody></table>
        ';

        $pdf->SetFont('helvetica', '', 9);
        $pdf->writeHTML($htmlTable, ln: false);
        $pdf->Ln(0);

        $pdf->SetFont($this->fontArial, 'I', 10);
        $pdf->Cell(
            0, 0,
            'In case of failure to make the full delivery within the '.
            'time specified above , a penalty of one tenth(1/10) of one',
            'LR', 1, 'C'
        );
        $pdf->Cell(
            0, 0,
            ' percent for every day of delay shall be imposed.',
            'LR', 1, 'L'
        );

        $pdf->Cell(0, 0, '', 'LR', 1);

        $pdf->SetFont($this->fontArialBold, 'B', 11);
        $pdf->Cell($pageWidth * 0.276, 0, 'Conforme:', 'L', 0, 'C');
        $pdf->Cell($pageWidth * 0.276, 0, '', 0, 0, 'C');
        $pdf->Cell(0, 0, 'Very truly yours,', 'R', 1, 'L');

        $pdf->Cell(0, 0, '', 'LR', 1);

        $pdf->SetFont($this->fontArialBold, 'B', 12);
        $pdf->Cell($pageWidth * 0.089, 0, '', 'L', 0, 'C');
        $pdf->Cell($pageWidth * 0.464, 0, '', 'B', 0, 'C');
        $pdf->Cell(0, 0, strtoupper(! empty($signatoryApproval) ? $signatoryApproval->fullname : ''), 'R', 1, 'C');

        $pdf->SetFont($this->fontArial, '', 11);
        $pdf->Cell($pageWidth * 0.089, 0, '', 'L', 0, 'C');
        $pdf->Cell($pageWidth * 0.464, 0, '(Signature Over Printed Name)', 0, 0, 'C');
        $pdf->Cell(0, 0, 'Municipal Mayor', 'R', 1, 'C');

        $pdf->SetFont($this->fontArial, '', 11);
        $pdf->Cell($pageWidth * 0.089, 0, '', 'L', 0, 'C');
        $pdf->Cell($pageWidth * 0.464, 0, '', 'B', 0, 'C');
        $pdf->Cell(0, 0, '(Authorized Official)', 'R', 1, 'C');

        $pdf->SetFont($this->fontArial, '', 11);
        $pdf->Cell($pageWidth * 0.089, 0, '', 'L', 0, 'C');
        $pdf->Cell($pageWidth * 0.464, 0, '(Date)', 0, 0, 'C');
        $pdf->Cell(0, 0, '', 'R', 1, 'C');

        $pdf->SetFont($this->fontArial, '', 5);
        $pdf->Cell(0, 0, '', 'LRB', 'B');

        $pdfBlob = $pdf->Output($filename, 'S');
        $pdfBase64 = base64_encode($pdfBlob);

        return $pdfBase64;
    }

    private function generateJobOrderDoc(
        string $filename, array $pageConfig, PurchaseOrder $data, Company $company
    ): string {
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
        } catch (\Throwable $th) {
        }

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
        $pdf->setCellHeightRatio(1.25);
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
                    >'.$item->quantity.'</td>
                    <td
                        width="8%"
                        align="center"
                    >'.$item->unit_issue->unit_name.'</td>
                    <td
                        width="47%"
                        align="left"
                    >'.$description.'</td>
                    <td
                        width="8%"
                        align="center"
                    >'.$item->stock_no.'</td>
                    <td
                        width="13%"
                        align="right"
                    >'.number_format($item->estimated_unit_cost, 2).'</td>
                    <td
                        width="13%"
                        align="right"
                    >'.number_format($item->estimated_cost, 2).'</td>
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
        $purpose = $purpose.(
            isset($data->funding_source->title) && ! empty($data->funding_source->title)
                ? " (Charged to {$data->funding_source->title})" : ''
        );
        $html = '
            <div style="border: 1px solid black;">
                <table cellpadding="2">
                    <tr>
                        <td style="color: red; font-size: 9px; font-style: italic;" width="9%">Purpose:</td>
                        <td width="91%" style="font-weight: bold; text-align: justify; font-size: 10px;">'.$purpose.'</td>
                    </tr>
                </table>
            </div>
        ';
        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->writeHTML($html, ln: false);
        $pdf->Ln(0);

        $pdf->SetFont($this->fontArialBold, 'B', 10);
        $pdf->setCellHeightRatio(1.6);
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
        $signatureWidth = $pageConfig['orientation'] === 'P'
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
        } catch (\Throwable $th) {
        }

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
        } catch (\Throwable $th) {
        }

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
        } catch (\Throwable $th) {
        }

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
