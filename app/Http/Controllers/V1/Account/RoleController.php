<?php

namespace App\Http\Controllers\V1\Account;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse | LengthAwarePaginator
    {
        $search = trim($request->get('search', ''));
        $perPage = $request->get('per_page', 50);
        $showAll = filter_var($request->get('show_all', false), FILTER_VALIDATE_BOOLEAN);
        $showInactive = filter_var($request->get('show_inactive', false), FILTER_VALIDATE_BOOLEAN);
        $columnSort = $request->get('column_sort', 'role_name');
        $sortDirection = $request->get('sort_direction', 'desc');
        $paginated = filter_var($request->get('paginated', true), FILTER_VALIDATE_BOOLEAN);

        $roles = Role::query();

        if (!empty($search)) {
            $roles = $roles->where(function($query) use ($search){
                $query->where('role_name', 'ILIKE', "%{$search}%");
            });
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            $roles = $roles->orderBy($columnSort, $sortDirection);
        }

        if ($paginated) {
            return $roles->paginate($perPage);
        } else {
            if (!$showInactive) $roles = $roles->where('active', true);

            $roles = $showAll
                ? $roles->get()
                : $roles = $roles->limit($perPage)->get();

             return response()->json([
                'data' => $roles
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'role_name' => 'required|unique:roles,role_name',
            'permissions' => 'required|string',
            'active' => 'required|in:true,false'
        ]);

        $active = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        try {
            $role = Role::create(array_merge(
                $validated,
                [
                    'permissions' => json_decode($validated['permissions'])
                ]
            ));
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
            'permissions' => 'required|string',
            'active' => 'required|in:true,false'
        ]);

        $active = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        try {
            $role->update(array_merge(
                $validated,
                [
                    'permissions' => json_decode($validated['permissions'])
                ]
            ));
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
    public function delete(Role $role): JsonResponse
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
