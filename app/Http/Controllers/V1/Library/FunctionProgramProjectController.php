<?php

namespace App\Http\Controllers\V1\Library;

use App\Http\Controllers\Controller;
use App\Http\Resources\FunctionProgramProjectResource;
use App\Services\FunctionProgramProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Library - Function Program Projects
 * APIs for managing function/program/project codes
 */
class FunctionProgramProjectController extends Controller
{
    public function __construct(protected FunctionProgramProjectService $service) {}

    /**
     * List Function/Program/Projects
     *
     * @queryParam search string Search by code or description.
     * @queryParam per_page int Number of items per page. Default 50.
     * @queryParam show_all boolean Show all items. Default false.
     * @queryParam show_inactive boolean Show inactive. Default false.
     * @queryParam column_sort string Sort field. Default code.
     * @queryParam sort_direction string Sort direction. Default desc.
     * @queryParam paginated boolean Return paginated results. Default true.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['search', 'per_page', 'show_all', 'show_inactive', 'column_sort', 'sort_direction', 'paginated']);
        $filters['show_all'] = filter_var($filters['show_all'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $filters['show_inactive'] = filter_var($filters['show_inactive'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $filters['paginated'] = filter_var($filters['paginated'] ?? true, FILTER_VALIDATE_BOOLEAN);

        return FunctionProgramProjectResource::collection($this->service->getAll($filters));
    }

    /**
     * Create Function/Program/Project
     *
     * @bodyParam code string required The code.
     * @bodyParam description string optional The description.
     * @bodyParam active boolean required Whether active. Default true.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|unique:function_program_projects,code',
            'description' => 'nullable',
            'active' => 'required|boolean',
        ]);
        try {
            $fpp = $this->service->create($validated);

            return response()->json([
                'data' => new FunctionProgramProjectResource($fpp),
                'message' => 'FPP created successfully.',
            ], 201);
        } catch (\Throwable $th) {
            $this->service->logError('FPP creation failed.', $th, $validated);

            return response()->json(['message' => 'FPP creation failed. Please try again.'], 422);
        }
    }

    /**
     * Show Function/Program/Project
     *
     * @urlParam id string required The UUID.
     */
    public function show(string $id): JsonResponse
    {
        $fpp = $this->service->getById($id);
        if (! $fpp) {
            return response()->json(['message' => 'FPP not found.'], 404);
        }

        return response()->json(['data' => new FunctionProgramProjectResource($fpp)]);
    }

    /**
     * Update Function/Program/Project
     *
     * @urlParam id string required The UUID.
     *
     * @bodyParam code string required The code.
     * @bodyParam description string optional The description.
     * @bodyParam active boolean required Whether active.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|unique:function_program_projects,code,'.$id,
            'description' => 'nullable',
            'active' => 'required|boolean',
        ]);
        try {
            $fpp = $this->service->update($id, $validated);

            return response()->json([
                'data' => new FunctionProgramProjectResource($fpp),
                'message' => 'FPP updated successfully.',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('FPP update failed.', $th, $validated);

            return response()->json(['message' => 'FPP update failed. Please try again.'], 422);
        }
    }
}
