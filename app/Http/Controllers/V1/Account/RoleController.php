<?php

namespace App\Http\Controllers\V1\Account;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $search = trim($request->get('search', ''));
        $perPage = $request->get('per_page', 50);
        $columnSort = $request->get('column_sort', 'firstname');
        $sortDirection = $request->get('sort_direction', 'desc');

        $roles = Role::query();

        if (!empty($search)) {
            $roles = $roles->where(function($query) use ($search){
                $query->where('role_name', 'ILIKE', "%{$search}%");
            });
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            $roles = $roles->orderBy($columnSort, $sortDirection);
        }

        $roles = $roles->paginate($perPage);

        return response()->json([
            'data' => $roles
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'role_name' => 'required|unique|string',
            'permissions' => 'required|array'
        ]);

        try {
            $role = Role::create($validated);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Role creation failed. Please try again.'
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $role,
                'message' => 'Role created successfully.'
            ]
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role): JsonResponse
    {
        return response()->json([
            'data' => [
                'data' => $role
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role): JsonResponse
    {
        $validated = $request->validate([
            'role_name' => 'required|unique:roles,role_name,' . $role->id,
            'permissions' => 'required|array'
        ]);

        try {
            $role->update($validated);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Role update failed. Please try again.'
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $role,
                'message' => 'Role updated successfully.'
            ]
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role): JsonResponse
    {
        try {
            $role->delete();
        } catch (\Throwable $th) {
            return response()->json([
                'message' =>
                    $th->getCode() === '23000' ?
                        'Failed to delete role. There are records connected to this record.' :
                        'Unknown error occured. Please try again.',
            ], 422);
        }

        return response()->json([
            'message' => 'Role deleted successfully',
        ]);
    }
}
