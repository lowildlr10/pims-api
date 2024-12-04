<?php

namespace App\Http\Controllers\V1\Account;

use App\Http\Controllers\Controller;
use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $search = trim($request->get('search', ''));
        $perPage = $request->get('per_page', 50);
        $columnSort = $request->get('column_sort', 'department_name');
        $sortDirection = $request->get('sort_direction', 'desc');
        $paginated = filter_var($request->get('paginated', true), FILTER_VALIDATE_BOOLEAN);

        $sections = Section::query();

        if (!empty($search)) {
            $sections = $sections->where(function($query) use ($search){
                $query->where('section_name', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('department', 'department_name', 'ILIKE', "%{$search}%");
            });
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            $sections = $sections->orderBy($columnSort, $sortDirection);
        }

        if ($paginated) {
            $sections = $sections->paginate($perPage);
        } else {
            $sections = $sections->limit($perPage)->get();
        }

        return response()->json([
            'data' => $sections
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'department_id' => 'required',
            'section_name' => 'required|string',
            'section_head_id' => 'nullable',
            'active' => 'required|in:true,false'
        ]);

        $active = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        try {
            $section = Section::create($validated);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Section creation failed. Please try again.'
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $section,
                'message' => 'Section created successfully.'
            ]
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Section $section)
    {
        return response()->json([
            'data' => [
                'data' => $section
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Section $section)
    {
        $validated = $request->validate([
            'department_id' => 'required',
            'section_name' => 'required|string',
            'section_head_id' => 'nullable',
            'active' => 'required|in:true,false'
        ]);

        $active = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        try {
            $section->update($validated);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Section update failed. Please try again.'
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $section,
                'message' => 'Section updated successfully.'
            ]
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function delete(Section $section): JsonResponse
    {
        try {
            $section->delete();
        } catch (\Throwable $th) {
            return response()->json([
                'message' =>
                    $th->getCode() === '23000' ?
                        'Failed to delete section. There are records connected to this record.' :
                        'Unknown error occured. Please try again.',
            ], 422);
        }

        return response()->json([
            'message' => 'Section deleted successfully',
        ]);
    }
}
