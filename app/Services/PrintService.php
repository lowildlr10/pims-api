<?php

namespace App\Services;

use App\Enums\DocumentPrintType;
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

class PrintService
{
    public function __construct(
        private PurchaseRequestRepository $purchaseRequestRepository,
        private RequestQuotationRepository $requestQuotationRepository,
        private AbstractQuotationRepository $abstractQuotationRepository,
        private PurchaseOrderRepository $purchaseOrderRepository,
        private InspectionAcceptanceReportRepository $inspectionAcceptanceReportRepository,
        private ObligationRequestRepository $obligationRequestRepository,
        private DisbursementVoucherRepository $disbursementVoucherRepository,
        private InventoryIssuanceRepository $inventoryIssuanceRepository,
        private LogRepository $logRepository
    ) {}

    public function print(string $document, string $documentId, array $pageConfig): array
    {
        $documentEnum = DocumentPrintType::from($document);
        $logModule = $document;

        $data = match ($documentEnum) {
            DocumentPrintType::PR => $this->purchaseRequestRepository->print($pageConfig, $documentId),
            DocumentPrintType::RFQ => $this->requestQuotationRepository->print($pageConfig, $documentId),
            DocumentPrintType::AOQ => $this->abstractQuotationRepository->print($pageConfig, $documentId),
            DocumentPrintType::PO => $this->purchaseOrderRepository->print($pageConfig, $documentId),
            DocumentPrintType::IAR => $this->inspectionAcceptanceReportRepository->print($pageConfig, $documentId),
            DocumentPrintType::OBR => $this->obligationRequestRepository->print($pageConfig, $documentId),
            DocumentPrintType::DV => $this->disbursementVoucherRepository->print($pageConfig, $documentId),
            DocumentPrintType::RIS => $this->inventoryIssuanceRepository->print($pageConfig, $documentId, DocumentPrintType::RIS),
            DocumentPrintType::ARE => $this->inventoryIssuanceRepository->print($pageConfig, $documentId, DocumentPrintType::ARE),
            DocumentPrintType::ICS => $this->inventoryIssuanceRepository->print($pageConfig, $documentId, DocumentPrintType::ICS),
            default => ['success' => false, 'message' => 'Unknown document type.'],
        };

        $logModule = match ($documentEnum) {
            DocumentPrintType::PR => 'pr',
            DocumentPrintType::RFQ => 'rfq',
            DocumentPrintType::AOQ => 'aoq',
            DocumentPrintType::PO => 'po',
            DocumentPrintType::IAR => 'iar',
            DocumentPrintType::OBR => 'obr',
            DocumentPrintType::DV => 'dv',
            default => 'inv-issuance',
        };

        if (! $data['success']) {
            $this->logError($documentId, $document, $data);

            return $data;
        }

        $this->logRepository->create([
            'message' => "Successfully generated the {$data['filename']} document.",
            'log_id' => $documentId,
            'log_module' => $logModule,
            'data' => $data,
        ]);

        return $data;
    }

    public function getPageConfig(string $paperId, string $pageOrientation, bool $showSignatures): ?array
    {
        $paper = PaperSize::find($paperId);

        if (! $paper) {
            return null;
        }

        return [
            'orientation' => $pageOrientation,
            'unit' => $paper->unit,
            'dimension' => [
                floatval($paper->height),
                floatval($paper->width),
            ],
            'show_signatures' => $showSignatures,
        ];
    }

    private function logError(string $documentId, string $documentType, array $data): void
    {
        $this->logRepository->create([
            'message' => 'Failed to generate the document.',
            'details' => $data['message'] ?? 'Unknown error',
            'log_id' => $documentId,
            'log_module' => $documentType,
            'data' => $data,
        ], isError: true);
    }
}
