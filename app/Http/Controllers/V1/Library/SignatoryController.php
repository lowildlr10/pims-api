<?php

namespace App\Http\Controllers\V1\Library;

use App\Http\Controllers\Controller;
use App\Http\Resources\SignatoryResource;
use App\Services\SignatoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Library - Signatories
 * APIs for managing signatories
 */
class SignatoryController extends Controller
{
    public function __construct(protected SignatoryService $service) {}

    /**
     * List Signatories
     *
     * Retrieve a paginated list of signatories or filtered by document and type.
     *
     * @queryParam search string Search by user name or position.
     * @queryParam per_page int Number of items per page. Default 50.
     * @queryParam show_all boolean Show all items. Default false.
     * @queryParam show_inactive boolean Show inactive. Default false.
     * @queryParam column_sort string Sort field. Default fullname.
     * @queryParam sort_direction string Sort direction. Default desc.
     * @queryParam document string Filter by document type.
     * @queryParam signatory_type string Filter by signatory type.
     * @queryParam paginated boolean Return paginated results. Default true.
     *
     * @response 200 {"data": [...], "meta": {...}}
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $filters = $request->only([
            'search', 'per_page', 'show_all', 'show_inactive',
            'column_sort', 'sort_direction', 'paginated',
        ]);
        $filters['show_all'] = filter_var($filters['show_all'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $filters['show_inactive'] = filter_var($filters['show_inactive'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $filters['paginated'] = filter_var($filters['paginated'] ?? true, FILTER_VALIDATE_BOOLEAN);

        $document = $request->get('document', '');
        $signatoryType = $request->get('signatory_type', '');

        if (! empty($document) && ! empty($signatoryType)) {
            $signatories = $this->service->getByDocumentAndType($document, $signatoryType, $filters);

            return response()->json(['data' => $signatories]);
        }

        $signatories = $this->service->getAll($filters);

        return SignatoryResource::collection($signatories);
    }

    /**
     * Create Signatory
     *
     * @bodyParam user_id string required The user ID.
     * @bodyParam details string required JSON array of signatory details.
     * @bodyParam active boolean required Whether active. Default true.
     *
     * @response 201 {"data": {"id": "uuid", ...}, "message": "Signatory created successfully."}
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|unique:signatories,user_id',
            'details' => 'required|string',
            'active' => 'required|boolean',
        ]);

        try {
            $signatory = $this->service->create($validated);

            return response()->json([
                'data' => new SignatoryResource($signatory),
                'message' => 'Signatory created successfully.',
            ], 201);
        } catch (\Throwable $th) {
            $this->service->logError('Signatory creation failed.', $th, $validated);

            return response()->json([
                'message' => 'Signatory creation failed. Please try again.',
            ], 422);
        }
    }

    /**
     * Show Signatory
     *
     * @urlParam id string required The UUID.
     *
     * @response 200 {"data": {"id": "uuid", ...}}
     */
    public function show(string $id): JsonResponse
    {
        $signatory = $this->service->getById($id);

        if (! $signatory) {
            return response()->json(['message' => 'Signatory not found.'], 404);
        }

        return response()->json(['data' => new SignatoryResource($signatory)]);
    }

    /**
     * Update Signatory
     *
     * @urlParam id string required The UUID.
     *
     * @bodyParam details string required JSON array of signatory details.
     * @bodyParam active boolean required Whether active.
     *
     * @response 200 {"data": {...}, "message": "Signatory updated successfully."}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'details' => 'required|string',
            'active' => 'required|boolean',
        ]);

        try {
            $signatory = $this->service->update($id, $validated);

            return response()->json([
                'data' => new SignatoryResource($signatory),
                'message' => 'Signatory updated successfully.',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Signatory update failed.', $th, $validated);

            return response()->json([
                'message' => 'Signatory update failed. Please try again.',
            ], 422);
        }
    }
}
