<?php

namespace App\Repositories;

use App\Enums\DocumentPrintType;
use App\Enums\InventoryIssuanceStatus;
use App\Helpers\FileHelper;
use App\Helpers\StatusTimestampsHelper;
use App\Helpers\TextHelper;
use App\Interfaces\InventoryIssuanceRepositoryInterface;
use App\Models\Company;
use App\Models\InventoryIssuance;
use App\Models\InventoryIssuanceItem;
use App\Models\InventorySupply;
use Illuminate\Support\Collection;
use TCPDF;
use TCPDF_FONTS;

class InventoryIssuanceRepository implements InventoryIssuanceRepositoryInterface
{
    protected string $appUrl;

    protected string $fontArial;

    protected string $fontArialBold;

    protected string $fontArialBlack;

    protected string $fontArialItalic;

    protected string $fontArialBoldItalic;

    protected string $fontArialNarrow;

    protected string $fontArialNarrowBold;

    public function __construct()
    {
        $this->appUrl = env('APP_URL') ?? 'http://localhost';
        $this->fontArial = TCPDF_FONTS::addTTFfont('fonts/arial.ttf', 'TrueTypeUnicode', '', 96);
        $this->fontArialBold = TCPDF_FONTS::addTTFfont('fonts/arialbd.ttf', 'TrueTypeUnicode', '', 96);
        $this->fontArialBlack = TCPDF_FONTS::addTTFfont('fonts/arialblk.ttf', 'TrueTypeUnicode', '', 96);
        $this->fontArialItalic = TCPDF_FONTS::addTTFfont('fonts/ariali.ttf', 'TrueTypeUnicode', '', 96);
        $this->fontArialBoldItalic = TCPDF_FONTS::addTTFfont('fonts/arialbi.ttf', 'TrueTypeUnicode', '', 96);
        $this->fontArialNarrow = TCPDF_FONTS::addTTFfont('fonts/arialn.ttf', 'TrueTypeUnicode', '', 96);
        $this->fontArialNarrowBold = TCPDF_FONTS::addTTFfont('fonts/arialnb.ttf', 'TrueTypeUnicode', '', 96);
    }

    public function storeUpdate(array $data, ?InventoryIssuance $inventoryIssuance): InventoryIssuance
    {
        if (! empty($inventoryIssuance)) {
            $inventoryIssuance->update($data);
        } else {
            $inventoryIssuance = InventoryIssuance::create(
                array_merge(
                    $data,
                    [
                        'inventory_no' => $this->generateNewInventoryNumber(
                            $data['document_type']
                        ),
                        'status' => InventoryIssuanceStatus::DRAFT,
                        'status_timestamps' => StatusTimestampsHelper::generate(
                            'draft_at', null
                        ),
                    ]
                )
            );
        }

        $this->storeUpdateItems(
            collect(isset($data['items']) && ! empty($data['items']) ? $data['items'] : []),
            $inventoryIssuance
        );

        return $inventoryIssuance;
    }

    private function storeUpdateItems(Collection $items, InventoryIssuance $inventoryIssuance): void
    {
        InventoryIssuanceItem::where('inventory_issuance_id', $inventoryIssuance->id)->delete();

        foreach ($items ?? [] as $key => $item) {
            $quantity = intval($item['quantity']);
            $unitCost = floatval($item['unit_cost']);
            $totalCost = round($quantity * $unitCost, 2);

            InventoryIssuanceItem::create([
                'inventory_issuance_id' => $inventoryIssuance->id,
                'inventory_supply_id' => $item['inventory_supply_id'],
                'stock_no' => isset($item['stock_no']) ? (int) $item['stock_no'] : $key + 1,
                'description' => $item['description'],
                'inventory_item_no' => isset($item['inventory_item_no'])
                                            ? ($item['inventory_item_no'] ?? null)
                                            : null,
                'property_no' => isset($item['property_no'])
                                            ? ($item['property_no'] ?? null)
                                            : null,
                'quantity' => $quantity ?? 0,
                'estimated_useful_life' => isset($item['estimated_useful_life'])
                                            ? ($item['estimated_useful_life'] ?? null)
                                            : null,
                'acquired_date' => isset($item['acquired_date'])
                                            ? ($item['acquired_date'] ?? null)
                                            : null,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
            ]);
        }
    }

    public function generateNewInventoryNumber(string $documentType): string
    {
        $month = date('m');
        $year = date('Y');
        $sequence = InventoryIssuance::whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            // ->where('document_type', $documentType)
            ->count() + 1;

        return "{$year}-{$sequence}-{$month}";
    }

    public function print(array $pageConfig, string $invId, DocumentPrintType $documentType): array
    {
        try {            
            $company = Company::first();
            $inv = InventoryIssuance::with([
                'requestor',
                'signatory_approval:id,user_id',
                'signatory_approval.user:id,firstname,middlename,lastname,allow_signature,signature',
                'signatory_approval.detail' => function ($query) use ($documentType) {
                    $query->where('document', $documentType)
                        ->where('signatory_type', 'approved_by');
                },
                'signatory_issuer:id,user_id',
                'signatory_issuer.user:id,firstname,middlename,lastname,allow_signature,signature',
                'signatory_issuer.detail' => function ($query) use ($documentType) {
                    $query->where('document', $documentType)
                        ->where('signatory_type', $documentType === DocumentPrintType::RIS ? 'issued_by' : 'received_from');
                },
                'recipient',

                'items' => function ($query) {
                    $query->orderBy(
                        InventorySupply::select('item_sequence')
                            ->whereColumn(
                                'inventory_issuance_items.inventory_supply_id', 'inventory_supplies.id'
                            ),
                        'asc'
                    );
                },
                'items.supply',
                'items.supply.unit_issue:id,unit_name',

                'responsibility_center',
                'purchase_order',
                'purchase_order.purchase_request',
                'purchase_order.supplier',
                'purchase_order.obligation_request',
                'purchase_order.disbursement_voucher',
            ])->find($invId);

            if (empty($inv)) {
                return [
                    'success' => false,
                    'message' => 'Issuance not found.',
                    'blob' => '',
                    'filename' => '',
                ];
            }

            switch ($documentType) {
                case DocumentPrintType::RIS:
                    $filename = "RIS-{$inv->inventory_no}.pdf";
                    $blob = $this->generateRequisitionIssueSlipDoc($filename, $pageConfig, $inv, $company);
                    break;

                case DocumentPrintType::ARE:
                    $filename = "ARE-{$inv->inventory_no}.pdf";
                    $blob = $this->generateAcknowledgmentReceiptEquipmentDoc($filename, $pageConfig, $inv, $company);
                    break;

                case DocumentPrintType::ICS:
                    $filename = "ICS-{$inv->inventory_no}.pdf";
                    $blob = $this->generateInventoryCustodianSlipDoc($filename, $pageConfig, $inv, $company);
                    break;
                
                default:
                    return [
                        'success' => false,
                        'message' => 'Invalid issuance type.',
                        'blob' => '',
                        'filename' => '',
                    ];
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

    private function generateRequisitionIssueSlipDoc(
        string $filename, array $pageConfig, InventoryIssuance $data, Company $company
    ): string {
        $municipality = strtoupper($company->municipality) ?? '';
        $companyType = strtoupper($company->company_type) ?? '';
        $division = strtoupper(
            $data->purchase_order?->purchase_request?->department?->department_name
        ) ?? '';
        $office = strtoupper(
            $data->purchase_order?->purchase_request?->section?->section_name
        ) ?? '';
        $responsibilityCenter = $data->responsibility_center->code
            ?? $data->purchase_order?->obligation_request?->responsibility_center?->code
            ?? $data->purchase_order?->disbursement_voucher?->responsibility_center?->code
            ?? '';
        $poNo = $data?->purchase_order?->po_no ?? '';
        $inventoryNumber = $data->inventory_no;
        $inventoryDate = $data->inventory_date
            ? date_format(date_create($data->inventory_date), 'F j, Y') 
            : '';
        $saiNo = $data->sai_no
            ?? $data->purchase_order?->purchase_request?->sai_no
            ?? '';
        $saiDate = $data->sai_date
            ? date_format(date_create($data->sai_date), 'F j, Y')
            : ($data->purchase_order?->purchase_request?->sai_date
                ? date_format(date_create($data->sai_date), 'F j, Y')
                : '');
        $items = !empty($data->items) ? $data->items : [];
        $supplier = strtoupper($data?->purchase_order?->supplier?->supplier_name ?? '');
        $purpose = $data?->purchase_order?->purchase_request?->purpose ?? '';
        $purpose = trim(str_replace("\r", '<br />', $purpose));
        $purpose = str_replace("\n", '<br />', $purpose);
        $requestedByName = $data->requestor?->fullname ?? '';
        $requestedByPosition = $data->requestor?->position?->position_name ?? '';
        $requestedBySignedDate = $data->requested_date 
            ? date_format(date_create($data->requested_date), 'M j, Y') 
            : '';
        $approvedByName = $data->signatory_approval?->user?->fullname ?? '';
        $approvedByPosition = $data->signatory_approval?->detail?->position ?? '-';
        $approvedBySignedDate = $data->approved_date 
            ? date_format(date_create($data->approved_date), 'M j, Y') 
            : '';
        $issuedByName = $data->signatory_issuer?->user?->fullname ?? '';
        $issuedByPosition = $data->signatory_issuer?->detail?->position ?? '-';
        $issuedBySignedDate = $data->issued_date
            ? date_format(date_create($data->issued_date), 'M j, Y') 
            : '';
        $receivedByName = $data->recipient?->fullname ?? '';
        $receivedByPosition = $data->recipient?->position?->position_name ?? '';
        $receivedBySignedDate = $data->received_date 
            ? date_format(date_create($data->received_date), 'M j, Y') 
            : '';

        $pdf = new TCPDF($pageConfig['orientation'], $pageConfig['unit'], $pageConfig['dimension']);

        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor(env('APP_NAME'));
        $pdf->SetTitle($filename);
        $pdf->SetSubject('Requisition and Issue Slip');
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
                    $x + ($x * 0.4),
                    $y + ($y * 0.5),
                    w: $pageConfig['orientation'] === 'P'
                        ? $x + ($x * 0.8)
                        : $y + ($y * 0.6),
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
                        $x + ($x * 2.5),
                        $y + ($y * 0.5),
                        w: $pageConfig['orientation'] === 'P'
                            ? $x + ($x * 0.8)
                            : $y + ($y * 0.6),
                        type: 'PNG',
                        resize: true,
                        dpi: 500,
                    );
                }
            } catch (\Throwable $th) {}
        }

        $pdf->SetLineStyle(['width' => 0.5, 'color' => [51, 51, 255]]);
        $pdf->SetFont($this->fontArial, '', 10);

        $pdf->Cell($pageWidth * 0.74, 0, '');
        $pdf->Cell($pageWidth * 0.103, 0, 'Annex 33', 0, 0, 'C');
        $pdf->Cell(0, 0, '', 0, 1);

        $pdf->SetFont($this->fontArial, '', 5);
        $pdf->Cell(0, 0, '', 'LTR', 1);

        $pdf->setCellHeightRatio(1.6);
        $pdf->SetFont($this->fontArialBlack, 'B', 18);
        $pdf->Cell(0, 0, 'REQUISITION AND ISSUE SLIP', 'LR', 1, 'C');

        $pdf->setCellHeightRatio(1.25);
        $pdf->SetFont($this->fontArialBold, 'B', 14);
        $pdf->Cell(0, 0, $municipality, 'LR', 1, 'C');

        $pdf->SetFont($this->fontArialItalic, 'I', 11);
        $pdf->Cell(0, 0, $companyType, 'LR', 1, 'C');

        $pdf->SetFont($this->fontArial, '', 5);
        $pdf->Cell(0, 0, '', 'LR', 1);

        $htmlTable = '
            <table 
                style="border-top: 1px solid #000; border-bottom: 1px solid #000; border-left: 1.5px solid #3333FF; border-right: 1.5px solid #3333FF;"
                cellpadding="-6"
                align="center"
            ><tbody>
                <tr>
                    <td
                        style="border-right: 1px solid #000;"
                        width="25.3%"
                    >
                        <div>
                            <table cellpadding="2" align="left" width="96%">
                                <tr>
                                    <td
                                        width="31%"
                                    >Division:</td>
                                    <td
                                        style="text-decoration: underline;"
                                        width="69%"
                                    >'. $division .'</td>
                                </tr>
                                <tr>
                                    <td
                                        width="31%"
                                    >Office:</td>
                                    <td
                                        style="text-decoration: underline;"
                                        width="69%"
                                    >'. $office .'</td>
                                </tr>
                            </table>
                        </div>
                    </td>
                    <td
                        style="border-right: 1px solid #000;"
                        width="32%"
                    >
                        <div>
                            <table cellpadding="2" align="left" width="96.5%">
                                <tr>
                                    <td
                                        width="58%"
                                    >Responsibility Center:</td>
                                    <td
                                        width="42%"
                                    >'. $responsibilityCenter .'</td>
                                </tr>
                                <tr>
                                    <td
                                        width="58%"
                                    >PO No.:</td>
                                    <td
                                        style="font-weight: bold; color: #FF0066; font-size: 12px;"
                                        width="42%"
                                    >'. $poNo .'</td>
                                </tr>
                            </table>
                        </div>
                    </td>
                    <td
                        width="42.7%"
                    >
                        <div>
                            <table cellpadding="2" align="left" width="97.3%">
                                <tr>
                                    <td
                                        width="18%"
                                    >RIS No.:</td>
                                    <td
                                        style="text-decoration: underline;"
                                        width="32%"
                                    >'. $inventoryNumber .'</td>
                                    <td
                                        width="12%"
                                    >Date:</td>
                                    <td
                                        style="text-decoration: underline;"
                                        width="38%"
                                    >'. $inventoryDate .'</td>
                                </tr>
                                <tr>
                                    <td
                                        width="18%"
                                    >SAI No.:</td>
                                    <td
                                        style="text-decoration: underline;"
                                        width="32%"
                                    >'. $saiNo .'</td>
                                    <td
                                        width="12%"
                                    >Date:</td>
                                    <td
                                        style="text-decoration: underline;"
                                        width="38%"
                                    >'. $saiDate .'</td>
                                </tr>
                            </table>
                        </div>
                    </td>
                </tr>
            </tbody></table>
        ';

        $pdf->setCellHeightRatio(1.25);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->writeHTML($htmlTable, ln: false);
        $pdf->Ln(0);

        $htmlTable = '
            <table 
                style="border-top: 1px solid #000; border-bottom: 1px solid #000; border-left: 1.5px solid #3333FF; border-right: 1.5px solid #3333FF;"
                cellpadding="2"
                align="center"
            ><tbody><tr>
                <td
                    width="57.3%"
                >
                    R e q u i s i t i o  n
                </td>
                <td
                    width="42.7%"
                >
                    I s s u a n  c e
                </td>
            </tr></tbody></table>
        ';

        $pdf->setCellHeightRatio(1.6);
        $pdf->SetFont($this->fontArialBoldItalic, 'BI', 11);
        $pdf->writeHTML($htmlTable, ln: false);
        $pdf->Ln(0);

        $htmlTable = '
            <table 
                style="border-top: 1px solid #000; border-bottom: 1px solid #000; border-left: 1.5px solid #3333FF; border-right: 1.5px solid #3333FF;"
                cellpadding="2"
                align="center"
            ><tbody><tr>
                <td
                    style="border-right: 1px solid #000;"
                    width="6.6%"
                    align="center"
                >
                    Stock No.
                </td>
                <td
                    style="border-right: 1px solid #000;"
                    width="5.7%"
                    align="center"
                >
                    Unit
                </td>
                <td
                    style="border-right: 1px solid #000;"
                    width="45%"
                    align="center"
                >
                    Description
                </td>
                <td
                    style="border-right: 1px solid #000;"
                    width="16.7%"
                    align="center"
                >
                    Quantity
                </td>
                <td
                    style="border-right: 1px solid #000; font-size: 10px;"
                    width="10.3%"
                    align="center"
                >
                    Unit Price
                </td>
                <td
                    width="15.7%"
                >
                    Total Cost
                </td>
             </tr></tbody></table>
        ';

        $pdf->setCellHeightRatio(1.25);
        $pdf->SetFont('Times', 'B', 11);
        $pdf->writeHTML($htmlTable, ln: false);
        $pdf->Ln(0);

        $htmlTable = '
            <table 
                style="border-top: 1px solid #3333FF; border-left: 1.5px solid #3333FF; border-right: 1.5px solid #3333FF;"
                cellpadding="2"
            ><tbody>
        ';

        foreach ($items as $item) {
            $stockNo = $item->stock_no ?? 0;
            $unit = $item?->supply?->unit_issue?->unit_name ?? '';
            $description = trim(str_replace("\r", '<br />', $item->description));
            $description = str_replace("\n", '<br />', $description);
            $quantity = $item->quantity ?? 0;
            $unitPrice = number_format($item->unit_cost, 2);
            $totalCost = number_format($item->total_cost, 2);

            $htmlTable .= '
                <tr>
                    <td
                        style="border-right: 1px solid #000;"
                        width="6.6%"
                        align="center"
                    >'. $stockNo .'</td>
                    <td
                        style="border-right: 1px solid #000;"
                        width="5.7%"
                        align="center"
                    >'. $unit .'</td>
                    <td
                        style="border-right: 1px solid #000;"
                        width="45%"
                    >'. $description .'</td>
                    <td
                        style="border-right: 1px solid #000;"
                        width="16.7%"
                        align="center"
                    >'. $quantity .'</td>
                    <td
                        style="border-right: 1px solid #000;"
                        width="10.3%"
                        align="center"
                    >'. $unitPrice .'</td>
                    <td
                        width="15.7%"
                        align="right"
                    >'. $totalCost .'</td>
                </tr>
            ';
        }

        $htmlTable .= '</tbody></table>';
        $pdf->setCellHeightRatio(1.25);
        $pdf->SetFont($this->fontArial, '', 12);
        $pdf->writeHTML($htmlTable, ln: false);
        $pdf->Ln(0);

        $htmlTable = '
            <table 
                style="border-left: 1.5px solid #3333FF; border-right: 1.5px solid #3333FF; border-bottom: 1.5px solid #000;"
                cellpadding="2"
            ><tbody>
                <tr>
                    <td
                        style="border-right: 1px solid #000;"
                        width="6.6%"
                        align="center"
                    ></td>
                    <td
                        style="border-right: 1px solid #000;"
                        width="5.7%"
                        align="center"
                    ></td>
                    <td
                        style="border-right: 1px solid #000;"
                        width="45%"
                    ></td>
                    <td
                        style="border-right: 1px solid #000;"
                        width="16.7%"
                        align="center"
                    ></td>
                    <td
                        style="border-right: 1px solid #000;"
                        width="10.3%"
                        align="center"
                    ></td>
                    <td
                        width="15.7%"
                        align="right"
                    ></td>
                </tr>
                <tr>
                    <td
                        style="border-right: 1px solid #000;"
                        width="6.6%"
                        align="center"
                    ></td>
                    <td
                        style="border-right: 1px solid #000;"
                        width="5.7%"
                        align="center"
                    ></td>
                    <td
                        style="border-right: 1px solid #000; color: #FF0066;"
                        width="45%"
                        align="center"
                    >SUPPLIER: '. $supplier .'</td>
                    <td
                        style="border-right: 1px solid #000;"
                        width="16.7%"
                        align="center"
                    ></td>
                    <td
                        style="border-right: 1px solid #000;"
                        width="10.3%"
                        align="center"
                    ></td>
                    <td
                        width="15.7%"
                        align="right"
                    ></td>
                </tr>
                <tr>
                    <td
                        style="border-right: 1px solid #000;"
                        width="6.6%"
                        align="center"
                    ></td>
                    <td
                        style="border-right: 1px solid #000;"
                        width="5.7%"
                        align="center"
                    ></td>
                    <td
                        style="border-right: 1px solid #000;"
                        width="45%"
                    ></td>
                    <td
                        style="border-right: 1px solid #000;"
                        width="16.7%"
                        align="center"
                    ></td>
                    <td
                        style="border-right: 1px solid #000;"
                        width="10.3%"
                        align="center"
                    ></td>
                    <td
                        width="15.7%"
                        align="right"
                    ></td>
                </tr>
            </tbody></table>
        ';

        $pdf->setCellHeightRatio(1.25);
        $pdf->SetFont($this->fontArialNarrowBold, 'B', 12);
        $pdf->writeHTML($htmlTable, ln: false);
        $pdf->Ln(0);

        $pdf->SetFont($this->fontArialBoldItalic, 'B', 12);
        $basePurposeHeight = $pdf->getStringHeight($pageWidth * 0.877, 'Purpose');
        $totalPurposeHeight = $pdf->getStringHeight($pageWidth * 0.877, $purpose);
        $linesPurpose = TextHelper::splitTextToLines($pdf, $purpose, $pageWidth * 0.877);

        $pdf->SetFont($this->fontArialBold, 'B', 10);
        $pdf->MultiCell(
            $pageWidth * 0.123, 
            $totalPurposeHeight, 
            'Purpose:',
            'L',
            'L',
            ln: 0,
            maxh: $totalPurposeHeight,
            valign: 'T'
        );
        $x = $pdf->GetX();

        $pdf->SetFont($this->fontArialBoldItalic, 'B', 12);

        foreach ($linesPurpose as $line) {
            $pdf->SetLineStyle(['width' => 0.5, 'color' => [51, 51, 255]]);
            $pdf->MultiCell(
                $pageWidth * 0.877, 
                $basePurposeHeight, 
                $line, 
                'R',
                'L',
                x: $x
            );

            $yAfter = $pdf->GetY();
            $pdf->SetDrawColor(0, 0, 0);
            $pdf->Line($x, $yAfter, $x + $pageWidth * 0.877, $yAfter);
        }

        $pdf->SetLineStyle(['width' => 0.5, 'color' => [51, 51, 255]]);
        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->Cell(0, 0, '', 'LR', ln: 1);

        $htmlTable = '
            <table 
                style="border-top: 1.5px solid #000; border-left: 1.5px solid #3333FF; border-right: 1.5px solid #3333FF; "
                cellpadding="2"
            ><tbody>
                <tr>
                    <td
                        style="border-right: 1px solid #000;"
                        width="13%"
                    ></td>
                    <td
                        style="border-right: 1px solid #000;"
                        width="21.75%"
                    >Requested by:</td>
                    <td
                        style="border-right: 1px solid #000;"
                        width="21.75%"
                    >Approved by:</td>
                    <td
                        style="border-right: 1px solid #000; font-weight: bold;"
                        width="21.75%"
                    >Issued by:</td>
                    <td
                        width="21.75%"
                    >Received by:</td>
                </tr>
            </tbody></table>
        ';

        $pdf->setCellHeightRatio(1.6);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->writeHTML($htmlTable, ln: false);
        $pdf->Ln(0);

        $htmlTable = '
            <table 
                style="border-top: 1.5px solid #000; border-left: 1.5px solid #3333FF; border-right: 1.5px solid #3333FF; "
                cellpadding="2"
            ><tbody>
                <tr>
                    <td
                        style="border-right: 1px solid #000;"
                        width="13%"
                    >Signature:</td>
                    <td
                        style="border-right: 1px solid #000;"
                        width="21.75%"
                    ></td>
                    <td
                        style="border-right: 1px solid #000;"
                        width="21.75%"
                    ></td>
                    <td
                        style="border-right: 1px solid #000; font-weight: bold;"
                        width="21.75%"
                    ></td>
                    <td
                        width="21.75%"
                    ></td>
                </tr>
            </tbody></table>
        ';

        $pdf->setCellHeightRatio(2);
        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->writeHTML($htmlTable, ln: false);
        $pdf->Ln(0);

        $htmlTable = '
            <table 
                style="border-top: 1.5px solid #000; border-bottom: 1.5px solid #3333FF; border-left: 1.5px solid #3333FF; border-right: 1.5px solid #3333FF; "
                cellpadding="2"
            ><tbody>
                <tr>
                    <td
                        style="border-right: 1px solid #000;"
                        width="13%"
                    >Printed Name:</td>
                    <td
                        style="border-right: 1px solid #000; font-weight: bold; font-size: 12px;"
                        width="21.75%"
                        align="center"
                    >'. $requestedByName .'</td>
                    <td
                        style="border-right: 1px solid #000; font-weight: bold; font-size: 12px;"
                        width="21.75%"
                        align="center"
                    >'. $approvedByName .'</td>
                    <td
                        style="border-right: 1px solid #000; font-size: 12px;"
                        width="21.75%"
                        align="center"
                    >'. $issuedByName .'</td>
                    <td
                        style="font-weight: bold; font-size: 12px;"
                        width="21.75%"
                        align="center"
                    >'. $receivedByName .'</td>
                </tr>
                <tr>
                    <td
                        style="border-top: 1px solid #000; border-right: 1px solid #000;"
                        width="13%"
                    >Designation:</td>
                    <td
                        style="border-top: 1px solid #000; border-right: 1px solid #000; font-size: 12px;"
                        width="21.75%"
                        align="center"
                    >'. $requestedByPosition .'</td>
                    <td
                        style="border-top: 1px solid #000; border-right: 1px solid #000; font-size: 12px;"
                        width="21.75%"
                        align="center"
                    >'. $approvedByPosition .'</td>
                    <td
                        style="border-top: 1px solid #000; border-right: 1px solid #000; font-size: 12px;"
                        width="21.75%"
                        align="center"
                    >'. $issuedByPosition .'</td>
                    <td
                        style="border-top: 1px solid #000; font-size: 12px;"
                        width="21.75%"
                        align="center"
                    >'. $receivedByPosition .'</td>
                </tr>
                <tr>
                    <td
                        style="border-top: 1px solid #000; border-right: 1px solid #000;"
                        width="13%"
                    >D a t e:</td>
                    <td
                        style="border-top: 1px solid #000; border-right: 1px solid #000;"
                        width="21.75%"
                        align="center"
                    >'. $requestedBySignedDate .'</td>
                    <td
                        style="border-top: 1px solid #000; border-right: 1px solid #000;"
                        width="21.75%"
                        align="center"
                    >'. $approvedBySignedDate .'</td>
                    <td
                        style="border-top: 1px solid #000; border-right: 1px solid #000;"
                        width="21.75%"
                        align="center"
                    >'. $issuedBySignedDate .'</td>
                    <td
                        style="border-top: 1px solid #000;"
                        width="21.75%"
                        align="center"
                    >'. $receivedBySignedDate .'</td>
                </tr>
            </tbody></table>
        ';

        $pdf->setCellHeightRatio(1.25);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->writeHTML($htmlTable, ln: false);
        $pdf->Ln(0);
       
        $pdfBlob = $pdf->Output($filename, 'S');
        $pdfBase64 = base64_encode($pdfBlob);

        return $pdfBase64;
    }

    private function generateAcknowledgmentReceiptEquipmentDoc(
        string $filename, array $pageConfig, InventoryIssuance $data, Company $company
    ): string {
        $municipality = strtoupper($company->municipality) ?? '';
        $province = strtoupper($company->province) ?? '';
        $companyType = strtoupper($company->company_type) ?? '';
        $inventoryNumber = $data->inventory_no;
        $inventoryDate = $data->inventory_date
            ? date_format(date_create($data->inventory_date), 'F j, Y') 
            : '';
        $items = !empty($data->items) ? $data->items : [];
        $poNo = $data?->purchase_order?->po_no ?? '';
        $purpose = $data?->purchase_order?->purchase_request?->purpose ?? '';
        $purpose = trim(str_replace("\r", '<br />', $purpose));
        $purpose = str_replace("\n", '<br />', $purpose);
        $purpose = $purpose . "
            <br /><span style=". '"text-align: right;"' .">under P.O. # {$poNo}</span>
        ";
        $receivedByName = $data->recipient?->fullname ?? '';
        $receivedByPosition = $data->recipient?->position?->position_name ?? '';
        $receivedBySignedDate = $data->received_date 
            ? date_format(date_create($data->received_date), 'M j, Y') 
            : '';
        $receivedFromName = $data->signatory_issuer?->user?->fullname ?? '';
        $receivedFromPosition = $data->signatory_issuer?->detail?->position ?? '-';
        $receivedFromSignedDate = $data->issued_date 
            ? date_format(date_create($data->issued_date), 'M j, Y') 
            : '';

        $pdf = new TCPDF($pageConfig['orientation'], $pageConfig['unit'], $pageConfig['dimension']);

        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor(env('APP_NAME'));
        $pdf->SetTitle($filename);
        $pdf->SetSubject('Acknowledgment Receipt for Equipment');
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
                    $y + ($y * 0.2),
                    w: $pageConfig['orientation'] === 'P'
                        ? $x - ($x * 0.1)
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
                        $x + ($x * 1.4),
                        $y + ($y * 0.2),
                        w: $pageConfig['orientation'] === 'P'
                            ? $x - ($x * 0.1)
                            : $y + ($y * 0.4),
                        type: 'PNG',
                        resize: true,
                        dpi: 500,
                    );
                }
            } catch (\Throwable $th) {}
        }

        $pdf->SetLineStyle(['width' => 0.5, 'color' => [51, 51, 255]]);
        $pdf->SetFont($this->fontArialItalic, 'I', 8);
        $pdf->Cell(0, 0, 'Annex 34', 'LTR', 1, 'R');

        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->Cell(0, 0, '', 'LR', 1);

        $pdf->SetFont($this->fontArialBold, 'B', 12);
        $pdf->SetTextColor(51, 51, 255);
        $pdf->Cell(0, 0, 'ACKNOWLEDGMENT RECEIPT FOR EQUIPMENT', 'LR', 1, 'C');

        $pdf->SetFont($this->fontArialBold, 'B', 24);
        $pdf->Cell(0, 0, '', 'LR', 1, 'C');

        $pdf->SetFont($this->fontArialBold, 'BU', 12);
        $pdf->Cell(0, 0, "{$municipality}, {$province}", 'LR', 1, 'C');

        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->Cell(0, 0, $companyType, 'LR', 1, 'C');

        $pdf->SetFont($this->fontArialBold, 'B', 12);
        $pdf->SetTextColor(0);
        $pdf->Cell($pageWidth * 0.6, 0, '', 'L', 0);
        $pdf->Cell($pageWidth * 0.16, 0, 'ARE Number:', 0, 0);
        $pdf->Cell(0, 0, $inventoryNumber, 'R', 1);

        $pdf->Cell($pageWidth * 0.6, 0, '', 'L', 0);
        $pdf->Cell($pageWidth * 0.16, 0, 'Date:', 0, 0);
        $pdf->Cell(0, 0, $inventoryDate, 'R', 1);

        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->Cell(0, 0, '', 'LR', 1);

        $htmlTable = '
            <table 
                style="border: 1px solid #3333FF; border-left: 1.5px solid #3333FF; border-right: 1.5px solid #3333FF;"
                cellpadding="2"
            ><thead><tr>
                <th
                    style="border-right: 1px solid #3333FF;"
                    width="9%"
                    align="center"
                >Quantity</th>
                <th
                    style="border-right: 1px solid #3333FF;"
                    width="8%"
                    align="center"
                >Unit</th>
                <th
                    style="border-right: 1px solid #3333FF;"
                    width="43%"
                    align="center"
                >Description</th>
                <th
                    style="border-right: 1px solid #3333FF;"
                    width="15%"
                    align="center"
                >Date<br />Acquired</th>
                <th
                    style="border-right: 1px solid #3333FF;"
                    width="10%"
                    align="center"
                >Property<br />No.</th>
                <th
                    width="15%"
                    align="center"
                >Amount</th>
            </tr></thead></table>
        ';

        $pdf->setCellHeightRatio(1.25);
        $pdf->SetFont($this->fontArialBold, 'B', 10);
        $pdf->writeHTML($htmlTable, ln: false);
        $pdf->Ln(0);

        $htmlTable = '
            <table 
                style="border-top: 1px solid #3333FF; border-left: 1.5px solid #3333FF; border-right: 1.5px solid #3333FF;"
                cellpadding="2"
            ><tbody>
        ';

        foreach ($items as $item) {
            $quantity = $item->quantity ?? 0;
            $unit = $item?->supply?->unit_issue?->unit_name ?? '';
            $description = trim(str_replace("\r", '<br />', $item->description));
            $description = str_replace("\n", '<br />', $description);
            $acquiredDate = $item->acquired_date
                ? date_format(date_create($item->acquired_date), 'm/d/Y') 
                : '';
            $propertyNo = $item->property_no;
            $amount = number_format($item->total_cost, 2);

            $htmlTable .= '
                <tr>
                    <td
                        style="border-right: 1px solid #3333FF;"
                        width="9%"
                        align="center"
                    >'. $quantity .'</td>
                    <td
                        style="border-right: 1px solid #3333FF;"
                        width="8%"
                        align="center"
                    >'. $unit .'</td>
                    <td
                        style="border-right: 1px solid #3333FF;"
                        width="43%"
                    >'. $description .'</td>
                    <td
                        style="border-right: 1px solid #3333FF;"
                        width="15%"
                    >'. $acquiredDate .'</td>
                    <td
                        style="border-right: 1px solid #3333FF;"
                        width="10%"
                    >'. $propertyNo .'</td>
                    <td
                        style="font-size: 14px;"
                        width="15%"
                        align="right"
                    >'. $amount .'</td>
                </tr>
            ';
        }

        $htmlTable .= '</tbody></table>';
        $pdf->setCellHeightRatio(1.25);
        $pdf->SetFont($this->fontArialBold, 'B', 12);
        $pdf->writeHTML($htmlTable, ln: false);
        $pdf->Ln(0);

        $htmlTable = '
            <table 
                style="border-bottom: 1px solid #3333FF; border-left: 1.5px solid #3333FF; border-right: 1.5px solid #3333FF;"
                cellpadding="2"
            ><tbody>
                <tr>
                    <td
                        style="border-right: 1px solid #3333FF;"
                        width="9%"
                        align="center"
                    ></td>
                    <td
                        style="border-right: 1px solid #3333FF;"
                        width="8%"
                        align="center"
                    ></td>
                    <td
                        style="border-right: 1px solid #3333FF;"
                        width="43%"
                    ></td>
                    <td
                        style="border-right: 1px solid #3333FF;"
                        width="15%"
                    ></td>
                    <td
                        style="border-right: 1px solid #3333FF;"
                        width="10%"
                    ></td>
                    <td
                        width="15%"
                        align="right"
                    ></td>
                </tr>
                <tr>
                    <td
                        style="border-right: 1px solid #3333FF;"
                        width="9%"
                        align="center"
                    ></td>
                    <td
                        style="border-right: 1px solid #3333FF;"
                        width="8%"
                        align="center"
                    ></td>
                    <td
                        style="border-right: 1px solid #3333FF; color: #0000CC;"
                        width="43%"
                    >'. $purpose .'</td>
                    <td
                        style="border-right: 1px solid #3333FF;"
                        width="15%"
                    ></td>
                    <td
                        style="border-right: 1px solid #3333FF;"
                        width="10%"
                    ></td>
                    <td
                        width="15%"
                        align="right"
                    ></td>
                </tr>
                <tr>
                    <td
                        style="border-right: 1px solid #3333FF;"
                        width="9%"
                        align="center"
                    ></td>
                    <td
                        style="border-right: 1px solid #3333FF;"
                        width="8%"
                        align="center"
                    ></td>
                    <td
                        style="border-right: 1px solid #3333FF;"
                        width="43%"
                    ></td>
                    <td
                        style="border-right: 1px solid #3333FF;"
                        width="15%"
                    ></td>
                    <td
                        style="border-right: 1px solid #3333FF;"
                        width="10%"
                    ></td>
                    <td
                        width="15%"
                        align="right"
                    ></td>
                </tr>
            </tbody></table>
        ';

        $pdf->setCellHeightRatio(1.25);
        $pdf->SetFont('Times', 'B', 12);
        $pdf->writeHTML($htmlTable, ln: false);
        $pdf->Ln(0);

        $pdf->SetFont('Times', 'BI', 11);
        $pdf->Cell($pageWidth * 0.5, 0, 'Received by:', 'L');
        $pdf->Cell($pageWidth * 0.5, 0, 'Received from:', 'LR', 1);

        $pdf->SetFont('Times', '', 24);
        $pdf->Cell($pageWidth * 0.5, 0, '', 'L');
        $pdf->Cell(0, 0, '', 'LR', 1);

        $pdf->SetFont('Times', 'B', 14);
        $pdf->Cell($pageWidth * 0.5, 0, $receivedByName, 'LB', 0, 'C');
        $pdf->Cell(0, 0, $receivedFromName, 'LRB', 1, 'C');

        $pdf->SetFont('Times', '', 11);
        $pdf->setTextColor(192, 0, 0);
        $pdf->Cell($pageWidth * 0.5, 0, 'Name', 'L', 0, 'C');
        $pdf->Cell(0, 0, 'Name', 'LR', 1, 'C');

        $pdf->SetFont('Times', '', 7);
        $pdf->Cell($pageWidth * 0.5, 0, '', 'LB', 0, 'C');
        $pdf->Cell(0, 0, '', 'LRB', 1, 'C');

        $pdf->Cell($pageWidth * 0.5, 0, '', 'L', 0, 'C');
        $pdf->Cell(0, 0, '', 'LR', 1, 'C');

        $pdf->SetFont('Times', '', 11);
        $pdf->setTextColor(0);
        $pdf->Cell($pageWidth * 0.5, 0, $receivedByPosition, 'LB', 0, 'C');
        $pdf->Cell(0, 0, $receivedFromPosition, 'LRB', 1, 'C');

        $pdf->SetFont('Times', '', 11);
        $pdf->setTextColor(192, 0, 0);
        $pdf->Cell($pageWidth * 0.5, 0, 'Position', 'L', 0, 'C');
        $pdf->Cell(0, 0, 'Position', 'LR', 1, 'C');

        $pdf->SetFont('Times', '', 5);
        $pdf->Cell($pageWidth * 0.5, 0, '', 'L', 0, 'C');
        $pdf->Cell(0, 0, '', 'LR', 1, 'C');
        
        $pdf->SetFont('Times', '', 11);
        $pdf->setTextColor(0);
        $pdf->Cell($pageWidth * 0.5, 0, $receivedBySignedDate, 'LB', 0, 'C');
        $pdf->Cell(0, 0, $receivedFromSignedDate, 'LRB', 1, 'C');

        $pdf->setTextColor(192, 0, 0);
        $pdf->Cell($pageWidth * 0.5, 0, 'Date', 'L', 0, 'C');
        $pdf->Cell(0, 0, 'Date', 'LR', 1, 'C');

        $pdf->SetFont('Times', '', 5);
        $pdf->Cell($pageWidth * 0.5, 0, '', 'LB', 0, 'C');
        $pdf->Cell(0, 0, '', 'LRB', 1, 'C');

        $pdfBlob = $pdf->Output($filename, 'S');
        $pdfBase64 = base64_encode($pdfBlob);

        return $pdfBase64;
    }

    private function generateInventoryCustodianSlipDoc(
        string $filename, array $pageConfig, InventoryIssuance $data, Company $company
    ): string {
        $municipality = $company->municipality ?? '';
        $province = $company->province ?? '';
        $inventoryNumber = $data->inventory_no;
        $inventoryDate = $data->inventory_date
            ? date_format(date_create($data->inventory_date), 'F j, Y') 
            : '';
        $items = !empty($data->items) ? $data->items : [];
        $purpose = $data?->purchase_order?->purchase_request?->purpose ?? '';
        $purpose = trim(str_replace("\r", '<br />', $purpose));
        $purpose = str_replace("\n", '<br />', $purpose);
        $receivedByName = $data->recipient?->fullname ?? '';
        $receivedByPosition = $data->recipient?->position?->position_name ?? '';
        $receivedBySignedDate = $data->received_date 
            ? date_format(date_create($data->received_date), 'M j, Y') 
            : '';
        $receivedFromName = $data->signatory_issuer?->user?->fullname ?? '';
        $receivedFromPosition = $data->signatory_issuer?->detail?->position ?? '-';
        $receivedFromSignedDate = $data->issued_date 
            ? date_format(date_create($data->issued_date), 'M j, Y') 
            : '';

        $pdf = new TCPDF($pageConfig['orientation'], $pageConfig['unit'], $pageConfig['dimension']);

        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor(env('APP_NAME'));
        $pdf->SetTitle($filename);
        $pdf->SetSubject('Acknowledgment Receipt for Equipment');
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
                    $y + ($y * 0.2),
                    w: $pageConfig['orientation'] === 'P'
                        ? $x - ($x * 0.1)
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
                        $x + ($x * 1.4),
                        $y + ($y * 0.2),
                        w: $pageConfig['orientation'] === 'P'
                            ? $x - ($x * 0.1)
                            : $y + ($y * 0.4),
                        type: 'PNG',
                        resize: true,
                        dpi: 500,
                    );
                }
            } catch (\Throwable $th) {}
        }

        $pdf->SetLineStyle(['width' => 0.5, 'color' => [51, 51, 255]]);
        $pdf->SetFont('Times', '', 10);
        $pdf->Cell(0, 0, '', 'LTR', 1);

        $pdf->Cell(0, 0, "Province of {$province}", 'LR', 1, 'C');

        $pdf->SetFont('Times', 'B', 14);
        $pdf->Cell(0, 0, $municipality, 'LR', 1, 'C');

        $pdf->SetFont($this->fontArialBold, 'B', 24);
        $pdf->Cell(0, 0, '', 'LR', 1, 'C');

        $pdf->SetFont('Times', 'B', 16);
        $pdf->Cell(0, 0, 'INVENTORY CUSTODIAN SLIP', 'LR', 1, 'C');

        $htmlTable = '
            <table 
                style="border-bottom: 1px solid #3333FF; border-left: 1.5px solid #3333FF; border-right: 1.5px solid #3333FF;"
                cellpadding="2"
            ><tbody>
                <tr>
                    <td
                        width="71%"
                    ></td>
                    <td
                        width="11%"
                         align="right"
                    >ICS No.:</td>
                    <td
                        style="border-right: 1px solid #3333FF; border-bottom: 1px solid #000; font-size: 12px; color: #3333FF;"
                        width="18%"
                    >'. $inventoryNumber .'</td>
                </tr>
                <tr>
                    <td
                        width="71%"
                    ></td>
                    <td
                        width="11%"
                        align="right"
                    >Date:</td>
                    <td
                        style="border-right: 1px solid #3333FF; border-bottom: 1px solid #000; color: #3333FF;"
                        width="18%"
                    >'. $inventoryDate .'</td>
                </tr>
            </tbody></table>
        ';

        $pdf->setCellHeightRatio(0.8);
        $pdf->SetFont('Times', 'B', 10);
        $pdf->writeHTML($htmlTable, ln: false);
        $pdf->Ln(0);

        $htmlTable = '
            <table 
                style="border: 1px solid #3333FF; border-left: 1.5px solid #3333FF; border-right: 1.5px solid #3333FF;"
                cellpadding="2"
            ><thead><tr>
                <th
                    style="border-right: 1px solid #3333FF;"
                    width="9%"
                    align="center"
                >Quantity</th>
                <th
                    style="border-right: 1px solid #3333FF;"
                    width="8%"
                    align="center"
                >Unit</th>
                <th
                    style="border-right: 1px solid #3333FF;"
                    width="38%"
                    align="center"
                >Description</th>
                <th
                    style="border-right: 1px solid #3333FF;"
                    width="10%"
                    align="center"
                >Inventory<br />Item No.</th>
                <th
                    style="border-right: 1px solid #3333FF;"
                    width="11%"
                    align="center"
                >Estimated<br />Useful Life</th>
                <th
                    style="border-right: 1px solid #3333FF;"
                    width="10%"
                    align="center"
                >Date<br />Acquired</th>
                <th
                    width="14%"
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
                style="border-top: 1px solid #3333FF; border-left: 1.5px solid #3333FF; border-right: 1.5px solid #3333FF;"
                cellpadding="2"
            ><tbody>
        ';

        foreach ($items as $item) {
            $quantity = $item->quantity ?? 0;
            $unit = $item?->supply?->unit_issue?->unit_name ?? '';
            $description = trim(str_replace("\r", '<br />', $item->description));
            $description = str_replace("\n", '<br />', $description);
            $inventoryItemNo = $item->inventory_item_no ?? '';
            $estimatedUsefulLife = $item->estimated_useful_life ?? '';
            $acquiredDate = $item->acquired_date
                ? date_format(date_create($item->acquired_date), 'm/d/Y') 
                : '';
            $amount = number_format($item->total_cost, 2);

            $htmlTable .= '
                <tr>
                    <td
                        style="border-right: 1px solid #3333FF;"
                        width="9%"
                        align="center"
                    >'. $quantity .'</td>
                    <td
                        style="border-right: 1px solid #3333FF;"
                        width="8%"
                        align="center"
                    >'. $unit .'</td>
                    <td
                        style="border-right: 1px solid #3333FF;"
                        width="38%"
                    >'. $description .'</td>
                    <td
                        style="border-right: 1px solid #3333FF;"
                        width="10%"
                    >'. $inventoryItemNo .'</td>
                    <td
                        style="border-right: 1px solid #3333FF;"
                        width="11%"
                    >'. $estimatedUsefulLife .'</td>
                    <td
                        style="border-right: 1px solid #3333FF;"
                        width="10%"
                        align="center"
                    >'. $acquiredDate .'</td>
                    <td
                        width="14%"
                        align="center"
                    >'. $amount .'</td>
                </tr>
            ';
        }

        $htmlTable .= '</tbody></table>';
        $pdf->setCellHeightRatio(1.25);
        $pdf->SetFont($this->fontArialBold, 'B', 12);
        $pdf->writeHTML($htmlTable, ln: false);
        $pdf->Ln(0);

        $htmlTable = '
            <table 
                style="border-bottom: 1px solid #3333FF; border-left: 1.5px solid #3333FF; border-right: 1.5px solid #3333FF;"
                cellpadding="2"
            ><tbody>
                <tr>
                    <td
                        style="border-right: 1px solid #3333FF;"
                        width="9%"
                        align="center"
                    ></td>
                    <td
                        style="border-right: 1px solid #3333FF;"
                        width="8%"
                        align="center"
                    ></td>
                    <td
                        style="border-right: 1px solid #3333FF;"
                        width="38%"
                    ></td>
                    <td
                        style="border-right: 1px solid #3333FF;"
                        width="10%"
                    ></td>
                    <td
                        style="border-right: 1px solid #3333FF;"
                        width="11%"
                    ></td>
                    <td
                        style="border-right: 1px solid #3333FF;"
                        width="10%"
                    ></td>
                    <td
                        width="14%"
                        align="right"
                    ></td>
                </tr>
                <tr>
                    <td
                        style="border-right: 1px solid #3333FF;"
                        width="9%"
                        align="center"
                    ></td>
                    <td
                        style="border-right: 1px solid #3333FF;"
                        width="8%"
                        align="center"
                    ></td>
                    <td
                        style="border-right: 1px solid #3333FF; color: #0000CC;"
                        width="38%"
                    >'. $purpose .'</td>
                    <td
                        style="border-right: 1px solid #3333FF;"
                        width="10%"
                    ></td>
                    <td
                        style="border-right: 1px solid #3333FF;"
                        width="11%"
                    ></td>
                    <td
                        style="border-right: 1px solid #3333FF;"
                        width="10%"
                    ></td>
                    <td
                        width="14%"
                        align="right"
                    ></td>
                </tr>
                <tr>
                    <td
                        style="border-right: 1px solid #3333FF;"
                        width="9%"
                        align="center"
                    ></td>
                    <td
                        style="border-right: 1px solid #3333FF;"
                        width="8%"
                        align="center"
                    ></td>
                    <td
                        style="border-right: 1px solid #3333FF;"
                        width="38%"
                    ></td>
                    <td
                        style="border-right: 1px solid #3333FF;"
                        width="10%"
                    ></td>
                    <td
                        style="border-right: 1px solid #3333FF;"
                        width="11%"
                    ></td>
                    <td
                        style="border-right: 1px solid #3333FF;"
                        width="10%"
                    ></td>
                    <td
                        width="14%"
                        align="right"
                    ></td>
                </tr>
            </tbody></table>
        ';

        $pdf->setCellHeightRatio(1.25);
        $pdf->SetFont($this->fontArialBold, 'B', 12);
        $pdf->writeHTML($htmlTable, ln: false);
        $pdf->Ln(0);

        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->Cell($pageWidth * 0.55, 0, '', 'L');
        $pdf->Cell(0, 0, '', 'LR', 1);
        
        $pdf->SetFont($this->fontArialItalic, 'I', 10);
        $pdf->Cell($pageWidth * 0.55, 0, 'Received by:', 'L');
        $pdf->Cell(0, 0, 'Received from:', 'LR', 1);

        $pdf->Cell($pageWidth * 0.55, 0, '', 'L');
        $pdf->Cell(0, 0, '', 'LR', 1);

        $pdf->SetFont($this->fontArialBold, 'B', 12);
        $pdf->Cell($pageWidth * 0.55, 0, $receivedByName, 'LTB', 0, 'C');
        $pdf->Cell(0, 0, $receivedFromName, 'LTRB', 1, 'C');

        $pdf->SetFont($this->fontArial, '', size: 10);
        $pdf->setTextColor(192, 0, 0);
        $pdf->Cell($pageWidth * 0.55, 0, '(Signature Over Printed Name)', 'L', 0, 'C');
        $pdf->Cell(0, 0, '(Signature Over Printed Name)', 'LR', 1, 'C');

        $pdf->SetFont($this->fontArial, '', 11);
        $pdf->setTextColor(0);
        $pdf->Cell($pageWidth * 0.55, 0, $receivedByPosition, 'LB', 0, 'C');
        $pdf->Cell(0, 0, $receivedFromPosition, 'LRB', 1, 'C');

        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->setTextColor(192, 0, 0);
        $pdf->Cell($pageWidth * 0.55, 0, '(Position/Office)', 'L', 0, 'C');
        $pdf->Cell(0, 0, '(Position/Office)', 'LR', 1, 'C');

        $pdf->SetFont($this->fontArial, '', 11);
        $pdf->setTextColor(0);
        $pdf->Cell($pageWidth * 0.55, 0, $receivedBySignedDate, 'LB', 0, 'C');
        $pdf->Cell(0, 0, $receivedFromSignedDate, 'LRB', 1, 'C');

        $pdf->setTextColor(192, 0, 0);
        $pdf->Cell($pageWidth * 0.55, 0, '(Date)', 'LB', 0, 'C');
        $pdf->Cell(0, 0, '(Date)', 'LRB', 1, 'C');

        $pdfBlob = $pdf->Output($filename, 'S');
        $pdfBase64 = base64_encode($pdfBlob);

        return $pdfBase64;
    }
}
