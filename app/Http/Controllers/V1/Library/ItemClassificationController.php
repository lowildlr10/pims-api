<?php

namespace App\Http\Controllers\V1\Library;

use App\Models\ItemClassification;
use Illuminate\Http\Request;

class ItemClassificationController extends Controller
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
        $columnSort = $request->get('column_sort', 'classification_name');
        $sortDirection = $request->get('sort_direction', 'desc');
        $paginated = filter_var($request->get('paginated', true), FILTER_VALIDATE_BOOLEAN);

        $itemClassifications = ItemClassification::query();

        if (!empty($search)) {
            $itemClassifications = $itemClassifications->where(function($query) use ($search){
                $query->where('classification_name', 'ILIKE', "%{$search}%");
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

            $itemClassifications = $itemClassifications->orderBy($columnSort, $sortDirection);
        }

        if ($paginated) {
            return $itemClassifications->paginate($perPage);
        } else {
            if (!$showInactive) $itemClassifications = $itemClassifications->where('active', true);

            $itemClassifications = $showAll
                ? $itemClassifications->get()
                : $itemClassifications = $itemClassifications->limit($perPage)->get();

            return response()->json([
                'data' => $itemClassifications
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
    public function show(ItemClassification $itemClassification)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ItemClassification $itemClassification)
    {
        //
    }
}
