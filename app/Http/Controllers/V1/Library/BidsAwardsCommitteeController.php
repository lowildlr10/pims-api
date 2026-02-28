<?php

namespace App\Http\Controllers\V1\Library;

use App\Http\Controllers\Controller;
use App\Http\Resources\BidsAwardsCommitteeResource;
use App\Services\BidsAwardsCommitteeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Library - Bids and Awards Committees
 * APIs for managing bids and awards committees
 */
class BidsAwardsCommitteeController extends Controller
{
    public function __construct(protected BidsAwardsCommitteeService $service) {}

    /**
     * List Bids and Awards Committees
     *
     * @queryParam search string Search by committee name.
     * @queryParam per_page int Number of items per page. Default 50.
     * @queryParam show_all boolean Show all items. Default false.
     * @queryParam show_inactive boolean Show inactive. Default false.
     * @queryParam column_sort string Sort field. Default committee_name.
     * @queryParam sort_direction string Sort direction. Default desc.
     * @queryParam paginated boolean Return paginated results. Default true.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['search', 'per_page', 'show_all', 'show_inactive', 'column_sort', 'sort_direction', 'paginated']);
        $filters['show_all'] = filter_var($filters['show_all'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $filters['show_inactive'] = filter_var($filters['show_inactive'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $filters['paginated'] = filter_var($filters['paginated'] ?? true, FILTER_VALIDATE_BOOLEAN);

        return BidsAwardsCommitteeResource::collection($this->service->getAll($filters));
    }

    /**
     * Create Bids and Awards Committee
     *
     * @bodyParam committee_name string required The committee name.
     * @bodyParam active boolean required Whether active. Default true.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'committee_name' => 'required|unique:bids_awards_committees,committee_name',
            'active' => 'required|boolean',
        ]);
        try {
            $committee = $this->service->create($validated);

            return response()->json([
                'data' => new BidsAwardsCommitteeResource($committee),
                'message' => 'Bids awards committee created successfully.',
            ], 201);
        } catch (\Throwable $th) {
            $this->service->logError('Bids awards committee creation failed.', $th, $validated);

            return response()->json(['message' => 'Bids awards committee creation failed. Please try again.'], 422);
        }
    }

    /**
     * Show Bids and Awards Committee
     *
     * @urlParam id string required The UUID.
     */
    public function show(string $id): JsonResponse
    {
        $committee = $this->service->getById($id);
        if (! $committee) {
            return response()->json(['message' => 'Bids awards committee not found.'], 404);
        }

        return response()->json(['data' => new BidsAwardsCommitteeResource($committee)]);
    }

    /**
     * Update Bids and Awards Committee
     *
     * @urlParam id string required The UUID.
     *
     * @bodyParam committee_name string required The committee name.
     * @bodyParam active boolean required Whether active.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'committee_name' => 'required|unique:bids_awards_committees,committee_name,'.$id,
            'active' => 'required|boolean',
        ]);
        try {
            $committee = $this->service->update($id, $validated);

            return response()->json([
                'data' => new BidsAwardsCommitteeResource($committee),
                'message' => 'Bids awards committee updated successfully.',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Bids awards committee update failed.', $th, $validated);

            return response()->json(['message' => 'Bids awards committee update failed. Please try again.'], 422);
        }
    }
}
