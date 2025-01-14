<?php

namespace App\Http\Controllers\V1\Library;

use App\Models\Signatory;
use Illuminate\Http\Request;

class SignatoryController extends Controller
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

        $signatories = Signatory::query()->with([
            'signatory_details',
            'user'
        ]);

        if (!empty($search)) {
            $signatories = $signatories->where(function($query) use ($search){
                $query->whereRelation('user', 'firstname', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('user', 'middlename', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('user', 'lastname', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('signatory_details', 'position', 'ILIKE', "%{$search}%");;
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

            $signatories = $signatories->orderBy($columnSort, $sortDirection);
        }

        if ($paginated) {
            return $signatories->paginate($perPage);
        } else {
            if (!$showInactive) $signatories = $signatories->where('active', true);

            $signatories = $showAll
                ? $signatories->get()
                : $signatories = $signatories->limit($perPage)->get();

            return response()->json([
                'data' => $signatories
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
    public function show(Signatory $signatories)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Signatory $signatories)
    {
        //
    }
}
