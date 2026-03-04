<?php

namespace App\Http\Controllers\V1\Library;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaxWithholdingResource;
use App\Models\TaxWithholding;
use App\Services\TaxWithholdingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Library - Tax Withholdings
 * APIs for managing tax withholding types used in Disbursement Vouchers
 */
class TaxWithholdingController extends Controller
{
    public function __construct(
        protected TaxWithholdingService $service
    ) {}

    /**
     * List Tax Withholdings
     *
     * Retrieve a list of tax withholding types.
     *
     * @queryParam search string Search by name or type.
     * @queryParam per_page int Number of items per page. Default 50.
     * @queryParam show_all boolean Show all items without pagination. Default false.
     * @queryParam column_sort string Sort field. Default name.
     * @queryParam sort_direction string Sort direction (asc/desc). Default asc.
     * @queryParam paginated boolean Return paginated results. Default true.
     *
     * @response 200 {
     *   "data": [...]
     * }
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only([
            'search',
            'per_page',
            'show_all',
            'column_sort',
            'sort_direction',
            'paginated',
        ]);

        return TaxWithholdingResource::collection($this->service->getAll($filters));
    }

    /**
     * Get Tax Withholding
     *
     * Display the specified tax withholding.
     *
     * @urlParam taxWithholding string required The tax withholding UUID.
     *
     * @response 200 {
     *   "data": {...}
     * }
     * @response 404 {
     *   "message": "Tax withholding not found."
     * }
     */
    public function show(string $id): JsonResponse
    {
        $taxWithholding = $this->service->getById($id);

        if (! $taxWithholding) {
            return response()->json(['message' => 'Tax withholding not found.'], 404);
        }

        return response()->json(['data' => new TaxWithholdingResource($taxWithholding)]);
    }

    /**
     * Create Tax Withholding
     *
     * Store a newly created tax withholding in storage.
     *
     * @bodyParam name string required The display name.
     * @bodyParam is_vat boolean required Whether VAT computation applies.
     * @bodyParam ewt_rate number required Expanded Withholding Tax rate (e.g. 0.01).
     * @bodyParam ptax_rate number required Percentage Tax rate (e.g. 0.03).
     * @bodyParam active boolean Active status. Default true.
     *
     * @response 201 {
     *   "data": {...},
     *   "message": "Tax withholding created successfully."
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'is_vat' => 'required|boolean',
            'ewt_rate' => 'required|numeric|min:0|max:1',
            'ptax_rate' => 'required|numeric|min:0|max:1',
            'active' => 'boolean',
        ]);

        try {
            $taxWithholding = $this->service->create($validated);

            return response()->json([
                'data' => new TaxWithholdingResource($taxWithholding),
                'message' => 'Tax withholding created successfully.',
            ], 201);
        } catch (\Throwable $th) {
            $this->service->logError('Tax withholding creation failed.', $th, $validated);

            return response()->json(['message' => $th->getMessage()], 422);
        }
    }

    /**
     * Update Tax Withholding
     *
     * Update the specified tax withholding in storage.
     *
     * @urlParam taxWithholding string required The tax withholding UUID.
     *
     * @bodyParam name string required The display name.
     * @bodyParam is_vat boolean required Whether VAT computation applies.
     * @bodyParam ewt_rate number required Expanded Withholding Tax rate.
     * @bodyParam ptax_rate number required Percentage Tax rate.
     * @bodyParam active boolean Active status.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Tax withholding updated successfully."
     * }
     */
    public function update(Request $request, TaxWithholding $taxWithholding): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'is_vat' => 'required|boolean',
            'ewt_rate' => 'required|numeric|min:0|max:1',
            'ptax_rate' => 'required|numeric|min:0|max:1',
            'active' => 'boolean',
        ]);

        try {
            $taxWithholding = $this->service->update($taxWithholding->id, $validated);

            return response()->json([
                'data' => new TaxWithholdingResource($taxWithholding),
                'message' => 'Tax withholding updated successfully.',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Tax withholding update failed.', $th, $validated);

            return response()->json(['message' => $th->getMessage()], 422);
        }
    }
}
