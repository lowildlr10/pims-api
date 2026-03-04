<?php

namespace App\Http\Controllers\V1\Library;

use App\Http\Controllers\Controller;
use App\Http\Resources\LocationResource;
use App\Services\LocationService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Library - Locations
 * APIs for managing locations
 */
class LocationController extends Controller
{
    public function __construct(
        protected LocationService $service
    ) {}

    /**
     * List Locations
     *
     * Retrieve a paginated list of locations.
     *
     * @queryParam search string Search by location name.
     * @queryParam per_page int Number of items per page. Default 50.
     * @queryParam column_sort string Sort field. Default location_name.
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

        $locations = $this->service->getAll($filters);

        return LocationResource::collection($locations);
    }
}
