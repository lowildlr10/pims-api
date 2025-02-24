<?php

namespace App\Repositories;

use App\Helpers\FileHelper;
use App\Interfaces\RequestQuotationRepositoryInterface;
use App\Models\Company;
use App\Models\Log;
use App\Models\PurchaseRequestItem;
use App\Models\RequestQuotation;
use TCPDF;
use TCPDF_FONTS;

class RequestQuotationRepository implements RequestQuotationRepositoryInterface
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

    public function print(array $pageConfig, string $rfqId): array
    {
        try {
            $company = Company::first();
            $rfq = RequestQuotation::with([
                'purchase_request:id,pr_no,funding_source_id,purpose',
                'purchase_request.funding_source:id,title,location_id',
                'purchase_request.funding_source.location:id,location_name',

                'supplier:id,supplier_name,address,tin_no,phone,telephone',
                'signatory_approval:id,user_id',
                'signatory_approval.user:id,firstname,middlename,lastname,allow_signature,signature',
                'signatory_approval.detail' => function ($query) {
                    $query->where('document', 'rfq')
                        ->where('signatory_type', 'approval');
                },
                'canvassers',
                'canvassers.user:id,firstname,lastname',
                'items' => function($query) {
                    $query->orderBy(
                        PurchaseRequestItem::select('item_sequence')
                            ->whereColumn(
                                'request_quotation_items.pr_item_id', 'purchase_request_items.id'
                            ),
                        'asc'
                    );
                },
                'items.pr_item:id,item_sequence,quantity,description,stock_no',
            ])->find($rfqId);

            $filename = "RFQ-{$rfq->rfq_no}.pdf";
            $blob = $this->generateRequestQuotationDoc($filename, $pageConfig, $rfq, $company);

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

    private function generateRequestQuotationDoc(
        string $filename, array $pageConfig, RequestQuotation $data, Company $company
    ): string
    {
        $purchaseRequest = $data->purchase_request;
        $fundingSource = $purchaseRequest->funding_source;
        $supplier = $data->supplier;
        $signatoryUser = $data->signatory_approval->user;
        $canvassers = [];

        for ($canvasserIndex = 0; $canvasserIndex < count($data->canvassers); $canvasserIndex++) {
            $canvassers[] = strtoupper($data->canvassers[$canvasserIndex]->user->fullname);
        }

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

        // try {
        //     if ($company->company_logo) {
        //         $imagePath = FileHelper::getPublicPath(
        //             $company->company_logo
        //         );
        //         $pdf->Image(
        //             $imagePath,
        //             $x + ($x * 0.15),
        //             $y - ($y * 0.04),
        //             w: $pageConfig['orientation'] === 'P'
        //                 ? $x - ($x * 0.04)
        //                 : $y + ($y * 0.4),
        //             type: 'PNG',
        //             resize: true,
        //             dpi: 500,
        //         );
        //     }
        // } catch (\Throwable $th) {}

        if ($data->signed_type === 'lce') {
            $pdf->setTextColor(0, 0, 0);
            $pdf->SetLineStyle(['width' => $pdf->getPageWidth() * 0.002, 'color' => [0, 0, 0]]);
        } else {
            $pdf->setTextColor(0, 32, 96);
            $pdf->SetLineStyle(['width' => $pdf->getPageWidth() * 0.002, 'color' => [0, 32, 96]]);
        }

        $pdf->SetFont($this->fontArial, '', $data->signed_type === 'lce' ? 10 : 14);
        $pdf->Cell(0, 0, "MUNICIPALITY OF {$company->municipality}", 0, 1, 'C');
        $pdf->Cell(0, 0, 'BIDS AND AWARDS COMMITTEE', 0, 1, 'C');

        if ($data->signed_type === 'lce') {
            $pdf->Ln();
        } else {
            $pdf->SetFont($this->fontArial, '', 10);
            $pdf->setCellHeightRatio(2.2);
            $pdf->Cell($pageWidth * 0.215, 0, 'Purchase Request No.: ', 0, 0, 'L');
            $pdf->SetFont($this->fontArial, 'U', 10);
            $pdf->Cell(0, 0, $purchaseRequest->pr_no, 0, 1, 'L');
            $pdf->SetFont($this->fontArial, '', 10);

            $pdf->SetFont($this->fontArial, '', 10);
            $pdf->setCellHeightRatio(2);
            $pdf->Cell($pageWidth * 0.215, 0, 'Name of Project: ', 0, 0, 'L');
            $pdf->SetFont($this->fontArial, 'U', 10);
            $pdf->Cell(0, 0, $fundingSource->title, 0, 1, 'L');
            $pdf->SetFont($this->fontArial, '', 10);

            $pdf->SetFont($this->fontArial, '', 10);
            $pdf->setCellHeightRatio(2);
            $pdf->Cell($pageWidth * 0.215, 0, 'Location of the Project: ', 0, 0, 'L');
            $pdf->SetFont($this->fontArial, 'U', 10);
            $pdf->Cell(0, 0, $fundingSource->location->location_name, 0, 1, 'L');
        }

        $pdf->setCellHeightRatio(1.6);

        $pdf->SetFont($this->fontArialBold, 'B', $data->signed_type === 'lce' ? 12 : 14);
        $pdf->Cell(0, 0, 'REQUEST FOR QUOTATION', 0, 1, 'C');

        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->Cell($pageWidth * 0.4, 0, '', 0, 0, 'L');
        $pdf->Cell($pageWidth * 0.3, 0, 'Date: ', 0, 0, 'R');
        $pdf->Cell(0, 0, date_format(date_create($data->rfq_date), 'F j, Y'), 'B', 1, 'L');

        $pdf->Cell($pageWidth * 0.4, 0, '', 0, 0, 'L');
        $pdf->Cell(
            $pageWidth * 0.3, 0, $data->signed_type === 'lce'
                ? 'Quotation No.: ' : 'Solicitation No.: ', 0, 0, 'R'
        );
        $pdf->Cell(0, 0, $data->rfq_no, 'B', 1, 'L');

        if ($data->signed_type === 'bac') {
            $pdf->SetFont($this->fontArialBold, 'B', 10);
        }

        $pdf->Cell(
            $pageWidth * ($data->signed_type === 'lce' ? 0.24 : 0.165), 0,
            $data->signed_type === 'lce' ? 'Company Name/Supplier: ' : 'Company Name: ',
            0, 0, 'L'
        );

        if ($data->signed_type === 'bac') {
            $pdf->SetFont($this->fontArial, '', 10);
        }

        $pdf->Cell(
            $pageWidth * ($data->signed_type === 'lce' ? 0.3 : 0.375), 0,
            isset($supplier->supplier_name) ? $supplier->supplier_name : '',
            'B', 0, 'L'
        );
        $pdf->Cell(0, 0, '', 0, 1, 'L');

        $pdf->Cell($pageWidth * 0.095, 0, 'Address: ', 0, 0, 'L');
        $pdf->Cell(
            $pageWidth * 0.445, 0,
            isset($supplier->address) ? $supplier->address : '',
            'B', 0, 'L'
        );
        $pdf->Cell(0, 0, '', 0, 1, 'L');
        $pdf->Ln();

        $pdf->setCellHeightRatio(1.25);
        $pdf->Cell(0, 0, 'Sir / Madam:', 0, 1, 'L');
        $pdf->MultiCell(
            0, 0,
            "        Please quote your lowest price on the item/s listed below, " .
            'subject to the conditions herein and submit your quotation duly ' .
            'signed by your representative.',
            0, 'L'
        );
        $pdf->setCellHeightRatio(1.6);

        $pdf->SetFont($this->fontArial, '', $data->signed_type === 'lce' ? 8 : 5);
        $pdf->Cell($pageWidth * 0.19, 0, '', 0, 0, 'L');
        $pdf->Cell(
            $pageWidth * 0.45, 0,
            $data->signed_type === 'lce' ? '(Date and Time of Opening)' : '',
            0, 0, 'C'
        );
        $pdf->Cell(0, 0, '', 0, 1, 'C');

        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->Cell($pageWidth * 0.19, 0, '', 0, 0, 'L');
        $pdf->Cell(
            $pageWidth * 0.45, 0,
            $data->signed_type === 'lce'
                ? date_format(date_create($data->opening_dt), 'F j, Y g:iA')
                : '',
            0, 0, 'C'
        );

        if ($data->signed_type === 'lce') {
            $pdf->SetFont($this->fontArialBold, 'B', 10);
        } else {
            $pdf->SetFont($this->fontArialBold, 'B', 12);
            $pdf->setTextColor(0, 0, 0);
            $pdf->SetLineStyle(['width' => $pdf->getPageWidth() * 0.002, 'color' => array(0, 0, 0)]);
        }

        $pdf->Cell(0, 0, strtoupper($signatoryUser->fullname), 'B', 1, 'C');

        if ($data->signed_type === 'lce') {
            $pdf->SetFont($this->fontArial, '', 10);
        } else {
            $pdf->SetFont($this->fontArial, '', 12);
        }

        $pdf->Cell($pageWidth * 0.19, 0, '', 0, 0, 'L');
        $pdf->Cell($pageWidth * 0.45, 0, '', 0, 0, 'C');
        $pdf->Cell(0, 0, $data->signatory_approval->detail->position, 0, 1, 'C');

        if ($data->signed_type === 'bac') {
            $pdf->setTextColor(0, 32, 96);
            $pdf->SetLineStyle(['width' => $pdf->getPageWidth() * 0.002, 'color' => [0, 32, 96]]);
        }

        $htmlTable = '
            <table border="1" cellpadding="2"><thead><tr>
                <th
                    style="'. (
                        $data->signed_type === 'lce'
                            ? 'border-left-color:#000;border-top-color:#000;
                                border-right-color:#000;border-bottom-color:#000;'
                            : 'border-left-color:#C00000;border-top-color:#C00000;
                                border-right-color:#C00000;border-bottom-color:#C00000;'
                    ) .'"
                    width="'. ($data->signed_type === 'lce' ? '9%' : '10%') .'"
                    align="center"
                >Item No.</th>
                <th
                    style="'. (
                        $data->signed_type === 'lce'
                            ? 'border-left-color:#000;border-top-color:#000;
                                border-right-color:#000;border-bottom-color:#000;'
                            : 'border-left-color:#C00000;border-top-color:#C00000;
                                border-right-color:#C00000;border-bottom-color:#C00000;'
                    ) .'"
                    width="11%"
                    align="center"
                >Qty</th>
                <th
                    style="'. (
                        $data->signed_type === 'lce'
                            ? 'border-left-color:#000;border-top-color:#000;
                                border-right-color:#000;border-bottom-color:#000;'
                            : 'border-left-color:#C00000;border-top-color:#C00000;
                                border-right-color:#C00000;border-bottom-color:#C00000;'
                    ) .'"
                    width="'. ($data->signed_type === 'lce' ? '44%' : '45%') .'"
                    align="center"
                >Item and Description</th>';

        if ($data->signed_type === 'lce') {
            $htmlTable .= '
                <th
                    style="'. (
                        $data->signed_type === 'lce'
                            ? 'border-left-color:#000;border-top-color:#000;
                                border-right-color:#000;border-bottom-color:#000;'
                            : 'border-left-color:#C00000;border-top-color:#C00000;
                                border-right-color:#C00000;border-bottom-color:#C00000;'
                    ) .'"
                    width="10%"
                    align="center"
                >Brand/<br />Model</th>';
        }

        $htmlTable .= '
                <th
                    style="'. (
                        $data->signed_type === 'lce'
                            ? 'border-left-color:#000;border-top-color:#000;
                                border-right-color:#000;border-bottom-color:#000;'
                            : 'border-left-color:#C00000;border-top-color:#C00000;
                                border-right-color:#C00000;border-bottom-color:#C00000;'
                    ) .'"
                    width="'. ($data->signed_type === 'lce' ? '13%' : '17%') .'"
                    align="center"
                >Unit Cost</th>
                <th
                    style="'. (
                        $data->signed_type === 'lce'
                            ? 'border-left-color:#000;border-top-color:#000;
                                border-right-color:#000;border-bottom-color:#000;'
                            : 'border-left-color:#C00000;border-top-color:#C00000;
                                border-right-color:#C00000;border-bottom-color:#C00000;'
                    ) .'"
                    width="'. ($data->signed_type === 'lce' ? '13%' : '17%') .'"
                    align="center"
                >Total Cost</th>
            </tr></thead></table>
        ';

        if ($data->signed_type === 'lce') {
            $pdf->SetFont($this->fontArialBold, 'B', 12);
        } else {
            $pdf->setTextColor(192, 0, 0);
            $pdf->SetFont($this->fontArial, '', 12);
        }

        $pdf->setCellHeightRatio(1.25);
        $pdf->writeHTML($htmlTable, ln: false);
        $pdf->Ln(0);

        $htmlTable = '<table border="1" cellpadding="2"><tbody>';

        foreach ($data->items ?? [] as $item) {
            if (!$item->included) continue;

            $description = trim(str_replace("\r", '<br />', $item->pr_item->description));
            $description = str_replace("\n", '<br />', $description);

            $brandModel = trim(str_replace("\r", '<br />', $item->brand_model));
            $brandModel = str_replace("\n", '<br />', $brandModel);

            $htmlTable .= '
                <tr>
                    <td
                        style="'. (
                            $data->signed_type === 'lce'
                                ? 'border-left-color:#000;border-top-color:#000;
                                    border-right-color:#000;border-bottom-color:#000;'
                                : 'border-left-color:#C00000;border-top-color:#C00000;
                                    border-right-color:#C00000;border-bottom-color:#C00000;'
                        ) .'"
                        width="'. ($data->signed_type === 'lce' ? '9%' : '10%') .'"
                        align="center"
                    >'. $item->pr_item->stock_no .'</td>
                    <td
                        style="'. (
                            $data->signed_type === 'lce'
                                ? 'border-left-color:#000;border-top-color:#000;
                                    border-right-color:#000;border-bottom-color:#000;'
                                : 'border-left-color:#C00000;border-top-color:#C00000;
                                    border-right-color:#C00000;border-bottom-color:#C00000;'
                        ) .'"
                        width="11%"
                        align="center"
                    >'. $item->pr_item->quantity .'</td>
                    <td
                        style="'. (
                            $data->signed_type === 'lce'
                                ? 'border-left-color:#000;border-top-color:#000;
                                    border-right-color:#000;border-bottom-color:#000;'
                                : 'border-left-color:#C00000;border-top-color:#C00000;
                                    border-right-color:#C00000;border-bottom-color:#C00000;'
                        ) .'"
                        width="'. ($data->signed_type === 'lce' ? '44%' : '45%') .'"
                        align="left"
                    >'. $description .'</td>';

            if ($data->signed_type === 'lce') {
                $htmlTable .= '
                    <th
                        style="'. (
                            $data->signed_type === 'lce'
                                ? 'border-left-color:#000;border-top-color:#000;
                                    border-right-color:#000;border-bottom-color:#000;'
                                : 'border-left-color:#C00000;border-top-color:#C00000;
                                    border-right-color:#C00000;border-bottom-color:#C00000;'
                        ) .'"
                        width="10%"
                        align="left"
                    >'. $brandModel .'</th>';
            }

            $htmlTable .= '
                    <td
                        style="'. (
                            $data->signed_type === 'lce'
                                ? 'border-left-color:#000;border-top-color:#000;
                                    border-right-color:#000;border-bottom-color:#000;'
                                : 'border-left-color:#C00000;border-top-color:#C00000;
                                    border-right-color:#C00000;border-bottom-color:#C00000;'
                        ) .'"
                        width="'. ($data->signed_type === 'lce' ? '13%' : '17%') .'"
                        align="right"
                    >'. number_format($item->unit_cost, 2) .'</td>
                    <td
                        style="'. (
                            $data->signed_type === 'lce'
                                ? 'border-left-color:#000;border-top-color:#000;
                                    border-right-color:#000;border-bottom-color:#000;'
                                : 'border-left-color:#C00000;border-top-color:#C00000;
                                    border-right-color:#C00000;border-bottom-color:#C00000;'
                        ) .'"
                        width="'. ($data->signed_type === 'lce' ? '13%' : '17%') .'"
                        align="right"
                    >'. number_format($item->total_cost, 2) .'</td>
                </tr>
            ';
        }

        $htmlTable .= '</tbody></table>';

        if ($data->signed_type === 'bac') {
            $pdf->setTextColor(0, 0, 0);
        }

        $pdf->SetFont($this->fontArial, '', $data->signed_type === 'lce' ? 12 : 11);
        $pdf->writeHTML($htmlTable, ln: false);

        $purpose = trim(str_replace("\r", '<br />', $purchaseRequest->purpose));
        $purpose = str_replace("\n", '<br />', $purpose);

        $htmlTable = '<table border="1" cellpadding="2"><tbody>
            <tr>
                <td
                    style="'. (
                        $data->signed_type === 'lce'
                            ? 'border-left-color:#000;border-top-color:#000;
                                border-right-color:#000;border-bottom-color:#000;'
                            : 'border-left-color:#C00000;border-top-color:#C00000;
                                border-right-color:#C00000;border-bottom-color:#C00000;'
                    ) .'"
                    width="'. ($data->signed_type === 'lce' ? '9%' : '10%') .'"
                    align="center"
                >Purpose</td>
                <td
                    style="'. (
                        $data->signed_type === 'lce'
                            ? 'border-left-color:#000;border-top-color:#000;
                                border-right-color:#000;border-bottom-color:#000;'
                            : 'border-left-color:#C00000;border-top-color:#C00000;
                                border-right-color:#C00000;border-bottom-color:#C00000;'
                    ) .'"
                    width="'. ($data->signed_type === 'lce' ? '91%' : '90%') .'"
                    align="left"
                >'. ($data->signed_type === 'lce' ? $purpose : "<strong>{$purpose}</strong>") .'</td>
            </tr>
        </tbody></table>';

        if ($data->signed_type === 'bac') {
            $pdf->setTextColor(0, 32, 96);
        }

        $pdf->SetFont('helvetica', '', 10);
        $pdf->writeHTML($htmlTable, ln: false);
        $pdf->Ln(0);

        $pdf->setCellHeightRatio(2);
        $pdf->Cell($pageWidth * 0.08, 0, '');

        if ($data->signed_type === 'lce') {
            $pdf->SetFont($this->fontArialBold, 'B', 12);
            $pdf->setTextColor(68, 84, 106);
        } else {
            $pdf->SetFont($this->fontArial, '', 12);
        }

        $pdf->Cell(0, 0, 'CONDITIONS:', 0, 1, 'L');
        $pdf->setCellHeightRatio(1.25);

        $pdf->SetFont($this->fontArial, '', $data->signed_type === 'lce' ? 9 : 10);
        $pdf->Cell($pageWidth * 0.08, 0, '');
        $pdf->Cell($pageWidth * 0.11, 0, '1.', 0, 0, 'R');
        $pdf->Cell(0, 0, 'All entries must be legibly printed/written.', 0, 1, 'L');

        $pdf->Cell($pageWidth * 0.08, 0, '');
        $pdf->Cell($pageWidth * 0.11, 0, '2.', 0, 0, 'R');
        $pdf->Cell($pageWidth * 0.5, 0, 'All taxes are inclusive. Please check whether you are', 0, 0, 'L');
        $pdf->SetLineStyle(['width' => $pdf->getPageWidth() * 0.0005]);
        $pdf->setCellHeightRatio(1.6);
        $pdf->SetFont('zapfdingbats', 'B', $data->signed_type === 'lce' ? 9 : 10);
        $pdf->Cell(
            $pageWidth * 0.05, 0,
            $data->vat_registered === true ? '3' : '',
            1, 0, 'C'
        );
        $pdf->SetFont($this->fontArial, '', $data->signed_type === 'lce' ? 9 : 10);
        $pdf->Cell(0, 0, 'VAT Registered    OR', 0, 1, 'L');

        $pdf->SetFont($this->fontArial, '', 3);
        $pdf->Cell(0, 0, '', 0, 1, 'L');
        $pdf->SetFont($this->fontArial, '', $data->signed_type === 'lce' ? 9 : 10);

        $pdf->Cell($pageWidth * 0.08, 0, '');
        $pdf->Cell($pageWidth * 0.11, 0, '', 0, 0, 'R');
        $pdf->Cell($pageWidth * 0.5, 0, '', 0, 0, 'L');
        $pdf->SetLineStyle(['width' => $pdf->getPageWidth() * 0.001]);
        $pdf->SetFont('zapfdingbats', 'B', $data->signed_type === 'lce' ? 9 : 10);
        $pdf->setTextColor(0, 0, 0);
        $pdf->Cell(
            $pageWidth * 0.05, 0,
            $data->vat_registered === false ? '3' : '',
            1, 0, 'C'
        );

        if ($data->signed_type === 'lce') {
            $pdf->setTextColor(68, 84, 106);
        } else {
            $pdf->setTextColor(0, 32, 96);
        }

        $pdf->SetFont($this->fontArial, '', $data->signed_type === 'lce' ? 9 : 10);
        $pdf->Cell(0, 0, 'Non VAT Registered ', 0, 1, 'L');

        $pdf->SetFont($this->fontArial, '', 3);
        $pdf->Cell(0, 0, '', 0, 1, 'L');
        $pdf->SetFont($this->fontArial, '', $data->signed_type === 'lce' ? 9 : 10);
        $pdf->setCellHeightRatio(1.25);

        $pdf->SetLineStyle(['width' => $pdf->getPageWidth() * 0.002]);
        $pdf->Cell($pageWidth * 0.08, 0, '');
        $pdf->setTextColor(0, 0, 0);
        $pdf->Cell($pageWidth * 0.11, 0, '3.', 0, 0, 'R');

        if ($data->signed_type === 'lce') {
            $pdf->setTextColor(68, 84, 106);
        } else {
            $pdf->setTextColor(0, 32, 96);
        }

        $pdf->MultiCell(
            0, 0,
            'Warranty shall be for a period of three (3) months for supplies '.
            'and materials, one (1) year for equipment from date of acceptance '.
            'by the procuring entity.   (If applicable)',
            0, 'L', ln: 1
        );

        $pdf->Cell($pageWidth * 0.08, 0, '');
        $pdf->Cell($pageWidth * 0.11, 0, '4.', 0, 0, 'R');
        $pdf->MultiCell(
            0, 0,
            'PhilGEPS registration certificate shall be attached upon submission '.
            'of the quotation, if applicable.',
            0, 'L', ln: 1
        );

        $pdf->Cell($pageWidth * 0.08, 0, '');
        $pdf->Cell($pageWidth * 0.11, 0, '5.', 0, 0, 'R');
        $pdf->MultiCell(
            0, 0,
            'Bidders shall submit original brochures showing certifications of '.
            'the products being offered.',
            0, 'L', ln: 1
        );

        $pdf->Cell($pageWidth * 0.08, 0, '');
        $pdf->Cell($pageWidth * 0.11, 0, '6.', 0, 0, 'R');
        $pdf->MultiCell(
            0, 0,
            'The Procuring entity reserves the right to waive any defects in the '.
            'tender or offer as well as the right to accept the bid most advantageous '.
            'to the Municipal Government',
            0, 'L', ln: 1
        );

        if ($data->signed_type === 'lce') {
            $pdf->Cell($pageWidth * 0.08, 0, '');
            $pdf->Cell($pageWidth * 0.11, 0, '7.', 0, 0, 'R');
            $pdf->Cell(0, 0, 'Pick Up Price.', 0, 1, 'L');

            $pdf->Cell($pageWidth * 0.08, 0, '');
            $pdf->Cell($pageWidth * 0.11, 0, '8.', 0, 0, 'R');
            $pdf->Cell(0, 0, 'With Delivery', 0, 1, 'L');

            $pdf->setTextColor(0, 0, 0);
        }

        $pdf->SetFont($this->fontArialBold, 'B', 3);
        $pdf->Cell(0, 0, '', ln: 1);

        $pdf->setCellHeightRatio(1.15);
        $pdf->SetFont($this->fontArialBold, 'B', 10);
        $pdf->Cell($pageWidth * 0.08, 0, '');
        $pdf->Cell($pageWidth * 0.095, 0, 'TIN NO.:', 0, 0, 'L');
        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->Cell(
            $pageWidth * 0.355, 0,
            isset($supplier->tin_no) ? $supplier->tin_no : '',
            'B', 0, 'L'
        );
        $pdf->SetFont($this->fontArialBold, 'B', 10);
        $pdf->Cell($pageWidth * 0.08, 0, '', 0, 0, 'L');
        $pdf->Cell(0, 0, 'CANVASSERS:', 0, 1, 'L');

        $pdf->SetFont($this->fontArialBold, 'B', 5);
        $pdf->Cell(0, 0, '', ln: 1);

        $pdf->setCellHeightRatio(1.15);
        $pdf->SetFont($this->fontArialBold, 'B', 10);
        $pdf->Cell($pageWidth * 0.08, 0, '');
        $pdf->Cell($pageWidth * 0.175, 0, 'TEL. NO./CP NO.:', 0, 0, 'L');
        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->Cell(
            $pageWidth * 0.275, 0,
            isset($supplier->phone)
                ? $supplier->phone
                : (isset($supplier->telphone) ? $supplier->telphone : ''),
            'B', 0, 'L'
        );
        $pdf->SetFont($this->fontArialBold, 'B', 9);
        $pdf->Cell($pageWidth * 0.08, 0, '', 0, 0, 'L');
        $pdf->Cell(0, 0, '', 0, 1, 'L');

        $pdf->setCellHeightRatio(1.6);
        $pdf->SetFont($this->fontArialBold, 'B', 10);
        $pdf->Cell($pageWidth * 0.615, 0, '');
        $pdf->Cell(0, 0, strtoupper($company->company_name), 0, 1, 'C');

        $pdf->setCellHeightRatio(1.25);
        $pdf->SetFont($this->fontArial, '', $data->signed_type === 'lce' ? 9 : 11);
        $pdf->Cell($pageWidth * 0.55, 0, '');
        $pdf->Cell($pageWidth * 0.065, 0, '');
        $pdf->MultiCell(0, 0, 'Requisitioning Office/Representative of End-User', 'T', ln: 1, align: 'C');

        $pdf->setCellHeightRatio(1.6);
        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->Cell($pageWidth * 0.08, 0, '');
        $pdf->Cell(
            $pageWidth * 0.455, 0,
            $data->signed_type === 'lce' ? 'Name of Establishment' : 'FULL NAME and SIGNATURE',
            'T', 0, 'C'
        );
        $pdf->Cell($pageWidth * 0.08, 0, '');
        $pdf->Cell(0, 0, '', 0, 1, 'C');

        $pdf->setCellHeightRatio(1.25);
        $pdf->Cell($pageWidth * 0.08, 0, '');
        $pdf->Cell($pageWidth * 0.455, 0, '', '', 0, 'C');
        $pdf->Cell($pageWidth * 0.08, 0, '');
        $pdf->SetFont($this->fontArialBold, 'B', 10);
        $pdf->MultiCell(0, 0,
            $data->signed_type === 'bac' ? implode(" / \n", $canvassers) : '',
            0, ln: 1, align: 'C'
        );

        $pdf->setCellHeightRatio(1.6);
        $pdf->SetFont($this->fontArial, '', 10);
        $pdf->Cell($pageWidth * 0.08, 0, '');
        $pdf->Cell(
            $pageWidth * 0.455, 0,
            $data->signed_type === 'lce' ? 'Address' : 'Business Address',
            'T', 0, 'C'
        );
        $pdf->Cell($pageWidth * 0.08, 0, '');
        $pdf->Cell(
            0, 0,
            $data->signed_type === 'bac' ? 'Canvasser' : '',
            $data->signed_type === 'bac' ? 'T' : 0,
            1, 'C'
        );

        if ($data->signed_type === 'lce') {
            $pdf->SetFont($this->fontArial, '', 10);
            $pdf->Cell($pageWidth * 0.08, 0, '');
            $pdf->Cell($pageWidth * 0.455, 0, '', '', 0, 'C');
            $pdf->Cell($pageWidth * 0.08, 0, '');
            $pdf->Cell(0, 0, '', 0, 1, 'C');

            $pdf->SetFont($this->fontArial, '', 10);
            $pdf->Cell($pageWidth * 0.08, 0, '');
            $pdf->Cell($pageWidth * 0.455, 0, 'Signature over printed name', 'T', 0, 'C');
            $pdf->Cell($pageWidth * 0.08, 0, '');
            $pdf->Cell(0, 0, '', 0, 1, 'C');
        }

        $pdfBlob = $pdf->Output($filename, 'S');
        $pdfBase64 = base64_encode($pdfBlob);

        return $pdfBase64;
    }
}
