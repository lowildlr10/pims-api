<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Services\PrintService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use ValueError;

/**
 * @group Document Printing
 * APIs for generating printable documents
 */
class PrintController extends Controller
{
    public function __construct(
        protected PrintService $service
    ) {}

    /**
     * Generate Document
     *
     * Generate a printable document (PDF) for the specified document type.
     *
     * @urlParam document string required The document type (pr, rfq, aoq, po, iar, obr, dv, ris, are, ics).
     * @urlParam documentId string required The document UUID.
     *
     * @queryParam paper_id string The paper size ID.
     * @queryParam page_orientation string The page orientation (P for portrait, L for landscape). Default: P.
     * @queryParam show_signatures boolean Whether to show signatures. Default: true.
     *
     * @response 200 {
     *   "data": {
     *     "blob": "base64-encoded-pdf-content",
     *     "filename": "document.pdf"
     *   }
     * }
     * @response 422 {
     *   "message": "Paper type not set."
     * }
     */
    public function index(Request $request, string $document, string $documentId): JsonResponse
    {
        $paperId = $request->get('paper_id', '');
        $pageOrientation = $request->get('page_orientation', 'P');
        $showSignatures = filter_var($request->get('show_signatures', true), FILTER_VALIDATE_BOOLEAN);

        try {
            $documentEnum = \App\Enums\DocumentPrintType::from($document);
        } catch (ValueError $e) {
            return response()->json([
                'message' => 'Invalid document type.',
            ], 422);
        }

        $pageConfig = $this->service->getPageConfig($paperId, $pageOrientation, $showSignatures);

        if (empty($pageConfig)) {
            return response()->json([
                'message' => 'Paper type not set.',
            ], 422);
        }

        $data = $this->service->print($document, $documentId, $pageConfig);

        return response()->json([
            'data' => [
                'blob' => $data['blob'],
                'filename' => $data['filename'],
            ],
        ]);
    }
}
