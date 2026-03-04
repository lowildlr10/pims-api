<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\LogResource;
use App\Services\LogService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

/**
 * @group Logs
 * APIs for managing system logs
 */
class LogController extends Controller
{
    public function __construct(protected LogService $service) {}

    /**
     * List Logs
     *
     * Retrieve a paginated list of logs with optional filtering.
     *
     * @queryParam search string Search by log_id, log_module, log_type, message, details, or user name.
     * @queryParam per_page int Number of items per page. Default 50.
     * @queryParam column_sort string Sort field. Default logged_at.
     * @queryParam sort_direction string Sort direction (asc/desc). Default desc.
     * @queryParam log_id string Filter by specific log entity ID.
     *
     * @response 200 {"data": [...], "meta": {...}}
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = Auth::user();
        $isSuper = $user->tokenCan('super:*');

        $filters = $request->only([
            'search',
            'per_page',
            'column_sort',
            'sort_direction',
            'log_id',
        ]);

        $logs = $this->service->getAll($filters, $user->id, $isSuper);

        return LogResource::collection($logs);
    }
}
