<?php

namespace App\Http\Controllers\V1;

use App\Enums\DocumentPrintType;
use App\Http\Controllers\Controller;
use App\Models\PaperSize;
use App\Repositories\AbstractQuotationRepository;
use App\Repositories\LogRepository;
use App\Repositories\PurchaseOrderRepository;
use App\Repositories\PurchaseRequestRepository;
use App\Repositories\RequestQuotationRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use function Laravel\Prompts\error;

class PrintController extends Controller
{
    private LogRepository $logRepository;
    private PurchaseRequestRepository $purchaseRequestRepository;
    private RequestQuotationRepository $requestQuotationRepository;
    private AbstractQuotationRepository $abstractQuotationRepository;
    private PurchaseOrderRepository $purchaseOrderRepository;

    public function __construct(
        LogRepository $logRepository,
        PurchaseRequestRepository $purchaseRequestRepository,
        RequestQuotationRepository $requestQuotationRepository,
        AbstractQuotationRepository $abstractQuotationRepository,
        PurchaseOrderRepository $purchaseOrderRepository
    ) {
        $this->logRepository = $logRepository;
        $this->purchaseRequestRepository = $purchaseRequestRepository;
        $this->requestQuotationRepository = $requestQuotationRepository;
        $this->abstractQuotationRepository = $abstractQuotationRepository;
        $this->purchaseOrderRepository = $purchaseOrderRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, string $document, string $documentId): JsonResponse
    {
        $paperId = $request->get('paper_id', '');
        $pageOrientation = $request->get('page_orientation', 'P');
        $showSignatures = filter_var($request->get('show_signatures', true), FILTER_VALIDATE_BOOLEAN);

        $paperData = $this->getPaperData($paperId);

        if (empty($paperData)) {
            return response()->json([
                'message' => 'Paper type not set.'
            ], 422);
        }

        $pageConfig = [
            'orientation' => $pageOrientation,
            'unit' => $paperData['unit'],
            'dimension' => $paperData['dimension'],
            'show_signatures' => $showSignatures
        ];

        try {
            $documentEnum = DocumentPrintType::from($document);
        } catch (ValueError $e) {
            return response()->json([
                'message' => 'Invalid document type.'
            ], 422);
        }

        switch ($documentEnum) {
            case DocumentPrintType::PR:
                $data = $this->purchaseRequestRepository->print($pageConfig, $documentId);

                if (!$data['success']) {
                    $this->logError($documentId, $documentEnum, $data);

                    return response()->json([
                        'data' => [
                            'blob' => $data['blob'],
                            'filename' => $data['filename']
                        ]
                    ]);
                }

                $this->logRepository->create([
                    'message' => "Succefully generated the {$data['filename']} document.",
                    'log_id' => $documentId,
                    'log_module' => 'pr',
                    'data' => $data
                ]);

                return response()->json([
                    'data' => [
                        'blob' => $data['blob'],
                        'filename' => $data['filename']
                    ]
                ]);

            case DocumentPrintType::RFQ:
                $data = $this->requestQuotationRepository->print($pageConfig, $documentId);

                if (!$data['success']) {
                    $this->logError($documentId, $documentEnum, $data);

                    return response()->json([
                        'data' => [
                            'blob' => $data['blob'],
                            'filename' => $data['filename']
                        ]
                    ]);
                }

                $this->logRepository->create([
                    'message' => "Succefully generated the {$data['filename']} document.",
                    'log_id' => $documentId,
                    'log_module' => 'rfq',
                    'data' => $data
                ]);

                return response()->json([
                    'data' => [
                        'blob' => $data['blob'],
                        'filename' => $data['filename']
                    ]
                ]);

            case DocumentPrintType::AOQ:
                $data = $this->abstractQuotationRepository->print($pageConfig, $documentId);

                if (!$data['success']) {
                    $this->logError($documentId, $documentEnum, $data);

                    return response()->json([
                        'data' => [
                            'blob' => $data['blob'],
                            'filename' => $data['filename']
                        ]
                    ]);
                }

                $this->logRepository->create([
                    'message' => "Succefully generated the {$data['filename']} document.",
                    'log_id' => $documentId,
                    'log_module' => 'aoq',
                    'data' => $data
                ]);

                return response()->json([
                    'data' => [
                        'blob' => $data['blob'],
                        'filename' => $data['filename']
                    ]
                ]);

            case DocumentPrintType::PO:
                $data = $this->purchaseOrderRepository->print($pageConfig, $documentId);

                if (!$data['success']) {
                    $this->logError($documentId, $documentEnum, $data);

                    return response()->json([
                        'data' => [
                            'blob' => $data['blob'],
                            'filename' => $data['filename']
                        ]
                    ]);
                }

                $this->logRepository->create([
                    'message' => "Succefully generated the {$data['filename']} document.",
                    'log_id' => $documentId,
                    'log_module' => 'po',
                    'data' => $data
                ]);

                return response()->json([
                    'data' => [
                        'blob' => $data['blob'],
                        'filename' => $data['filename']
                    ]
                ]);

            case DocumentPrintType::IAR:
                return response()->json([
                    'data' => [
                        'blob' => 'test',
                        'filename' => 'test.pdf'
                    ]
                ]);

            case DocumentPrintType::ORS:
                return response()->json([
                    'data' => [
                        'blob' => 'test',
                        'filename' => 'test.pdf'
                    ]
                ]);

            case DocumentPrintType::DV:
                return response()->json([
                    'data' => [
                        'blob' => 'test',
                        'filename' => 'test.pdf'
                    ]
                ]);

            case DocumentPrintType::RIS:
                return response()->json([
                    'data' => [
                        'blob' => 'test',
                        'filename' => 'test.pdf'
                    ]
                ]);

            case DocumentPrintType::ARE:
                return response()->json([
                    'data' => [
                        'blob' => 'test',
                        'filename' => 'test.pdf'
                    ]
                ]);

            case DocumentPrintType::ICS:
                return response()->json([
                    'data' => [
                        'blob' => 'test',
                        'filename' => 'test.pdf'
                    ]
                ]);

            case DocumentPrintType::SUMMARY:
                return response()->json([
                    'data' => [
                        'blob' => 'test',
                        'filename' => 'test.pdf'
                    ]
                ]);

            case DocumentPrintType::PAYMENT:
                return response()->json([
                    'data' => [
                        'blob' => 'test',
                        'filename' => 'test.pdf'
                    ]
                ]);

            default:
                return response()->json([
                    'message' => 'Unknown error occurred. Please try again.'
                ], 422);
        }
    }

    private function getPaperData(string $paperId): array | NULL
    {
        $paper = PaperSize::find($paperId);

        if (!$paper) {
            return NULL;
        }

        $measurementUnit = $paper->unit;
        $dimension = [
            floatval($paper->height),
            floatval($paper->width)
        ];

        return [
            'unit' => $measurementUnit,
            'dimension' => $dimension
        ];
    }

    private function logError(string $documentId, DocumentPrintType $documentType, array $data): void
    {
        $this->logRepository->create([
            'message' => "Failed to generated the document.",
            'details' => $data['message'],
            'log_id' => $documentId,
            'log_module' => $documentType,
            'data' => $data
        ], isError: true);
    }
}
