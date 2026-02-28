<?php

namespace App\Http\Controllers\V1\Account;

use App\Http\Controllers\Controller;
use App\Http\Resources\SectionResource;
use App\Services\SectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Account - Sections
 * APIs for managing sections
 */
class SectionController extends Controller
{
    public function __construct(
        protected SectionService $service
    ) {}

    /**
     * List Sections
     *
     * Retrieve a paginated list of sections.
     *
     * @queryParam search string Search by section name.
     * @queryParam per_page int Number of items per page. Default 50.
     * @queryParam column_sort string Sort field. Default section_name.
     * @queryParam sort_direction string Sort direction (asc/desc). Default desc.
     * @queryParam filter_by_department boolean Filter by department. Default false.
     * @queryParam department_id string Department ID to filter by.
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
            'column_sort',
            'sort_direction',
            'filter_by_department',
            'department_id',
        ]);

        $sections = $this->service->getAll($filters);

        return SectionResource::collection($sections);
    }

    /**
     * Create Section
     *
     * Create a new section.
     *
     * @bodyParam department_id string required The department ID.
     * @bodyParam section_name string required The section name.
     * @bodyParam section_head_id string optional The section head user ID.
     * @bodyParam active boolean required Whether the section is active. Default true.
     *
     * @response 201 {
     *   "data": {
     *     "id": "uuid",
     *     "section_name": "IT Section"
     *   },
     *   "message": "Section created successfully."
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'department_id' => 'required',
            'section_name' => 'required|string',
            'section_head_id' => 'nullable',
            'active' => 'required|boolean',
        ]);

        try {
            $section = $this->service->create($validated);

            return response()->json([
                'data' => new SectionResource($section),
                'message' => 'Section created successfully.',
            ], 201);
        } catch (\Throwable $th) {
            $this->service->logError('Section creation failed.', $th, $validated);

            return response()->json([
                'message' => 'Section creation failed. Please try again.',
            ], 422);
        }
    }

    /**
     * Show Section
     *
     * Get a specific section by ID.
     *
     * @urlParam id string required The section UUID.
     *
     * @response 200 {
     *   "data": {
     *     "id": "uuid",
     *     "section_name": "IT Section"
     *   }
     * }
     */
    public function show(string $id): JsonResponse
    {
        $section = $this->service->getById($id);

        if (! $section) {
            return response()->json(['message' => 'Section not found.'], 404);
        }

        return response()->json([
            'data' => new SectionResource($section),
        ]);
    }

    /**
     * Update Section
     *
     * Update an existing section.
     *
     * @urlParam id string required The section UUID.
     *
     * @bodyParam department_id string required The department ID.
     * @bodyParam section_name string required The section name.
     * @bodyParam section_head_id string optional The section head user ID.
     * @bodyParam active boolean required Whether the section is active.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Section updated successfully."
     * }
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'department_id' => 'required',
            'section_name' => 'required|string',
            'section_head_id' => 'nullable',
            'active' => 'required|boolean',
        ]);

        try {
            $section = $this->service->update($id, $validated);

            return response()->json([
                'data' => new SectionResource($section),
                'message' => 'Section updated successfully.',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Section update failed.', $th, $validated);

            return response()->json([
                'message' => 'Section update failed. Please try again.',
            ], 422);
        }
    }
}
