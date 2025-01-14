<?php

namespace App\Http\Controllers\V1\Library;

use App\Models\ProcurementMode;
use Illuminate\Http\Request;

class ProcurementModeController extends Controller
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
        $columnSort = $request->get('column_sort', 'code');
        $sortDirection = $request->get('sort_direction', 'desc');
        $paginated = filter_var($request->get('paginated', true), FILTER_VALIDATE_BOOLEAN);

        $procurementModes = ProcurementMode::query();

        if (!empty($search)) {
            $procurementModes = $procurementModes->where(function($query) use ($search){
                $query->where('mode_name', 'ILIKE', "%{$search}%");
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

            $procurementModes = $procurementModes->orderBy($columnSort, $sortDirection);
        }

        if ($paginated) {
            return $procurementModes->paginate($perPage);
        } else {
            if (!$showInactive) $procurementModes = $procurementModes->where('active', true);

            $procurementModes = $showAll
                ? $procurementModes->get()
                : $procurementModes = $procurementModes->limit($perPage)->get();

            return response()->json([
                'data' => $procurementModes
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
    public function show(ProcurementMode $procurementMode)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProcurementMode $procurementMode)
    {
        //
    }
}
