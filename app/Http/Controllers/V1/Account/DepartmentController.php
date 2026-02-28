<?php

namespace App\Http\Controllers\V1\Account;

use App\Http\Controllers\Controller;
use App\Http\Resources\DepartmentResource;
use App\Services\DepartmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Account - Departments
 * APIs for managing departments
 */
class DepartmentController extends Controller
{
    public function __construct(
        protected DepartmentService $service
    ) {}

    /**
     * List Departments
     *
     * Retrieve a paginated list of departments.
     *
     * @queryParam search string Search by department name.
     * @queryParam per_page int Number of items per page. Default 50.
     * @queryParam column_sort string Sort field. Default department_name.
     * @queryParam sort_direction string Sort direction (asc/desc). Default desc.
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
        ]);

        $departments = $this->service->getAll($filters);

        return DepartmentResource::collection($departments);
    }

    /**
     * Create Department
     *
     * Create a new department.
     *
     * @bodyParam department_name string required The department name.
     * @bodyParam department_head_id string optional The department head user ID.
     * @bodyParam active boolean required Whether the department is active. Default true.
     *
     * @response 201 {
     *   "data": {
     *     "id": "uuid",
     *     "department_name": "IT Department"
     *   },
     *   "message": "Department created successfully."
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'department_name' => 'required|unique:departments,department_name',
            'department_head_id' => 'nullable',
            'active' => 'required|boolean',
        ]);

        try {
            $department = $this->service->create($validated);

            return response()->json([
                'data' => new DepartmentResource($department),
                'message' => 'Department created successfully.',
            ], 201);
        } catch (\Throwable $th) {
            $this->service->logError('Department creation failed.', $th, $validated);

            return response()->json([
                'message' => 'Department creation failed. Please try again.',
            ], 422);
        }
    }

    /**
     * Show Department
     *
     * Get a specific department by ID.
     *
     * @urlParam id string required The department UUID.
     *
     * @response 200 {
     *   "data": {
     *     "id": "uuid",
     *     "department_name": "IT Department"
     *   }
     * }
     */
    public function show(string $id): JsonResponse
    {
        $department = $this->service->getById($id);

        if (! $department) {
            return response()->json(['message' => 'Department not found.'], 404);
        }

        return response()->json([
            'data' => new DepartmentResource($department),
        ]);
    }

    /**
     * Update Department
     *
     * Update an existing department.
     *
     * @urlParam id string required The department UUID.
     *
     * @bodyParam department_name string required The department name.
     * @bodyParam department_head_id string optional The department head user ID.
     * @bodyParam active boolean required Whether the department is active.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Department updated successfully."
     * }
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'department_name' => 'required|unique:departments,department_name,'.$id,
            'department_head_id' => 'nullable',
            'active' => 'required|boolean',
        ]);

        try {
            $department = $this->service->update($id, $validated);

            return response()->json([
                'data' => new DepartmentResource($department),
                'message' => 'Department updated successfully.',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Department update failed.', $th, $validated);

            return response()->json([
                'message' => 'Department update failed. Please try again.',
            ], 422);
        }
    }
}
