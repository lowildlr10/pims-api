<?php

namespace App\Http\Controllers\V1\Account;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Section;
use App\Repositories\LogRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class SectionController extends Controller
{
    private LogRepository $logRepository;

    public function __construct(LogRepository $logRepository)
    {
        $this->logRepository = $logRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse|LengthAwarePaginator
    {
        $user = Auth::user();

        $search = trim($request->get('search', ''));
        $perPage = $request->get('per_page', 50);
        $filterByDepartment = $request->boolean('filter_by_department', false);
        $departmentId = $request->get('department_id', '');
        $showAll = filter_var($request->get('show_all', false), FILTER_VALIDATE_BOOLEAN);
        $showInactive = filter_var($request->get('show_inactive', false), FILTER_VALIDATE_BOOLEAN);
        $columnSort = $request->get('column_sort', 'section_name');
        $sortDirection = $request->get('sort_direction', 'desc');
        $paginated = filter_var($request->get('paginated', true), FILTER_VALIDATE_BOOLEAN);

        $sections = Section::with('department');

        if (! empty($search)) {
            $sections = $sections->where(function ($query) use ($search) {
                $query->where('section_name', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('department', 'department_name', 'ILIKE', "%{$search}%");
            });
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            $sections = $sections->orderBy($columnSort, $sortDirection);
        }

        if ($filterByDepartment && !empty($departmentId)) {
            $sections = $sections->where('department_id', $departmentId);
        } else if ($filterByDepartment && empty($departmentId)) {
            $sections = $sections->limit(0);
        }

        if ($paginated) {
            return $sections->paginate($perPage);
        } else {
            if (! $showInactive) {
                $sections = $sections->where('active', true);
            }

            if ($user->tokenCan('super:*')
                || $user->tokenCan('head:*')
                || $user->tokenCan('supply:*')
                || $user->tokenCan('budget:*')
                || $user->tokenCan('accounting:*')
            ) {
            } else {
                $sections = $sections->where('id', $user->section_id);
            }

            $sections = $showAll
                ? $sections->get()
                : $sections = $sections->limit($perPage)->get();

            foreach ($sections ?? [] as $section) {
                $section->department_section = "{$section->section_name} ({$section->department->department_name})";
            }

            return response()->json([
                'data' => $sections,
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'department' => 'required',
            'section_name' => 'required|string',
            'section_head_id' => 'nullable',
            'active' => 'required|boolean',
        ]);

        $validated['active'] = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        try {
            $section = Section::create($validated);
            $department = Department::find($validated['department']);

            if (! $department->active) {
                Section::where('department_id', $department->id)
                    ->update([
                        'active' => $department->active,
                    ]);
            }

            $this->logRepository->create([
                'message' => 'Section created successfully.',
                'log_id' => $section->id,
                'log_module' => 'account-section',
                'data' => $section,
            ]);
        } catch (\Throwable $th) {
            $this->logRepository->create([
                'message' => 'Section creation failed.',
                'details' => $th->getMessage(),
                'log_module' => 'account-section',
                'data' => $validated,
            ], isError: true);

            return response()->json([
                'message' => 'Section creation failed. Please try again.',
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $section,
                'message' => 'Section created successfully.',
            ],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Section $section)
    {
        $section->load(['department', 'head']);

        return response()->json([
            'data' => [
                'data' => $section,
            ],
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
            'active' => 'required|boolean',
        ]);

        $validated['active'] = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        try {
            $section->update($validated);
            $department = Department::find($validated['department_id']);

            if (! $department->active) {
                Section::where('department_id', $department->id)
                    ->update([
                        'active' => $department->active,
                    ]);
            }

            $this->logRepository->create([
                'message' => 'Section updated successfully.',
                'log_id' => $section->id,
                'log_module' => 'account-section',
                'data' => $section,
            ]);
        } catch (\Throwable $th) {
            $this->logRepository->create([
                'message' => 'Section update failed.',
                'details' => $th->getMessage(),
                'log_id' => $section->id,
                'log_module' => 'account-section',
                'data' => $validated,
            ], isError: true);

            return response()->json([
                'message' => 'Section update failed. Please try again.',
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $section,
                'message' => 'Section updated successfully.',
            ],
        ]);
    }
}
