<?php

namespace App\Http\Controllers\V1\Account;

use App\Http\Controllers\Controller;
use App\Http\Resources\DesignationResource;
use App\Services\DesignationService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Account - Designations
 * APIs for managing designations
 */
class DesignationController extends Controller
{
    public function __construct(
        protected DesignationService $service
    ) {}

    /**
     * List Designations
     *
     * Retrieve a paginated list of designations.
     *
     * @queryParam search string Search by designation name.
     * @queryParam per_page int Number of items per page. Default 50.
     * @queryParam column_sort string Sort field. Default designation_name.
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

        $designations = $this->service->getAll($filters);

        return DesignationResource::collection($designations);
    }
}
