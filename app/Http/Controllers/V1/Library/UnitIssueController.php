<?php

namespace App\Http\Controllers\V1\Library;

use App\Models\UnitIssue;
use Illuminate\Http\Request;

class UnitIssueController extends Controller
{
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
                $query->where('unit_name', 'ILIKE', "%{$search}%");
            });
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            // switch ($columnSort) {
            //     case 'headfullname':
            //         $columnSort = 'department_head_id';
            //         break;
            //     case 'department_name_formatted':
            //         $columnSort = 'department_name';
            //         break;
            //     default:
            //         break;
            // }

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
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(UnitIssue $unitIssue)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UnitIssue $unitIssue)
    {
        //
    }
}
