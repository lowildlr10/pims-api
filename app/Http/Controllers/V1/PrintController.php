<?php

namespace App\Http\Controllers\V1;

use App\Enums\DocumentPrintType;
use App\Http\Controllers\Controller;
use App\Models\PaperSize;
use App\Repositories\AbstractQuotationRepository;
use App\Repositories\DisbursementVoucherRepository;
use App\Repositories\InspectionAcceptanceReportRepository;
use App\Repositories\InventoryIssuanceRepository;
use App\Repositories\LogRepository;
use App\Repositories\ObligationRequestRepository;
use App\Repositories\PurchaseOrderRepository;
use App\Repositories\PurchaseRequestRepository;
use App\Repositories\RequestQuotationRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use ValueError;

class PrintController extends Controller
{
    private LogRepository $logRepository;

    private PurchaseRequestRepository $purchaseRequestRepository;

    private RequestQuotationRepository $requestQuotationRepository;

    private AbstractQuotationRepository $abstractQuotationRepository;

    private PurchaseOrderRepository $purchaseOrderRepository;

    private InspectionAcceptanceReportRepository $inspectionAcceptanceReportRepository;

    private ObligationRequestRepository $obligationRequestRepository;

    private DisbursementVoucherRepository $disbursementVoucherRepository;

    private InventoryIssuanceRepository $inventoryIssuanceRepository;

    public function __construct(
        LogRepository $logRepository,
        PurchaseRequestRepository $purchaseRequestRepository,
        RequestQuotationRepository $requestQuotationRepository,
        AbstractQuotationRepository $abstractQuotationRepository,
        PurchaseOrderRepository $purchaseOrderRepository,
        InspectionAcceptanceReportRepository $inspectionAcceptanceReportRepository,
        ObligationRequestRepository $obligationRequestRepository,
        DisbursementVoucherRepository $disbursementVoucherRepository,
        InventoryIssuanceRepository $inventoryIssuanceRepository
    ) {
        $this->logRepository = $logRepository;
        $this->purchaseRequestRepository = $purchaseRequestRepository;
        $this->requestQuotationRepository = $requestQuotationRepository;
        $this->abstractQuotationRepository = $abstractQuotationRepository;
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->inspectionAcceptanceReportRepository = $inspectionAcceptanceReportRepository;
        $this->obligationRequestRepository = $obligationRequestRepository;
        $this->disbursementVoucherRepository = $disbursementVoucherRepository;
        $this->inventoryIssuanceRepository = $inventoryIssuanceRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, string $document, string $documentId): JsonResponse
    {
        $paperId = $request->get('paper_id', '');
        $pageOrientation = $request->get('page_orientation', 'P');
        $showSignatures = filter_var($request->get('show_signatures', true), FILTER_VALIDATE_BOOLEAN);
        $logModule = $document;

        $paperData = $this->getPaperData($paperId);
        $data = [
            'success' => false,
        ];

        if (empty($paperData)) {
            return response()->json([
                'message' => 'Paper type not set.',
            ], 422);
        }

        $pageConfig = [
            'orientation' => $pageOrientation,
            'unit' => $paperData['unit'],
            'dimension' => $paperData['dimension'],
            'show_signatures' => $showSignatures,
        ];

        try {
            $documentEnum = DocumentPrintType::from($document);
        } catch (ValueError $e) {
            return response()->json([
                'message' => 'Invalid document type.',
            ], 422);
        }

        switch ($documentEnum) {
            case DocumentPrintType::PR:
                $data = $this->purchaseRequestRepository->print($pageConfig, $documentId);
                $logModule = 'pr';
                break;

            case DocumentPrintType::RFQ:
                $data = $this->requestQuotationRepository->print($pageConfig, $documentId);
                $logModule = 'rfq';
                break;

            case DocumentPrintType::AOQ:
                $data = $this->abstractQuotationRepository->print($pageConfig, $documentId);
                $logModule = 'aoq';
                break;

            case DocumentPrintType::PO:
                $data = $this->purchaseOrderRepository->print($pageConfig, $documentId);
                $logModule = 'po';
                break;

            case DocumentPrintType::IAR:
                $data = $this->inspectionAcceptanceReportRepository->print($pageConfig, $documentId);
                $logModule = 'iar';
                break;

            case DocumentPrintType::OBR:
                $data = $this->obligationRequestRepository->print($pageConfig, $documentId);
                $logModule = 'obr';
                break;

            case DocumentPrintType::DV:
                $data = $this->disbursementVoucherRepository->print($pageConfig, $documentId);
                $logModule = 'dv';
                break;

            case DocumentPrintType::RIS:
                $data = $this->inventoryIssuanceRepository->print($pageConfig, $documentId, DocumentPrintType::RIS);
                $logModule = 'inv-issuance';
                break;

            case DocumentPrintType::ARE:
                $data = $this->inventoryIssuanceRepository->print($pageConfig, $documentId, DocumentPrintType::ARE);
                $logModule = 'inv-issuance';
                break;

            case DocumentPrintType::ICS:
                $data = $this->inventoryIssuanceRepository->print($pageConfig, $documentId, DocumentPrintType::ICS);
                $logModule = 'inv-issuance';
                break;

            default:
                return response()->json([
                    'message' => 'Unknown error occurred. Please try again.',
                ], 422);
        }

        if (! $data['success']) {
            $this->logError($documentId, $documentEnum, $data);

            return response()->json([
                'data' => [
                    'blob' => $data['blob'],
                    'filename' => $data['filename'],
                ],
            ]);
        }

        $this->logRepository->create([
            'message' => "Successfully generated the {$data['filename']} document.",
            'log_id' => $documentId,
            'log_module' => $logModule,
            'data' => $data,
        ]);

        return response()->json([
            'data' => [
                'blob' => $data['blob'],
                'filename' => $data['filename'],
            ],
        ]);
    }

    private function getPaperData(string $paperId): ?array
    {
        $paper = PaperSize::find($paperId);

        if (! $paper) {
            return null;
        }

        $measurementUnit = $paper->unit;
        $dimension = [
            floatval($paper->height),
            floatval($paper->width),
        ];

        return [
            'unit' => $measurementUnit,
            'dimension' => $dimension,
        ];
    }

    private function logError(string $documentId, DocumentPrintType $documentType, array $data): void
    {
        $this->logRepository->create([
            'message' => 'Failed to generated the document.',
            'details' => $data['message'],
            'log_id' => $documentId,
            'log_module' => $documentType,
            'data' => $data,
        ], isError: true);
    }
}
