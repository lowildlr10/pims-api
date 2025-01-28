<?php

namespace App\Http\Controllers\V1\Library;

use App\Http\Controllers\Controller;
use App\Models\FundingSource;
use App\Models\Location;
use App\Repositories\LogRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class FundingSourceController extends Controller
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
        $validated = $request->validate([
            'title' => 'required|unique:funding_sources,title',
            'location' => 'required',
            'total_cost' => 'required|numeric',
            'active' => 'required|in:true,false'
        ]);

        $validated['active'] = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        try {
            $location = Location::updateOrCreate([
                'location_name' => $validated['location'],
            ], [
                'location_name' => $validated['location']
            ]);

            $fundingSource = FundingSource::create(array_merge(
                $validated,
                [
                    'location_id' => $location->id,
                ]
            ));

            $this->logRepository->create([
                'message' => "Funding source/project created successfully.",
                'log_id' => $fundingSource->id,
                'log_module' => 'lib-fund-source',
                'data' => $fundingSource
            ]);
        } catch (\Throwable $th) {
            $this->logRepository->create([
                'message' => "Funding source/project creation failed. Please try again.",
                'details' => $th->getMessage(),
                'log_module' => 'lib-fund-source',
                'data' => $validated
            ], isError: true);

            return response()->json([
                'message' => 'Funding source/project creation failed. Please try again.'
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $fundingSource,
                'message' => 'Funding source/project created successfully.'
            ]
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(FundingSource $fundingSource)
    {
        $fundingSource = $fundingSource->with('location')
            ->find($fundingSource->id);

        return response()->json([
            'data' => [
                'data' => $fundingSource
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FundingSource $fundingSource)
    {
        $validated = $request->validate([
            'title' => 'required|unique:funding_sources,title,' . $fundingSource->id,
            'location' => 'required',
            'total_cost' => 'required|numeric',
            'active' => 'required|in:true,false'
        ]);

        $validated['active'] = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        try {
            $location = Location::updateOrCreate([
                'location_name' => $validated['location'],
            ], [
                'location_name' => $validated['location']
            ]);

            $fundingSource->update(array_merge(
                $validated,
                [
                    'location_id' => $location->id,
                ]
            ));

            $this->logRepository->create([
                'message' => "Funding source/project update failed. Please try again.",
                'log_id' => $fundingSource->id,
                'log_module' => 'lib-fund-source',
                'data' => $fundingSource
            ]);
        } catch (\Throwable $th) {
            $this->logRepository->create([
                'message' => "Funding source/project update failed. Please try again.",
                'details' => $th->getMessage(),
                'log_id' => $fundingSource->id,
                'log_module' => 'lib-fund-source',
                'data' => $validated
            ], isError: true);

            return response()->json([
                'message' => 'Funding source/project update failed. Please try again.'
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $fundingSource,
                'message' => 'Funding source/project updated successfully.'
            ]
        ]);
    }
}
