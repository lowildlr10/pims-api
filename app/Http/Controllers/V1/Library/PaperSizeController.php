<?php

namespace App\Http\Controllers\V1\Library;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaperSizeResource;
use App\Services\PaperSizeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Library - Paper Sizes
 * APIs for managing paper sizes
 */
class PaperSizeController extends Controller
{
    public function __construct(
        protected PaperSizeService $service
    ) {}

    /**
     * List Paper Sizes
     *
     * Retrieve a paginated list of paper sizes.
     *
     * @queryParam search string Search by paper type, unit, width, or height.
     * @queryParam per_page int Number of items per page. Default 50.
     * @queryParam show_all boolean Show all items without pagination. Default false.
     * @queryParam column_sort string Sort field. Default paper_type.
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
            'column_sort',
            'sort_direction',
            'paginated',
        ]);

        $filters['show_all'] = filter_var($filters['show_all'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $filters['paginated'] = filter_var($filters['paginated'] ?? true, FILTER_VALIDATE_BOOLEAN);

        $paperSizes = $this->service->getAll($filters);

        return PaperSizeResource::collection($paperSizes);
    }

    /**
     * Create Paper Size
     *
     * Create a new paper size.
     *
     * @bodyParam paper_type string required The paper type name.
     * @bodyParam unit string required The unit (mm, cm, in).
     * @bodyParam width numeric required The width.
     * @bodyParam height numeric required The height.
     *
     * @response 201 {
     *   "data": {
     *     "id": "uuid",
     *     "paper_type": "A4"
     *   },
     *   "message": "Paper type created successfully."
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'paper_type' => 'required|unique:paper_sizes,paper_type',
            'unit' => 'required|in:mm,cm,in',
            'width' => 'required|numeric',
            'height' => 'required|numeric',
        ]);

        try {
            $paperSize = $this->service->create($validated);

            return response()->json([
                'data' => new PaperSizeResource($paperSize),
                'message' => 'Paper type created successfully.',
            ], 201);
        } catch (\Throwable $th) {
            $this->service->logError('Paper type creation failed.', $th, $validated);

            return response()->json([
                'message' => 'Paper type creation failed. Please try again.',
            ], 422);
        }
    }

    /**
     * Show Paper Size
     *
     * Get a specific paper size by ID.
     *
     * @urlParam id string required The paper size UUID.
     *
     * @response 200 {
     *   "data": {
     *     "id": "uuid",
     *     "paper_type": "A4"
     *   }
     * }
     */
    public function show(string $id): JsonResponse
    {
        $paperSize = $this->service->getById($id);

        if (! $paperSize) {
            return response()->json(['message' => 'Paper type not found.'], 404);
        }

        return response()->json([
            'data' => new PaperSizeResource($paperSize),
        ]);
    }

    /**
     * Update Paper Size
     *
     * Update an existing paper size.
     *
     * @urlParam id string required The paper size UUID.
     *
     * @bodyParam paper_type string required The paper type name.
     * @bodyParam unit string required The unit (mm, cm, in).
     * @bodyParam width numeric required The width.
     * @bodyParam height numeric required The height.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Paper type updated successfully."
     * }
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'paper_type' => 'required|unique:paper_sizes,paper_type,'.$id,
            'unit' => 'required|in:mm,cm,in',
            'width' => 'required|numeric',
            'height' => 'required|numeric',
        ]);

        try {
            $paperSize = $this->service->update($id, $validated);

            return response()->json([
                'data' => new PaperSizeResource($paperSize),
                'message' => 'Paper type updated successfully.',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Paper type update failed.', $th, $validated);

            return response()->json([
                'message' => 'Paper type update failed. Please try again.',
            ], 422);
        }
    }
}
