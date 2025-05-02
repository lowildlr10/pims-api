<?php

namespace App\Http\Controllers\V1\Library;

use App\Http\Controllers\Controller;
use App\Models\UnitIssue;
use App\Repositories\LogRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class UnitIssueController extends Controller
{
    private LogRepository $logRepository;

    public function __construct(LogRepository $logRepository)
    {
        $this->logRepository = $logRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse | LengthAwarePaginator
    {
        $search = trim($request->get('search', ''));
        $perPage = $request->get('per_page', 5);
        $showAll = filter_var($request->get('show_all', false), FILTER_VALIDATE_BOOLEAN);
        $showInactive = filter_var($request->get('show_inactive', false), FILTER_VALIDATE_BOOLEAN);
        $columnSort = $request->get('column_sort', 'unit_name');
        $sortDirection = $request->get('sort_direction', 'desc');
        $paginated = filter_var($request->get('paginated', true), FILTER_VALIDATE_BOOLEAN);

        $unitIssues = UnitIssue::query();

        if (!empty($search)) {
            $unitIssues = $unitIssues->where(function($query) use ($search){
                $query->whereRaw("CAST(id AS TEXT) = ?", [$search])
                    ->orWhere('unit_name', 'ILIKE', "%{$search}%");
            });
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            switch ($columnSort) {
                case 'unit_name_formatted':
                    $columnSort = 'unit_name';
                    break;
                default:
                    break;
            }

            $unitIssues = $unitIssues->orderBy($columnSort, $sortDirection);
        }

        if ($paginated) {
            return $unitIssues->paginate($perPage);
        } else {
            if (!$showInactive) $unitIssues = $unitIssues->where('active', true);

            $unitIssues = $showAll
                ? $unitIssues->get()
                : $unitIssues = $unitIssues->limit($perPage)->get();

            return response()->json([
                'data' => $unitIssues
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'unit_name' => 'required|unique:unit_issues,unit_name',
            'active' => 'required|in:true,false'
        ]);

        $validated['active'] = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        try {
            $unitIssue = UnitIssue::create($validated);

            $this->logRepository->create([
                'message' => "Unit of issue created successfully.",
                'log_id' => $unitIssue->id,
                'log_module' => 'lib-unit-issue',
                'data' => $unitIssue
            ]);
        } catch (\Throwable $th) {
            $this->logRepository->create([
                'message' => "Unit of issue creation failed. Please try again.",
                'details' => $th->getMessage(),
                'log_module' => 'lib-unit-issue',
                'data' => $validated
            ], isError: true);

            return response()->json([
                'message' => 'Unit of issue creation failed. Please try again.'
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $unitIssue,
                'message' => 'Unit of issue created successfully.'
            ]
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(UnitIssue $unitIssue)
    {
        return response()->json([
            'data' => [
                'data' => $unitIssue
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UnitIssue $unitIssue)
    {
        $validated = $request->validate([
            'unit_name' => 'required|unique:unit_issues,unit_name,' . $unitIssue->id,
            'active' => 'required|in:true,false'
        ]);

        $validated['active'] = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        try {
            $unitIssue->update($validated);

            $this->logRepository->create([
                'message' => "Unit of issue updated successfully.",
                'log_id' => $unitIssue->id,
                'log_module' => 'lib-unit-issue',
                'data' => $unitIssue
            ]);
        } catch (\Throwable $th) {
            $this->logRepository->create([
                'message' => "Unit of issue update failed. Please try again.",
                'details' => $th->getMessage(),
                'log_id' => $unitIssue->id,
                'log_module' => 'lib-unit-issue',
                'data' => $validated
            ], isError: true);

            return response()->json([
                'message' => 'Unit of issue update failed. Please try again.'
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $unitIssue,
                'message' => 'Unit of issue updated successfully.'
            ]
        ]);
    }
}
