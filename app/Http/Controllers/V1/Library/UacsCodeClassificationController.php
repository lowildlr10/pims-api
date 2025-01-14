<?php

namespace App\Http\Controllers\V1\Library;

use App\Models\UacsCodeClassification;
use Illuminate\Http\Request;

class UacsCodeClassificationController extends Controller
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

        $uacsCodeClassifications = UacsCodeClassification::query()->with('uacs_codes');

        if (!empty($search)) {
            $uacsCodeClassifications = $uacsCodeClassifications->where(function($query) use ($search){
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

            $uacsCodeClassifications = $uacsCodeClassifications->orderBy($columnSort, $sortDirection);
        }

        if ($paginated) {
            return $uacsCodeClassifications->paginate($perPage);
        } else {
            if (!$showInactive) $uacsCodeClassifications = $uacsCodeClassifications->where('active', true);

            $uacsCodeClassifications = $showAll
                ? $uacsCodeClassifications->get()
                : $uacsCodeClassifications = $uacsCodeClassifications->limit($perPage)->get();

            return response()->json([
                'data' => $uacsCodeClassifications
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
    public function show(UacsCodeClassification $uacsCodeClassification)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UacsCodeClassification $uacsCodeClassification)
    {
        //
    }
}
