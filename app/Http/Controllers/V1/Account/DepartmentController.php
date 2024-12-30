<?php

namespace App\Http\Controllers\V1\Account;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse | LengthAwarePaginator
    {
        $search = trim($request->get('search', ''));
        $perPage = $request->get('per_page', 5);
        $columnSort = $request->get('column_sort', 'department_name');
        $sortDirection = $request->get('sort_direction', 'desc');
        $paginated = filter_var($request->get('paginated', true), FILTER_VALIDATE_BOOLEAN);

        $departments = Department::query()->with([
            'sections:id,section_name,department_id,active',
            'sections.head:id,firstname,lastname',
            'head:id,firstname,lastname'
        ]);

        if (!empty($search)) {
            $departments = $departments->where(function($query) use ($search){
                $query->where('department_name', 'ILIKE', "%{$search}%");
            });
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            $departments = $departments->orderBy($columnSort, $sortDirection);
        }

        if ($paginated) {
            return $departments->paginate($perPage);
        } else {
            $departments = $departments->limit($perPage)->get();

            return response()->json([
                'data' => $departments
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'department_name' => 'required|unique:departments,department_name',
            'department_head_id' => 'nullable',
            'active' => 'required|in:true,false'
        ]);

        $active = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        try {
            $department = Department::create($validated);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Department creation failed. Please try again.'
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $department,
                'message' => 'Department created successfully.'
            ]
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Department $department): JsonResponse
    {
        return response()->json([
            'data' => [
                'data' => $department
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Department $department): JsonResponse
    {
        $validated = $request->validate([
            'department_name' => 'required|unique:departments,department_name,' . $department->id,
            'department_head_id' => 'nullable',
            'active' => 'required|boolean'
        ]);

        $active = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        try {
            $department->update($validated);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Department update failed. Please try again.'
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $department,
                'message' => 'Department updated successfully.'
            ]
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function delete(Department $department): JsonResponse
    {
        try {
            $department->delete();
        } catch (\Throwable $th) {
            return response()->json([
                'message' =>
                    $th->getCode() === '23000' ?
                        'Failed to delete department. There are records connected to this record.' :
                        'Unknown error occured. Please try again.',
            ], 422);
        }

        return response()->json([
            'message' => 'Department deleted successfully',
        ]);
    }
}
