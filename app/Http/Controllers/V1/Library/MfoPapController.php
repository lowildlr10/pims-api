<?php

namespace App\Http\Controllers\V1\Library;

use App\Models\MfoPap;
use Illuminate\Http\Request;

class MfoPapController extends Controller
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

        $mfoPaps = MfoPap::query();

        if (!empty($search)) {
            $mfoPaps = $mfoPaps->where(function($query) use ($search){
                $query->where('code', 'ILIKE', "%{$search}%")
                    ->orWhere('description', 'ILIKE', "%{$search}%");
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

            $mfoPaps = $mfoPaps->orderBy($columnSort, $sortDirection);
        }

        if ($paginated) {
            return $mfoPaps->paginate($perPage);
        } else {
            if (!$showInactive) $mfoPaps = $mfoPaps->where('active', true);

            $mfoPaps = $showAll
                ? $mfoPaps->get()
                : $mfoPaps = $mfoPaps->limit($perPage)->get();

            return response()->json([
                'data' => $mfoPaps
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
    public function show(MfoPap $mfoPap)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MfoPap $mfoPap)
    {
        //
    }
}
