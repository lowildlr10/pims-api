<?php

namespace App\Http\Controllers\V1\Library;

use App\Http\Controllers\Controller;
use App\Models\FundingSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class FundingSourceController extends Controller
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
        $columnSort = $request->get('column_sort', 'title');
        $sortDirection = $request->get('sort_direction', 'desc');
        $paginated = filter_var($request->get('paginated', true), FILTER_VALIDATE_BOOLEAN);

        $fundingSources = FundingSource::query()->with('location');

        if (!empty($search)) {
            $fundingSources = $fundingSources->where(function($query) use ($search){
                $query->where('title', 'ILIKE', "%{$search}%")
                    ->orWhere('total_cost', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('location', 'location_name', 'ILIKE', "%{$search}%");
            });
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            switch ($columnSort) {
                case 'title_formatted':
                    $columnSort = 'title';
                    break;
                case 'location_name':
                    $columnSort = 'location_id';
                    break;
                case 'total_cost_formatted':
                    $columnSort = 'total_cost';
                    break;
                default:
                    break;
            }

            $fundingSources = $fundingSources->orderBy($columnSort, $sortDirection);
        }

        if ($paginated) {
            return $fundingSources->paginate($perPage);
        } else {
            if (!$showInactive) $fundingSources = $fundingSources->where('active', true);

            $fundingSources = $showAll
                ? $fundingSources->get()
                : $fundingSources = $fundingSources->limit($perPage)->get();

            return response()->json([
                'data' => $fundingSources
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
    public function show(FundingSource $fundingSource)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FundingSource $fundingSource)
    {
        //
    }
}
