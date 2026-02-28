<?php

namespace App\Http\Controllers\V1\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Resources\AbstractQuotationResource;
use App\Http\Resources\PurchaseRequestResource;
use App\Models\AbstractQuotation;
use App\Services\AbstractQuotationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

/**
 * @group Abstract Quotations
 * APIs for managing abstract of quotations/bids
 */
class AbstractQuotationController extends Controller
{
    public function __construct(
        protected AbstractQuotationService $service
    ) {}

    /**
     * List Abstract Quotations
     *
     * Retrieve a paginated list of purchase requests with abstract quotations.
     *
     * @queryParam search string Search by PR number, purpose, etc.
     * @queryParam per_page int Number of items per page. Default: 50.
     * @queryParam show_all boolean Show all results without pagination. Default: false.
     * @queryParam column_sort string Sort field. Default: pr_no.
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
        $user = Auth::user();
        $result = $this->service->getAll($filters, $user);

        if ($paginated) {
            return PurchaseRequestResource::collection($result);
        }

        $showAll = filter_var($filters['show_all'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $results = $showAll ? $result->get() : $result->limit($filters['per_page'] ?? 50)->get();

        return response()->json([
            'data' => PurchaseRequestResource::collection($results),
        ]);
    }

    /**
     * Get Abstract Quotation
     *
     * Display the specified abstract quotation.
     *
     * @urlParam abstractQuotation string required The abstract quotation UUID.
     *
     * @response 200 {
     *   "data": {...}
     * }
     * @response 404 {
     *   "message": "Abstract quotation not found."
     * }
     */
    public function show(string $id): JsonResponse
    {
        $abstractQuotation = $this->service->getById($id);

        if (! $abstractQuotation) {
            return response()->json(['message' => 'Abstract quotation not found.'], 404);
        }

        return response()->json([
            'data' => new AbstractQuotationResource($abstractQuotation),
        ]);
    }

    /**
     * Update Abstract Quotation
     *
     * Update the specified abstract quotation in storage.
     *
     * @urlParam abstractQuotation string required The abstract quotation UUID.
     *
     * @bodyParam bids_awards_committee_id string required The BAC ID.
     * @bodyParam mode_procurement_id string required The mode of procurement ID.
     * @bodyParam solicitation_no string required The solicitation number.
     * @bodyParam solicitation_date date required The solicitation date.
     * @bodyParam opened_on date nullable The date opened.
     * @bodyParam bac_action string nullable The BAC action.
     * @bodyParam sig_twg_chairperson_id string nullable The TWG chairperson signatory ID.
     * @bodyParam sig_twg_member_1_id string nullable The TWG member 1 signatory ID.
     * @bodyParam sig_twg_member_2_id string nullable The TWG member 2 signatory ID.
     * @bodyParam sig_chairman_id string nullable The chairman signatory ID.
     * @bodyParam sig_vice_chairman_id string nullable The vice chairman signatory ID.
     * @bodyParam sig_member_1_id string nullable The member 1 signatory ID.
     * @bodyParam sig_member_2_id string nullable The member 2 signatory ID.
     * @bodyParam sig_member_3_id string nullable The member 3 signatory ID.
     * @bodyParam items array required The AOQ items.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Abstract of bids and quotation updated successfully."
     * }
     * @response 422 {
     *   "message": "Error message"
     * }
     */
    public function update(Request $request, AbstractQuotation $abstractQuotation): JsonResponse
    {
        $validated = $request->validate([
            'bids_awards_committee_id' => 'required',
            'mode_procurement_id' => 'required',
            'solicitation_no' => 'required|string',
            'solicitation_date' => 'required',
            'opened_on' => 'nullable',
            'bac_action' => 'nullable',
            'sig_twg_chairperson_id' => 'nullable',
            'sig_twg_member_1_id' => 'nullable',
            'sig_twg_member_2_id' => 'nullable',
            'sig_chairman_id' => 'nullable',
            'sig_vice_chairman_id' => 'nullable',
            'sig_member_1_id' => 'nullable',
            'sig_member_2_id' => 'nullable',
            'sig_member_3_id' => 'nullable',
            'items' => 'required|array|min:1',
        ]);

        try {
            $abstractQuotation = $this->service->createOrUpdate($validated, $abstractQuotation);

            return response()->json([
                'data' => new AbstractQuotationResource($abstractQuotation->load('items')),
                'message' => 'Abstract of bids and quotation updated successfully.',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Abstract of bids or quotation update failed.', $th, $validated);

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }

    /**
     * Set Abstract Quotation as Pending
     *
     * Mark the abstract quotation as pending.
     *
     * @urlParam abstractQuotation string required The abstract quotation UUID.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Abstract of bids and quotation successfully marked as Pending."
     * }
     * @response 422 {
     *   "message": "Error message"
     * }
     */
    public function pending(AbstractQuotation $abstractQuotation): JsonResponse
    {
        try {
            $abstractQuotation = $this->service->pending($abstractQuotation);

            return response()->json([
                'data' => new AbstractQuotationResource($abstractQuotation->load('items')),
                'message' => 'Abstract of bids and quotation successfully marked as "Pending".',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Abstract quotation pending failed.', $th, $abstractQuotation->toArray());

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }

    /**
     * Approve Abstract Quotation
     *
     * Mark the abstract quotation as approved.
     *
     * @urlParam abstractQuotation string required The abstract quotation UUID.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Abstract of bids and quotation successfully marked as Approved."
     * }
     * @response 422 {
     *   "message": "Error message"
     * }
     */
    public function approve(AbstractQuotation $abstractQuotation): JsonResponse
    {
        try {
            $abstractQuotation = $this->service->approve($abstractQuotation);

            return response()->json([
                'data' => new AbstractQuotationResource($abstractQuotation->load('items')),
                'message' => 'Abstract of bids and quotation successfully marked as "Approved".',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Abstract quotation approval failed.', $th, $abstractQuotation->toArray());

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }

    /**
     * Revert Abstract Quotation
     *
     * Revert changes for this abstract quotation to draft status.
     *
     * @urlParam abstractQuotation string required The abstract quotation UUID.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Changes for this abstract of bids and quotation successfully reverted."
     * }
     * @response 422 {
     *   "message": "Error message"
     * }
     */
    public function revert(AbstractQuotation $abstractQuotation): JsonResponse
    {
        try {
            $abstractQuotation = $this->service->revert($abstractQuotation);

            return response()->json([
                'data' => new AbstractQuotationResource($abstractQuotation->load('items')),
                'message' => 'Changes for this abstract of bids and quotation successfully reverted.',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Abstract quotation revert failed.', $th, $abstractQuotation->toArray());

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }
}
