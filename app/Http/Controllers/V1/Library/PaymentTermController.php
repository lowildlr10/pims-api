<?php

namespace App\Http\Controllers\V1\Library;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentTermResource;
use App\Services\PaymentTermService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Library - Payment Terms
 * APIs for managing payment terms
 */
class PaymentTermController extends Controller
{
    public function __construct(
        protected PaymentTermService $service
    ) {}

    /**
     * List Payment Terms
     *
     * Retrieve a paginated list of payment terms.
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

        $paymentTerms = $this->service->getAll($filters);

        return PaymentTermResource::collection($paymentTerms);
    }
}
