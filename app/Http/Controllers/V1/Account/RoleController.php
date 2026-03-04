<?php

namespace App\Http\Controllers\V1\Account;

use App\Http\Controllers\Controller;
use App\Http\Resources\RoleResource;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Account - Roles
 * APIs for managing roles
 */
class RoleController extends Controller
{
    public function __construct(
        protected RoleService $service
    ) {}

    /**
     * List Roles
     *
     * Retrieve a paginated list of roles.
     *
     * @queryParam search string Search by role name.
     * @queryParam per_page int Number of items per page. Default 50.
     * @queryParam column_sort string Sort field. Default role_name.
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

        $roles = $this->service->getAll($filters);

        return RoleResource::collection($roles);
    }

    /**
     * Create Role
     *
     * Create a new role.
     *
     * @bodyParam role_name string required The role name.
     * @bodyParam permissions string required JSON array of permissions.
     * @bodyParam active boolean required Whether the role is active. Default true.
     *
     * @response 201 {
     *   "data": {
     *     "id": "uuid",
     *     "role_name": "Admin"
     *   },
     *   "message": "Role created successfully."
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'role_name' => 'required|unique:roles,role_name',
            'permissions' => 'required|string',
            'active' => 'required|boolean',
        ]);

        try {
            $role = $this->service->create($validated);

            return response()->json([
                'data' => new RoleResource($role),
                'message' => 'Role created successfully.',
            ], 201);
        } catch (\Throwable $th) {
            $this->service->logError('Role creation failed.', $th, $validated);

            return response()->json([
                'message' => 'Role creation failed. Please try again.',
            ], 422);
        }
    }

    /**
     * Show Role
     *
     * Get a specific role by ID.
     *
     * @urlParam id string required The role UUID.
     *
     * @response 200 {
     *   "data": {
     *     "id": "uuid",
     *     "role_name": "Admin"
     *   }
     * }
     */
    public function show(string $id): JsonResponse
    {
        $role = $this->service->getById($id);

        if (! $role) {
            return response()->json(['message' => 'Role not found.'], 404);
        }

        return response()->json([
            'data' => new RoleResource($role),
        ]);
    }

    /**
     * Update Role
     *
     * Update an existing role.
     *
     * @urlParam id string required The role UUID.
     *
     * @bodyParam role_name string required The role name.
     * @bodyParam permissions string required JSON array of permissions.
     * @bodyParam active boolean required Whether the role is active.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Role updated successfully."
     * }
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'role_name' => 'required|unique:roles,role_name,'.$id,
            'permissions' => 'required|string',
            'active' => 'required|boolean',
        ]);

        try {
            $role = $this->service->update($id, $validated);

            return response()->json([
                'data' => new RoleResource($role),
                'message' => 'Role updated successfully.',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Role update failed.', $th, $validated);

            return response()->json([
                'message' => 'Role update failed. Please try again.',
            ], 422);
        }
    }
}
