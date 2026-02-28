<?php

namespace App\Http\Controllers\V1\Account;

use App\Http\Controllers\Controller;
use App\Http\Resources\PositionResource;
use App\Services\PositionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Account - Positions
 * APIs for managing positions
 */
class PositionController extends Controller
{
    public function __construct(
        protected PositionService $service
    ) {}

    /**
     * List Positions
     *
     * Retrieve a paginated list of positions.
     *
     * @queryParam search string Search by position name.
     * @queryParam per_page int Number of items per page. Default 50.
     * @queryParam column_sort string Sort field. Default position_name.
     * @queryParam sort_direction string Sort direction (asc/desc). Default desc.
     *
     * @response 200 {
     *   "data": [...],
     *   "meta": {...}
     * }
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only([
            'search',
            'per_page',
            'column_sort',
            'sort_direction',
        ]);

        $positions = $this->service->getAll($filters);

        return PositionResource::collection($positions);
    }
}
