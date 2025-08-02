<?php

namespace App\Repositories;

use App\Enums\AbstractQuotationStatus;
use App\Helpers\FileHelper;
use App\Helpers\StatusTimestampsHelper;
use App\Interfaces\AbstractQuotationRepositoryInterface;
use App\Jobs\StoreAbstractItems;
use App\Models\AbstractQuotation;
use App\Models\AbstractQuotationItem;
use App\Models\Company;
use App\Models\PurchaseRequestItem;
use Illuminate\Support\Collection;
use TCPDF;
use TCPDF_FONTS;

class AbstractQuotationRepository implements AbstractQuotationRepositoryInterface
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

    public function storeUpdate(array $data, ?AbstractQuotation $abstractQuotation = null): AbstractQuotation
    {
        if (! empty($abstractQuotation)) {
            $abstractQuotation->update($data);
        } else {
            $abstractQuotation = AbstractQuotation::create(
                array_merge(
                    $data,
                    [
                        'abstract_no' => $this->generateNewAoqNumber(),
                        'status' => AbstractQuotationStatus::DRAFT,
                        'status_timestamps' => StatusTimestampsHelper::generate(
                            'draft_at', null
                        ),
                    ]
                )
            );
        }

        $this->storeItems(
            collect(isset($data['items']) && ! empty($data['items']) ? $data['items'] : []),
            $abstractQuotation
        );

        return $abstractQuotation;
    }

    private function storeItems(Collection $items, AbstractQuotation $abstractQuotation): void
    {
        $itemChunks = $items->chunk(20);

        AbstractQuotationItem::where('abstract_quotation_id', $abstractQuotation->id)
            ->delete();

        foreach ($itemChunks as $itemChunk) {
            StoreAbstractItems::dispatch($itemChunk, $abstractQuotation);
        }
    }

    private function generateNewAoqNumber(): string
    {
        $year = date('Y');
        $sequence = AbstractQuotation::whereYear('created_at', $year)
            ->count() + 1;

        return "{$year}-{$sequence}";
    }

    public function print(array $pageConfig, string $aoqId): array
    {
        try {
            $company = Company::first();
            $aoq = AbstractQuotation::with([
                'bids_awards_committee:id,committee_name',
                'mode_procurement:id,mode_name',
                'signatory_twg_chairperson:id,user_id',
                'signatory_twg_chairperson.user:id,firstname,middlename,lastname,allow_signature,signature',
                'signatory_twg_chairperson.detail' => function ($query) {
                    $query->where('document', 'aoq')
                        ->where('signatory_type', 'twg_chairperson');
                },
                'signatory_twg_member_1:id,user_id',
                'signatory_twg_member_1.user:id,firstname,middlename,lastname,allow_signature,signature',
                'signatory_twg_member_1.detail' => function ($query) {
                    $query->where('document', 'aoq')
                        ->where('signatory_type', 'twg_member_1');
                },
                'signatory_twg_member_2:id,user_id',
                'signatory_twg_member_2.user:id,firstname,middlename,lastname,allow_signature,signature',
                'signatory_twg_member_2.detail' => function ($query) {
                    $query->where('document', 'aoq')
                        ->where('signatory_type', 'twg_member_2');
                },
                'signatory_chairman:id,user_id',
                'signatory_chairman.user:id,firstname,middlename,lastname,allow_signature,signature',
                'signatory_chairman.detail' => function ($query) {
                    $query->where('document', 'aoq')
                        ->where('signatory_type', 'chairman');
                },
                'signatory_vice_chairman:id,user_id',
                'signatory_vice_chairman.user:id,firstname,middlename,lastname,allow_signature,signature',
                'signatory_vice_chairman.detail' => function ($query) {
                    $query->where('document', 'aoq')
                        ->where('signatory_type', 'vice_chairman');
                },
                'signatory_member_1:id,user_id',
                'signatory_member_1.user:id,firstname,middlename,lastname,allow_signature,signature',
                'signatory_member_1.detail' => function ($query) {
                    $query->where('document', 'aoq')
                        ->where('signatory_type', 'member_1');
                },
                'signatory_member_2:id,user_id',
                'signatory_member_2.user:id,firstname,middlename,lastname,allow_signature,signature',
                'signatory_member_2.detail' => function ($query) {
                    $query->where('document', 'aoq')
                        ->where('signatory_type', 'member_2');
                },
                'signatory_member_3:id,user_id',
                'signatory_member_3.user:id,firstname,middlename,lastname,allow_signature,signature',
                'signatory_member_3.detail' => function ($query) {
                    $query->where('document', 'aoq')
                        ->where('signatory_type', 'member_3');
                },
                'items' => function ($query) {
                    $query->orderBy(
                        PurchaseRequestItem::select('item_sequence')
                            ->whereColumn(
                                'abstract_quotation_items.pr_item_id', 'purchase_request_items.id'
                            ),
                        'asc'
                    );
                },
                'items.awardee:id,supplier_name',
                'items.pr_item:id,unit_issue_id,item_sequence,quantity,description,stock_no',
                'items.pr_item.unit_issue:id,unit_name',
                'items.details',
                'items.details.supplier:id,supplier_name',
                'purchase_request:id,purpose',
            ])->find($aoqId);

            $filename = "AOQ-{$aoq->abstract_no}.pdf";
            $blob = $this->generateAbstractQuotationDoc($filename, $pageConfig, $aoq, $company);

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

    private function generateAbstractQuotationDoc(
        string $filename, array $pageConfig, AbstractQuotation $data, Company $company
    ): string {
        $purchaseRequest = $data->purchase_request;
        $bidsAwardsCommittee = $data->bids_awards_committee;
        $modeProcurement = $data->mode_procurement;
        $signatoryTwgChairperson = $data->signatory_twg_chairperson?->user;
        $signatoryTwgMember1 = $data->signatory_twg_member_1?->user;
        $signatoryTwgMember2 = $data->signatory_twg_member_2?->user;
        $signatoryChairman = $data->signatory_chairman?->user;
        $signatoryViceChairman = $data->signatory_vice_chairman?->user;
        $signatoryMember1 = $data->signatory_member_1?->user;
        $signatoryMember2 = $data->signatory_member_2?->user;
        $signatoryMember3 = $data->signatory_member_3?->user;

        $items = $data->items ?? [];
        $details = $data->items[0]->details ?? [];
        $supplierHeaders = collect($details ?? [])->map(function ($detail) use ($items) {
            $relevantDetails = collect($items ?? [])->flatMap(function ($item) {
                return $item->details ?? [];
            });

            return (object) [
                'supplier_id' => $detail->supplier_id,
                'supplier_name' => $detail->supplier->supplier_name,
                'unit_cost' => $relevantDetails
                    ->filter(function ($itemDetail) use ($detail) {
                        return $itemDetail->supplier_id === $detail->supplier_id;
                    })
                    ->reduce(function ($carry, $itemDetail) {
                        return $carry + ($itemDetail->unit_cost ?? 0);
                    }, 0),
                'total_cost' => $relevantDetails
                    ->filter(function ($itemDetail) use ($detail) {
                        return $itemDetail->supplier_id === $detail->supplier_id;
                    })
                    ->reduce(function ($carry, $itemDetail) {
                        return $carry + ($itemDetail->total_cost ?? 0);
                    }, 0),
            ];
        });

        $pdf = new TCPDF($pageConfig['orientation'], $pageConfig['unit'], $pageConfig['dimension']);

        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor(env('APP_NAME'));
        $pdf->SetTitle($filename);
        $pdf->SetSubject('Purchase Request');
        $pdf->SetMargins(
            $pdf->getPageWidth() * 0.03,
            $pdf->getPageHeight() * 0.04,
            $pdf->getPageWidth() * 0.03
        );
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        // $pdf->SetAutoPageBreak(TRUE, 0.4);

        $pdf->AddPage();

        $pageWidth = $pdf->getPageWidth() * 0.94;

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
                    $y - ($y * 0.04),
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

        $pdf->SetFont($this->fontArialBold, 'B', 10);
        $pdf->Cell(0, 0, 'Republic of the Philippines', 0, 1, 'C');
        $pdf->Cell(0, 0, "BIDS AND AWARDS COMMITTEE ({$bidsAwardsCommittee?->committee_name})", 0, 1, 'C');
        $pdf->Cell(0, 0, "ABSTRACT OF BIDS OR QUOTATION ({$modeProcurement?->mode_name})", 0, 1, 'C');

        $pdf->Ln();
        $pdf->Ln();

        $pdf->SetFont($this->fontArial, '', 12);
        $pdf->Cell($pageWidth * 0.105, 0, 'Solicitation No.:', 0, 0, 'L');
        $pdf->Cell($pageWidth * 0.235, 0, $data->solicitation_no, 'B', 0, 'L');
        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->Cell($pageWidth * 0.04, 0, 'Dated:', 0, 0, 'R');
        $pdf->Cell($pageWidth * 0.15, 0, date_format(date_create($data->abstract_date), 'F j, Y'), 'B', 0, 'L');
        $pdf->Cell($pageWidth * 0.14, 0, 'and opened on:', 0, 0, 'R');
        $pdf->Cell($pageWidth * 0.13, 0, date_format(date_create($data->opened_on), 'F j, Y'), 'B', 0, 'L');
        $pdf->Cell($pageWidth * 0.08, 0, 'Abstract No.:', 0, 0, 'R');
        $pdf->Cell(0, 0, $data->abstract_no, 'B', 1, 'L');

        $pdf->Ln();

        $supplierHeadersCount = count($supplierHeaders);
        $htmlTable = '
            <table border="1" cellpadding="3"><thead><tr>
                <th
                    rowspan="2"
                    width="3.5%"
                    align="center"
                >Stock No.</th>
                <th
                    rowspan="2"
                    width="3%"
                    align="center"
                >QTY.</th>
                <th
                    rowspan="2"
                    width="4%"
                    align="center"
                >UNIT</th>
                <th
                    rowspan="2"
                    width="'.($supplierHeadersCount > 3 ? '18.5' : '23.5').'%"
                    align="center"
                >DESCRIPTION/SPECIFICATION OF ARTICLES</th>';

        foreach ($supplierHeaders as $supplierHeader) {
            $htmlTable .= '
                <th
                    colspan="3"
                    width="'.($supplierHeadersCount > 3 ? 71 : 66) / $supplierHeadersCount.'%"
                    align="center"
                '.">{$supplierHeader->supplier_name}</th>";
        }

        $htmlTable .= '</tr><tr>';

        foreach ($supplierHeaders as $supplierHeader) {
            $htmlTable .= '
                <th
                    width="'.(0.24 * (($supplierHeadersCount > 3 ? 71 : 66) / $supplierHeadersCount)).'%"
                    align="center"
                >Brand</th>
                <th
                    width="'.(0.38 * (($supplierHeadersCount > 3 ? 71 : 66) / $supplierHeadersCount)).'%"
                    align="center"
                >Unit Price</th>
                <th
                    width="'.(0.38 * (($supplierHeadersCount > 3 ? 71 : 66) / $supplierHeadersCount)).'%"
                    align="center"
                >Total Price</th>
            ';
        }

        $htmlTable .= '</tr></thead></table>';

        $pdf->SetLineStyle(['width' => $pdf->getPageWidth() * 0.0012, 'color' => [0, 0, 0]]);

        $pdf->SetFont($this->fontArial, '', 9);
        $pdf->writeHTML($htmlTable, ln: false);
        $pdf->Ln(0);

        $htmlTable = '<table border="1" cellpadding="2"><tbody>';

        foreach ($items ?? [] as $item) {
            if (! $item->included) {
                continue;
            }

            $prItem = $item->pr_item;
            $description = trim(str_replace("\r", '<br />', $prItem->description));
            $description = str_replace("\n", '<br />', $description);
            $details = collect($item->details);

            $htmlTable .= '
                <tr>
                    <td
                        width="3.5%"
                        align="center"
                    >'.$prItem->stock_no.'</td>
                    <td
                        width="3%"
                        align="center"
                    >'.$prItem->quantity.'</td>
                    <td
                        width="4%"
                        align="center"
                    >'.$prItem->unit_issue->unit_name.'</td>
                    <td
                        width="'.($supplierHeadersCount > 3 ? '18.5' : '23.5').'%"
                        align="left"
                    >'.$description.'</td>';

            foreach ($supplierHeaders as $supplierHeader) {
                $detail = collect($details ?? [])->first(function ($detail) use ($supplierHeader) {
                    return $detail->supplier_id === $supplierHeader->supplier_id;
                });

                $brandModel = trim(str_replace("\r", '<br />', $detail->brand_model));
                $brandModel = str_replace("\n", '<br />', $brandModel);

                $htmlTable .= '
                    <td
                        width="'.(0.24 * (($supplierHeadersCount > 3 ? 71 : 66) / $supplierHeadersCount)).'%"
                        align="left"
                    >'.$brandModel.'</td>
                    <td
                        width="'.(0.38 * (($supplierHeadersCount > 3 ? 71 : 66) / $supplierHeadersCount)).'%"
                        align="right"
                    >'.number_format($detail->unit_cost, 2).'</td>
                    <td
                        width="'.(0.38 * (($supplierHeadersCount > 3 ? 71 : 66) / $supplierHeadersCount)).'%"
                        align="right"
                    >'.number_format($detail->total_cost, 2).'</td>
                ';
            }

            $htmlTable .= '</tr>';
        }

        $htmlTable .= '</tbody></table>';

        $pdf->writeHTML($htmlTable, ln: false);
        $pdf->Ln(0);

        $purpose = trim(str_replace("\r", '<br />', $purchaseRequest->purpose));
        $purpose = str_replace("\n", '<br />', $purpose);

        $htmlTable = '<table border="1" cellpadding="5"><tbody>
            <tr>
                <td
                    width="10.5%"
                    align="center"
                ><strong>PURPOSE:</strong></td>
                <td
                    width="'.($supplierHeadersCount > 3 ? '18.5' : '23.5').'%"
                    align="left"
                >'.$purpose.'</td>';

        foreach ($supplierHeaders as $supplierHeader) {
            $htmlTable .= '
                <td
                    width="'.(0.24 * (($supplierHeadersCount > 3 ? 71 : 66) / $supplierHeadersCount)).'%"
                    align="left"
                ></td>
                <td
                    width="'.(0.38 * (($supplierHeadersCount > 3 ? 71 : 66) / $supplierHeadersCount)).'%"
                    align="right"
                ></td>
                <td
                    width="'.(0.38 * (($supplierHeadersCount > 3 ? 71 : 66) / $supplierHeadersCount)).'%"
                    align="right"
                ></td>
            ';
        }

        $htmlTable .= '</tr></tbody></table>';

        $pdf->SetFont('helvetica', '', 9);
        $pdf->writeHTML($htmlTable, ln: false);
        $pdf->Ln(0);

        $bacAction = trim(str_replace("\r", '<br />', $data->bac_action));
        $bacAction = str_replace("\n", '<br />', $bacAction);

        $pdf->SetFont($this->fontArial, '', 9);
        $pdf->Cell(
            $pageWidth * (
                0.105
                    + ($supplierHeadersCount > 3 ? 0.185 : 0.235)
                    + (0.62 * (($supplierHeadersCount > 3 ? 0.71 : 0.66) / $supplierHeadersCount))
            ),
            border: 0
        );

        foreach ($supplierHeaders as $index => $supplierHeader) {
            if ($index === 0) {
                $pdf->Cell(
                    $pageWidth * (0.38 * (($supplierHeadersCount > 3 ? 0.71 : 0.66) / $supplierHeadersCount)),
                    0, 'P'.number_format($supplierHeader->total_cost, 2), 1, 0, 'L'
                );

                continue;
            }

            $pdf->Cell(
                $pageWidth * (0.24 * (($supplierHeadersCount > 3 ? 0.71 : 0.66) / $supplierHeadersCount)),
                0, '', 1, 0, 'L'
            );
            $pdf->Cell(
                $pageWidth * (0.38 * (($supplierHeadersCount > 3 ? 0.71 : 0.66) / $supplierHeadersCount)),
                0, '', 1, 0, 'L'
            );
            $pdf->Cell(
                $index === $supplierHeadersCount - 1
                    ? 0
                    : $pageWidth * (0.38 * (($supplierHeadersCount > 3 ? 0.71 : 0.66) / $supplierHeadersCount)),
                0, 'P'.number_format($supplierHeader->total_cost, 2), 1,
                $index === $supplierHeadersCount - 1 ? 1 : 0,
                'L'
            );
        }

        $pdf->Cell(
            $pageWidth * (
                0.105
                    + ($supplierHeadersCount > 3 ? 0.185 : 0.235)
                    + (0.62 * (($supplierHeadersCount > 3 ? 0.71 : 0.66) / $supplierHeadersCount))
            ),
            border: 0
        );
        $pdf->MultiCell(0, 0, "BAC Action: {$bacAction}", 1, 'L', ln: 1, ishtml: true);

        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->Cell(
            $pageWidth * (
                0.105
                    + ($supplierHeadersCount > 3 ? 0.185 : 0.235)
                    + (0.62 * (($supplierHeadersCount > 3 ? 0.71 : 0.66) / $supplierHeadersCount))
            ),
            txt: 'Prepared by:',
            border: 0
        );
        $pdf->Cell(0, 0, '', 'LR', 1, 'L');

        $pdf->Cell(
            $pageWidth * 0.065,
            border: 0
        );
        $pdf->SetFont($this->fontArialBold, 'B', 10);
        $pdf->Cell(
            w: $pageWidth * (
                0.04 + ($supplierHeadersCount > 3 ? 0.185 : 0.235)
            ),
            h: 0,
            txt: strtoupper($signatoryTwgChairperson?->fullname),
            border: 'B',
            ln: 0,
            align: 'C'
        );
        $pdf->Cell(
            w: $pageWidth * (
                0.62 * (($supplierHeadersCount > 3 ? 0.71 : 0.66) / $supplierHeadersCount)
            ),
            h: 0,
            txt: '',
            border: 0,
            ln: 0,
            align: 'L'
        );
        $pdf->Cell(0, 0, '', 'LR', 1, 'L');

        $pdf->Cell(
            $pageWidth * 0.065,
            border: 0
        );
        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->Cell(
            w: $pageWidth * (
                0.04 + ($supplierHeadersCount > 3 ? 0.185 : 0.235)
            ),
            h: 0,
            txt: 'BAC-TWG Chairperson',
            border: 0,
            ln: 0,
            align: 'C'
        );
        $pdf->Cell(
            w: $pageWidth * (
                0.62 * (($supplierHeadersCount > 3 ? 0.71 : 0.66) / $supplierHeadersCount)
            ),
            h: 0,
            txt: '',
            border: 0,
            ln: 0,
            align: 'L'
        );
        $pdf->Cell(0, 0, '', 'LRB', 1, 'L');

        $pdf->Ln();
        $pdf->Ln();

        $pdf->SetFont($this->fontArialBold, 'B', 10);
        $pdf->Cell(
            w: $pageWidth * (
                0.45 * (
                    0.105 + ($supplierHeadersCount > 3 ? 0.185 : 0.235)
                    + (0.62 * (($supplierHeadersCount > 3 ? 0.71 : 0.66) / $supplierHeadersCount))
                )
            ),
            h: 0,
            txt: strtoupper($signatoryTwgMember1?->fullname),
            border: 'B',
            ln: 0,
            align: 'C'
        );
        $pdf->Cell(
            w: $pageWidth * (
                0.1 * (
                    0.105 + ($supplierHeadersCount > 3 ? 0.185 : 0.235)
                    + (0.62 * (($supplierHeadersCount > 3 ? 0.71 : 0.66) / $supplierHeadersCount))
                )
            ),
            border: 0
        );
        $pdf->Cell(
            w: $pageWidth * (
                0.45 * (
                    0.105 + ($supplierHeadersCount > 3 ? 0.185 : 0.235)
                    + (0.62 * (($supplierHeadersCount > 3 ? 0.71 : 0.66) / $supplierHeadersCount))
                )
            ),
            h: 0,
            txt: strtoupper($signatoryTwgMember2?->fullname),
            border: 'B',
            ln: 0,
            align: 'C'
        );
        $pdf->Cell(0, 0, '', 0, 1, 'L');

        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->Cell(
            w: $pageWidth * (
                0.45 * (
                    0.105 + ($supplierHeadersCount > 3 ? 0.185 : 0.235)
                    + (0.62 * (($supplierHeadersCount > 3 ? 0.71 : 0.66) / $supplierHeadersCount))
                )
            ),
            h: 0,
            txt: 'TWG Member',
            border: '',
            ln: 0,
            align: 'C'
        );
        $pdf->Cell(
            w: $pageWidth * (
                0.1 * (
                    0.105 + ($supplierHeadersCount > 3 ? 0.185 : 0.235)
                    + (0.62 * (($supplierHeadersCount > 3 ? 0.71 : 0.66) / $supplierHeadersCount))
                )
            ),
            border: 0
        );
        $pdf->Cell(
            w: $pageWidth * (
                0.45 * (
                    0.105 + ($supplierHeadersCount > 3 ? 0.185 : 0.235)
                    + (0.62 * (($supplierHeadersCount > 3 ? 0.71 : 0.66) / $supplierHeadersCount))
                )
            ),
            h: 0,
            txt: 'TWG Member',
            border: '',
            ln: 0,
            align: 'C'
        );
        $pdf->Cell(0, 0, '', 0, 1, 'L');

        $pdf->Ln();
        $pdf->Ln();

        $pdf->SetFont($this->fontArialBold, 'B', 10);
        $pdf->Cell(
            w: $pageWidth * 0.16,
            border: 0
        );
        $pdf->Cell(
            w: $pageWidth * 0.22,
            h: 0,
            txt: strtoupper($signatoryChairman?->fullname),
            border: 'B',
            ln: 0,
            align: 'C'
        );
        $pdf->Cell(
            w: $pageWidth * 0.16,
            border: 0
        );
        $pdf->Cell(
            w: $pageWidth * 0.22,
            h: 0,
            txt: strtoupper($signatoryViceChairman?->fullname),
            border: 'B',
            ln: 0,
            align: 'C'
        );
        $pdf->Cell(0, 0, '', 0, 1, 'L');

        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->Cell(
            w: $pageWidth * 0.16,
            border: 0
        );
        $pdf->Cell(
            w: $pageWidth * 0.22,
            h: 0,
            txt: 'Chairman & Presiding Officer',
            border: '',
            ln: 0,
            align: 'C'
        );
        $pdf->Cell(
            w: $pageWidth * 0.16,
            border: 0
        );
        $pdf->Cell(
            w: $pageWidth * 0.22,
            h: 0,
            txt: 'Vice Chairman',
            border: '',
            ln: 0,
            align: 'C'
        );
        $pdf->Cell(0, 0, '', 0, 1, 'L');

        $pdf->Ln();
        $pdf->Ln();

        $pdf->SetFont($this->fontArialBold, 'B', 10);
        $pdf->Cell(
            w: $pageWidth * 0.22,
            h: 0,
            txt: strtoupper($signatoryMember1?->fullname),
            border: 'B',
            ln: 0,
            align: 'C'
        );
        $pdf->Cell(
            w: $pageWidth * 0.17,
            border: 0
        );
        $pdf->Cell(
            w: $pageWidth * 0.22,
            h: 0,
            txt: strtoupper($signatoryMember2?->fullname),
            border: 'B',
            ln: 0,
            align: 'C'
        );
        $pdf->Cell(
            w: $pageWidth * 0.17,
            border: 0
        );
        $pdf->Cell(
            w: 0,
            h: 0,
            txt: strtoupper($signatoryMember3?->fullname),
            border: 'B',
            ln: 1,
            align: 'C'
        );

        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->Cell(
            w: $pageWidth * 0.22,
            h: 0,
            txt: 'Member',
            border: 0,
            ln: 0,
            align: 'C'
        );
        $pdf->Cell(
            w: $pageWidth * 0.17,
            border: 0
        );
        $pdf->Cell(
            w: $pageWidth * 0.22,
            h: 0,
            txt: 'Member',
            border: 0,
            ln: 0,
            align: 'C'
        );
        $pdf->Cell(
            w: $pageWidth * 0.17,
            border: 0
        );
        $pdf->Cell(
            w: 0,
            h: 0,
            txt: 'Member',
            border: 0,
            ln: 1,
            align: 'C'
        );

        $pdfBlob = $pdf->Output($filename, 'S');
        $pdfBase64 = base64_encode($pdfBlob);

        return $pdfBase64;
    }
}
