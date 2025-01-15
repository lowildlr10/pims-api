<?php

namespace App\Http\Controllers\V1\Library;

use App\Http\Controllers\Controller;
use App\Models\Signatory;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

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
        $columnSort = $request->get('column_sort', 'fullname');
        $sortDirection = $request->get('sort_direction', 'desc');
        $paginated = filter_var($request->get('paginated', true), FILTER_VALIDATE_BOOLEAN);

        $signatories = Signatory::query()->with([
            'details' => function ($query) {
                $query->orderBy('document');
            },
            'user'
        ]);

        if (!empty($search)) {
            $signatories = $signatories->where(function($query) use ($search){
                $query->whereRelation('user', 'firstname', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('user', 'middlename', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('user', 'lastname', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('details', 'position', 'ILIKE', "%{$search}%");;
            });
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            switch ($columnSort) {
                case 'fullname':
                    // $columnSort = 'user.firstname';
                    $columnSort = '';
                    $signatories = $signatories->orderBy(
                        User::select('firstname')->whereColumn('users.id', 'signatories.user_id')
                    );
                    break;
                default:
                    break;
            }

            if ($columnSort) {
                $signatories = $signatories->orderBy($columnSort, $sortDirection);
            }
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
