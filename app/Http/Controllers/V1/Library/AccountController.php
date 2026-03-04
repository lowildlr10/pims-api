<?php

namespace App\Http\Controllers\V1\Library;

use App\Http\Controllers\Controller;
use App\Http\Resources\AccountResource;
use App\Services\AccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Library - Accounts
 * APIs for managing accounts
 */
class AccountController extends Controller
{
    public function __construct(protected AccountService $service) {}

    /**
     * List Accounts
     *
     * @queryParam search string Search by account title, code, description, or classification.
     * @queryParam per_page int Number of items per page. Default 50.
     * @queryParam show_all boolean Show all items. Default false.
     * @queryParam show_inactive boolean Show inactive. Default false.
     * @queryParam column_sort string Sort field. Default code.
     * @queryParam sort_direction string Sort direction. Default desc.
     * @queryParam paginated boolean Return paginated results. Default true.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['search', 'per_page', 'show_all', 'show_inactive', 'column_sort', 'sort_direction', 'paginated']);
        $filters['show_all'] = filter_var($filters['show_all'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $filters['show_inactive'] = filter_var($filters['show_inactive'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $filters['paginated'] = filter_var($filters['paginated'] ?? true, FILTER_VALIDATE_BOOLEAN);

        return AccountResource::collection($this->service->getAll($filters));
    }

    /**
     * Create Account
     *
     * @bodyParam classification_id string required The classification ID.
     * @bodyParam account_title string required The account title.
     * @bodyParam code string required The code.
     * @bodyParam description string optional The description.
     * @bodyParam active boolean required Whether active. Default true.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'classification_id' => 'required',
            'account_title' => 'required|string',
            'code' => 'required|unique:accounts,code',
            'description' => 'nullable',
            'active' => 'required|boolean',
        ]);
        try {
            $account = $this->service->create($validated);

            return response()->json([
                'data' => new AccountResource($account),
                'message' => 'Account created successfully.',
            ], 201);
        } catch (\Throwable $th) {
            $this->service->logError('Account creation failed.', $th, $validated);

            return response()->json(['message' => 'Account creation failed. Please try again.'], 422);
        }
    }

    /**
     * Show Account
     *
     * @urlParam id string required The UUID.
     */
    public function show(string $id): JsonResponse
    {
        $account = $this->service->getById($id);
        if (! $account) {
            return response()->json(['message' => 'Account not found.'], 404);
        }

        return response()->json(['data' => new AccountResource($account)]);
    }

    /**
     * Update Account
     *
     * @urlParam id string required The UUID.
     *
     * @bodyParam classification_id string required The classification ID.
     * @bodyParam account_title string required The account title.
     * @bodyParam code string required The code.
     * @bodyParam description string optional The description.
     * @bodyParam active boolean required Whether active.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'classification_id' => 'required',
            'account_title' => 'required|string',
            'code' => 'required|unique:accounts,code,'.$id,
            'description' => 'nullable',
            'active' => 'required|boolean',
        ]);
        try {
            $account = $this->service->update($id, $validated);

            return response()->json([
                'data' => new AccountResource($account),
                'message' => 'Account updated successfully.',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Account update failed.', $th, $validated);

            return response()->json(['message' => 'Account update failed. Please try again.'], 422);
        }
    }
}
