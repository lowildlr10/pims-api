<?php

namespace App\Http\Controllers\V1\Library;

use App\Http\Controllers\Controller;
use App\Http\Resources\DeliveryTermResource;
use App\Services\DeliveryTermService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Library - Delivery Terms
 * APIs for managing delivery terms
 */
class DeliveryTermController extends Controller
{
    public function __construct(
        protected DeliveryTermService $service
    ) {}

    /**
     * List Delivery Terms
     *
     * Retrieve a paginated list of delivery terms.
     *
     * @queryParam search string Search by term name.
     * @queryParam per_page int Number of items per page. Default 50.
     * @queryParam show_all boolean Show all items without pagination. Default false.
     * @queryParam column_sort string Sort field. Default term_name.
     * @queryParam sort_direction string Sort direction (asc/desc). Default desc.
     * @queryParam paginated boolean Return paginated results. Default true.
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
            'show_all',
            'column_sort',
            'sort_direction',
            'paginated',
        ]);

        $filters['show_all'] = filter_var($filters['show_all'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $filters['paginated'] = filter_var($filters['paginated'] ?? true, FILTER_VALIDATE_BOOLEAN);

        $deliveryTerms = $this->service->getAll($filters);

        return DeliveryTermResource::collection($deliveryTerms);
    }
}
