<?php

namespace App\Http\Controllers\V1\Library;

use App\Http\Controllers\Controller;
use App\Http\Resources\FundingSourceResource;
use App\Services\FundingSourceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Library - Funding Sources
 * APIs for managing funding sources
 */
class FundingSourceController extends Controller
{
    public function __construct(
        protected FundingSourceService $service
    ) {}

    /**
     * List Funding Sources
     *
     * Retrieve a paginated list of funding sources.
     *
     * @queryParam search string Search by title, total cost, or location.
     * @queryParam per_page int Number of items per page. Default 50.
     * @queryParam show_all boolean Show all items without pagination. Default false.
     * @queryParam show_inactive boolean Show inactive funding sources. Default false.
     * @queryParam column_sort string Sort field. Default title.
     * @queryParam sort_direction string Sort direction (asc/desc). Default desc.
     * @queryParam paginated boolean Return paginated results. Default true.
     *
     * @response 200 {
     *   "data": [...],
     *   "meta": {...}
     * }
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only([
            'search',
            'per_page',
            'show_all',
            'show_inactive',
            'column_sort',
            'sort_direction',
            'paginated',
        ]);

        $filters['show_all'] = filter_var($filters['show_all'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $filters['show_inactive'] = filter_var($filters['show_inactive'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $filters['paginated'] = filter_var($filters['paginated'] ?? true, FILTER_VALIDATE_BOOLEAN);

        $fundingSources = $this->service->getAll($filters);

        return FundingSourceResource::collection($fundingSources);
    }

    /**
     * Create Funding Source
     *
     * Create a new funding source.
     *
     * @bodyParam title string required The funding source title.
     * @bodyParam location string required The location name.
     * @bodyParam total_cost numeric required The total cost.
     * @bodyParam active boolean required Whether the funding source is active. Default true.
     *
     * @response 201 {
     *   "data": {
     *     "id": "uuid",
     *     "title": "Funding Source"
     *   },
     *   "message": "Funding source/project created successfully."
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|unique:funding_sources,title',
            'location' => 'required',
            'total_cost' => 'required|numeric',
            'active' => 'required|boolean',
        ]);

        try {
            $fundingSource = $this->service->create($validated);

            return response()->json([
                'data' => new FundingSourceResource($fundingSource),
                'message' => 'Funding source/project created successfully.',
            ], 201);
        } catch (\Throwable $th) {
            $this->service->logError('Funding source/project creation failed.', $th, $validated);

            return response()->json([
                'message' => 'Funding source/project creation failed. Please try again.',
            ], 422);
        }
    }

    /**
     * Show Funding Source
     *
     * Get a specific funding source by ID.
     *
     * @urlParam id string required The funding source UUID.
     *
     * @response 200 {
     *   "data": {
     *     "id": "uuid",
     *     "title": "Funding Source"
     *   }
     * }
     */
    public function show(string $id): JsonResponse
    {
        $fundingSource = $this->service->getById($id);

        if (! $fundingSource) {
            return response()->json(['message' => 'Funding source not found.'], 404);
        }

        return response()->json([
            'data' => new FundingSourceResource($fundingSource),
        ]);
    }

    /**
     * Update Funding Source
     *
     * Update an existing funding source.
     *
     * @urlParam id string required The funding source UUID.
     *
     * @bodyParam title string required The funding source title.
     * @bodyParam location string required The location name.
     * @bodyParam total_cost numeric required The total cost.
     * @bodyParam active boolean required Whether the funding source is active.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Funding source/project updated successfully."
     * }
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|unique:funding_sources,title,'.$id,
            'location' => 'required',
            'total_cost' => 'required|numeric',
            'active' => 'required|boolean',
        ]);

        try {
            $fundingSource = $this->service->update($id, $validated);

            return response()->json([
                'data' => new FundingSourceResource($fundingSource),
                'message' => 'Funding source/project updated successfully.',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Funding source/project update failed.', $th, $validated);

            return response()->json([
                'message' => 'Funding source/project update failed. Please try again.',
            ], 422);
        }
    }
}
