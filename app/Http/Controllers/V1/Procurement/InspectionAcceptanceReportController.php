<?php

namespace App\Http\Controllers\V1\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Resources\InspectionAcceptanceReportResource;
use App\Models\InspectionAcceptanceReport;
use App\Services\InspectionAcceptanceReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Inspection and Acceptance Reports
 * APIs for managing inspection and acceptance reports
 */
class InspectionAcceptanceReportController extends Controller
{
    public function __construct(
        protected InspectionAcceptanceReportService $service
    ) {}

    /**
     * List Inspection and Acceptance Reports
     *
     * Retrieve a paginated list of inspection and acceptance reports.
     *
     * @queryParam search string Search by IAR number, invoice number, etc.
     * @queryParam per_page int Number of items per page. Default: 50.
     * @queryParam show_all boolean Show all results without pagination. Default: false.
     * @queryParam column_sort string Sort field. Default: iar_no.
     * @queryParam sort_direction string Sort direction (asc/desc). Default: desc.
     * @queryParam paginated boolean Return paginated results. Default: true.
     *
     * @response 200 {
     *   "data": [...],
     *   "links": {...},
     *   "meta": {...}
     * }
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $filters = $request->only([
            'search',
            'per_page',
            'show_all',
            'column_sort',
            'sort_direction',
            'paginated',
        ]);

        $paginated = filter_var($filters['paginated'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $result = $this->service->getAll($filters);

        if ($paginated) {
            return InspectionAcceptanceReportResource::collection($result);
        }

        return response()->json([
            'data' => InspectionAcceptanceReportResource::collection($result),
        ]);
    }

    /**
     * Get Inspection and Acceptance Report
     *
     * Display the specified inspection and acceptance report.
     *
     * @urlParam id string required The inspection and acceptance report UUID.
     *
     * @response 200 {
     *   "data": {...}
     * }
     * @response 404 {
     *   "message": "Inspection and Acceptance Report not found."
     * }
     */
    public function show(string $id): JsonResponse
    {
        $iar = $this->service->getById($id);

        if (! $iar) {
            return response()->json(['message' => 'Inspection and Acceptance Report not found.'], 404);
        }

        return response()->json([
            'data' => new InspectionAcceptanceReportResource($iar),
        ]);
    }

    /**
     * Update Inspection and Acceptance Report
     *
     * Update the specified inspection and acceptance report.
     *
     * @urlParam id string required The inspection and acceptance report UUID.
     *
     * @bodyParam iar_date date required The IAR date.
     * @bodyParam invoice_no string required The invoice number.
     * @bodyParam invoice_date date required The invoice date.
     * @bodyParam inspected_date date nullable The inspected date.
     * @bodyParam inspected boolean nullable Whether items have been inspected.
     * @bodyParam sig_inspection_id string nullable The signatory for inspection ID.
     * @bodyParam acceptance_id string nullable The acceptance user ID.
     * @bodyParam received_date date nullable The received date.
     * @bodyParam acceptance_completed boolean nullable Whether acceptance is completed.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Inspection and acceptance report updated successfully."
     * }
     * @response 422 {
     *   "message": "Inspection and acceptance report update failed."
     * }
     */
    public function update(Request $request, InspectionAcceptanceReport $inspectionAcceptanceReport): JsonResponse
    {
        $validated = $request->validate([
            'iar_date' => 'required',
            'invoice_no' => 'required',
            'invoice_date' => 'required',
            'inspected_date' => 'nullable',
            'inspected' => 'nullable|boolean',
            'sig_inspection_id' => 'nullable|exists:signatories,id',
            'acceptance_id' => 'nullable|exists:users,id',
            'received_date' => 'nullable',
            'acceptance_completed' => 'nullable|boolean',
        ]);

        $validated['inspected'] = isset($validated['inspected'])
            ? $request->boolean('inspected')
            : null;
        $validated['acceptance_completed'] = isset($validated['acceptance_completed'])
            ? $request->boolean('acceptance_completed')
            : null;

        try {
            $iar = $this->service->update($inspectionAcceptanceReport, $validated);

            return response()->json([
                'data' => new InspectionAcceptanceReportResource($iar),
                'message' => 'Inspection and acceptance report updated successfully.',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Inspection and acceptance report update failed.', $th, $validated);

            return response()->json([
                'message' => 'Inspection and acceptance report update failed. Please try again.',
            ], 422);
        }
    }

    /**
     * Mark as Pending for Inspection
     *
     * Mark the inspection and acceptance report as pending for inspection.
     *
     * @urlParam id string required The inspection and acceptance report UUID.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Inspection & acceptance report successfully marked as pending for inspection."
     * }
     * @response 422 {
     *   "message": "Failed to set the inspection & acceptance report to pending for inspection."
     * }
     */
    public function pending(InspectionAcceptanceReport $inspectionAcceptanceReport): JsonResponse
    {
        try {
            $iar = $this->service->pending($inspectionAcceptanceReport);

            return response()->json([
                'data' => new InspectionAcceptanceReportResource($iar),
                'message' => 'Inspection & acceptance report successfully marked as pending for inspection.',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Inspection & acceptance report failed to mark as pending for inspection.', $th, $inspectionAcceptanceReport->toArray());

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }

    /**
     * Mark as Inspected
     *
     * Mark the inspection and acceptance report as inspected and create obligation request.
     *
     * @urlParam id string required The inspection and acceptance report UUID.
     *
     * @bodyParam items array required The inspected items (required for PO type).
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Inspection & acceptance report successfully marked as inspected."
     * }
     * @response 422 {
     *   "message": "Failed to set the inspection & acceptance report to inspected."
     * }
     */
    public function inspect(Request $request, InspectionAcceptanceReport $inspectionAcceptanceReport): JsonResponse
    {
        $inspectionAcceptanceReport->load([
            'purchase_order',
            'purchase_order.supplier',
        ]);

        $items = $inspectionAcceptanceReport->purchase_order->document_type === 'po'
            ? $request->validate(['items' => 'required|array|min:1'])['items']
            : [];

        try {
            $iar = $this->service->inspect($inspectionAcceptanceReport, $items);

            return response()->json([
                'data' => new InspectionAcceptanceReportResource($iar),
                'message' => 'Inspection & acceptance report successfully marked as inspected.',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Inspection & acceptance report failed to mark as inspected.', $th, $inspectionAcceptanceReport->toArray());

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }
}
