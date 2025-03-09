<?php

namespace App\Http\Controllers\V1\Library;

use App\Http\Controllers\Controller;
use App\Models\BidsAwardsCommittee;
use App\Repositories\LogRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class BidsAwardsCommitteeController extends Controller
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
        $columnSort = $request->get('column_sort', 'committee_name');
        $sortDirection = $request->get('sort_direction', 'desc');
        $paginated = filter_var($request->get('paginated', true), FILTER_VALIDATE_BOOLEAN);

        $bidsAwardsCommittees = BidsAwardsCommittee::query();

        if (!empty($search)) {
            $bidsAwardsCommittees = $bidsAwardsCommittees->where(function($query) use ($search){
                $query->where('id', $search)
                    ->orWhere('committee_name', 'ILIKE', "%{$search}%");
            });
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            switch ($columnSort) {
                case 'committee_name_formatted':
                    $columnSort = 'committee_name';
                    break;
                default:
                    break;
            }

            $bidsAwardsCommittees = $bidsAwardsCommittees->orderBy($columnSort, $sortDirection);
        }

        if ($paginated) {
            return $bidsAwardsCommittees->paginate($perPage);
        } else {
            if (!$showInactive) $bidsAwardsCommittees = $bidsAwardsCommittees->where('active', true);

            $bidsAwardsCommittees = $showAll
                ? $bidsAwardsCommittees->get()
                : $bidsAwardsCommittees = $bidsAwardsCommittees->limit($perPage)->get();

            return response()->json([
                'data' => $bidsAwardsCommittees
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'committee_name' => 'required|unique:bids_awards_committees,committee_name',
            'active' => 'required|in:true,false'
        ]);

        $validated['active'] = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        try {
            $bidsAwardsCommittee = BidsAwardsCommittee::create($validated);

            $this->logRepository->create([
                'message' => "Bids awards committee created successfully.",
                'log_id' => $bidsAwardsCommittee->id,
                'log_module' => 'lib-bid-committee',
                'data' => $bidsAwardsCommittee
            ]);
        } catch (\Throwable $th) {
            $this->logRepository->create([
                'message' => "Bids awards committee creation failed. Please try again.",
                'details' => $th->getMessage(),
                'log_module' => 'lib-bid-committee',
                'data' => $validated
            ], isError: true);

            return response()->json([
                'message' => 'Bids awards committee creation failed. Please try again.'
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $bidsAwardsCommittee,
                'message' => 'Bids awards committee created successfully.'
            ]
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(BidsAwardsCommittee $bidsAwardsCommittee)
    {
        return response()->json([
            'data' => [
                'data' => $bidsAwardsCommittee
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BidsAwardsCommittee $bidsAwardsCommittee)
    {
        $validated = $request->validate([
            'committee_name' => 'required|unique:bids_awards_committees,committee_name,' . $bidsAwardsCommittee->id,
            'active' => 'required|in:true,false'
        ]);

        $validated['active'] = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        try {
            $bidsAwardsCommittee->update($validated);

            $this->logRepository->create([
                'message' => "Bids awards committee updated successfully.",
                'log_id' => $bidsAwardsCommittee->id,
                'log_module' => 'lib-bid-committee',
                'data' => $bidsAwardsCommittee
            ]);
        } catch (\Throwable $th) {
            $this->logRepository->create([
                'message' => "Bids awards committee update failed. Please try again.",
                'details' => $th->getMessage(),
                'log_id' => $bidsAwardsCommittee->id,
                'log_module' => 'lib-bid-committee',
                'data' => $validated
            ], isError: true);

            return response()->json([
                'message' => 'Bids awards committee update failed. Please try again.'
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $bidsAwardsCommittee,
                'message' => 'Bids awards committee updated successfully.'
            ]
        ]);
    }
}
