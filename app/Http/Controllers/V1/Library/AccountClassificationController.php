<?php

namespace App\Http\Controllers\V1\Library;

use App\Http\Controllers\Controller;
use App\Http\Resources\AccountClassificationResource;
use App\Services\AccountClassificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Library - Account Classifications
 * APIs for managing account classifications
 */
class AccountClassificationController extends Controller
{
    public function __construct(protected AccountClassificationService $service) {}

    /**
     * List Account Classifications
     *
     * Retrieve a paginated list of account classifications.
     *
     * @queryParam search string Search by classification name.
     * @queryParam per_page int Number of items per page. Default 50.
     * @queryParam show_all boolean Show all items without pagination. Default false.
     * @queryParam show_inactive boolean Show inactive classifications. Default false.
     * @queryParam column_sort string Sort field. Default classification_name.
     * @queryParam sort_direction string Sort direction (asc/desc). Default desc.
     * @queryParam paginated boolean Return paginated results. Default true.
     *
     * @response 200 {"data": [...], "meta": {...}}
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['search', 'per_page', 'show_all', 'show_inactive', 'column_sort', 'sort_direction', 'paginated']);
        $filters['show_all'] = filter_var($filters['show_all'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $filters['show_inactive'] = filter_var($filters['show_inactive'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $filters['paginated'] = filter_var($filters['paginated'] ?? true, FILTER_VALIDATE_BOOLEAN);

        return AccountClassificationResource::collection($this->service->getAll($filters));
    }

    /**
     * Create Account Classification
     *
     * @bodyParam classification_name string required The classification name.
     * @bodyParam active boolean required Whether active. Default true.
     *
     * @response 201 {"data": {"id": "uuid", "classification_name": "Name"}, "message": "Account classification created successfully."}
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'classification_name' => 'required|unique:account_classifications,classification_name',
            'active' => 'required|boolean',
        ]);
        try {
            $accountClassification = $this->service->create($validated);

            return response()->json([
                'data' => new AccountClassificationResource($accountClassification),
                'message' => 'Account classification created successfully.',
            ], 201);
        } catch (\Throwable $th) {
            $this->service->logError('Account classification creation failed.', $th, $validated);

            return response()->json(['message' => 'Account classification creation failed. Please try again.'], 422);
        }
    }

    /**
     * Show Account Classification
     *
     * @urlParam id string required The account classification UUID.
     *
     * @response 200 {"data": {"id": "uuid", "classification_name": "Name"}}
     */
    public function show(string $id): JsonResponse
    {
        $accountClassification = $this->service->getById($id);
        if (! $accountClassification) {
            return response()->json(['message' => 'Account classification not found.'], 404);
        }

        return response()->json(['data' => new AccountClassificationResource($accountClassification)]);
    }

    /**
     * Update Account Classification
     *
     * @urlParam id string required The account classification UUID.
     *
     * @bodyParam classification_name string required The classification name.
     * @bodyParam active boolean required Whether active.
     *
     * @response 200 {"data": {...}, "message": "Account classification updated successfully."}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'classification_name' => 'required|unique:account_classifications,classification_name,'.$id,
            'active' => 'required|boolean',
        ]);
        try {
            $accountClassification = $this->service->update($id, $validated);

            return response()->json([
                'data' => new AccountClassificationResource($accountClassification),
                'message' => 'Account classification updated successfully.',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Account classification update failed.', $th, $validated);

            return response()->json(['message' => 'Account classification update failed. Please try again.'], 422);
        }
    }
}
