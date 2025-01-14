<?php

namespace App\Http\Controllers\V1\Library;

use App\Models\UacsCode;
use Illuminate\Http\Request;

class UacsCodeController extends Controller
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
        $columnSort = $request->get('column_sort', 'account_title');
        $sortDirection = $request->get('sort_direction', 'desc');
        $paginated = filter_var($request->get('paginated', true), FILTER_VALIDATE_BOOLEAN);

        $uacsCodes = UacsCode::query()->with('uacs_classification');

        if (!empty($search)) {
            $uacsCodes = $uacsCodes->where(function($query) use ($search){
                $query->where('account_title', 'ILIKE', "%{$search}%")
                    ->orWhere('code', 'ILIKE', "%{$search}%")
                    ->orWhere('description', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('uacs_classification', 'classification_name', 'ILIKE', "%{$search}%");
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

            $uacsCodes = $uacsCodes->orderBy($columnSort, $sortDirection);
        }

        if ($paginated) {
            return $uacsCodes->paginate($perPage);
        } else {
            if (!$showInactive) $uacsCodes = $uacsCodes->where('active', true);

            $uacsCodes = $showAll
                ? $uacsCodes->get()
                : $uacsCodes = $uacsCodes->limit($perPage)->get();

            return response()->json([
                'data' => $uacsCodes
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
    public function show(UacsCode $uacsCode)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UacsCode $uacsCode)
    {
        //
    }
}
