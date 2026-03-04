<?php

namespace App\Http\Controllers\V1\Library;

use App\Http\Controllers\Controller;
use App\Http\Resources\SupplierResource;
use App\Services\SupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Library - Suppliers
 * APIs for managing suppliers
 */
class SupplierController extends Controller
{
    public function __construct(
        protected SupplierService $service
    ) {}

    /**
     * List Suppliers
     *
     * Retrieve a paginated list of suppliers.
     *
     * @queryParam search string Search by supplier name, address, TIN, phone, etc.
     * @queryParam per_page int Number of items per page. Default 50.
     * @queryParam show_all boolean Show all items without pagination. Default false.
     * @queryParam show_inactive boolean Show inactive suppliers. Default false.
     * @queryParam column_sort string Sort field. Default supplier_name.
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
            'show_inactive',
            'column_sort',
            'sort_direction',
            'paginated',
        ]);

        $filters['show_all'] = filter_var($filters['show_all'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $filters['show_inactive'] = filter_var($filters['show_inactive'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $filters['paginated'] = filter_var($filters['paginated'] ?? true, FILTER_VALIDATE_BOOLEAN);

        $suppliers = $this->service->getAll($filters);

        return SupplierResource::collection($suppliers);
    }

    /**
     * Create Supplier
     *
     * Create a new supplier.
     *
     * @bodyParam supplier_name string required The supplier name.
     * @bodyParam address string optional The address.
     * @bodyParam tin_no string optional The TIN number.
     * @bodyParam phone string optional The phone number.
     * @bodyParam telephone string optional The telephone number.
     * @bodyParam vat_no string optional The VAT number.
     * @bodyParam contact_person string optional The contact person.
     * @bodyParam active boolean required Whether the supplier is active. Default true.
     *
     * @response 201 {
     *   "data": {
     *     "id": "uuid",
     *     "supplier_name": "Supplier Name"
     *   },
     *   "message": "Supplier created successfully."
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'supplier_name' => 'required|unique:suppliers,supplier_name',
            'address' => 'nullable',
            'tin_no' => 'nullable',
            'phone' => 'nullable',
            'telephone' => 'nullable',
            'vat_no' => 'nullable',
            'contact_person' => 'nullable',
            'active' => 'required|boolean',
        ]);

        try {
            $supplier = $this->service->create($validated);

            return response()->json([
                'data' => new SupplierResource($supplier),
                'message' => 'Supplier created successfully.',
            ], 201);
        } catch (\Throwable $th) {
            $this->service->logError('Supplier creation failed.', $th, $validated);

            return response()->json([
                'message' => 'Supplier creation failed. Please try again.',
            ], 422);
        }
    }

    /**
     * Show Supplier
     *
     * Get a specific supplier by ID.
     *
     * @urlParam id string required The supplier UUID.
     *
     * @response 200 {
     *   "data": {
     *     "id": "uuid",
     *     "supplier_name": "Supplier Name"
     *   }
     * }
     */
    public function show(string $id): JsonResponse
    {
        $supplier = $this->service->getById($id);

        if (! $supplier) {
            return response()->json(['message' => 'Supplier not found.'], 404);
        }

        return response()->json([
            'data' => new SupplierResource($supplier),
        ]);
    }

    /**
     * Update Supplier
     *
     * Update an existing supplier.
     *
     * @urlParam id string required The supplier UUID.
     *
     * @bodyParam supplier_name string required The supplier name.
     * @bodyParam address string optional The address.
     * @bodyParam tin_no string optional The TIN number.
     * @bodyParam phone string optional The phone number.
     * @bodyParam telephone string optional The telephone number.
     * @bodyParam vat_no string optional The VAT number.
     * @bodyParam contact_person string optional The contact person.
     * @bodyParam active boolean required Whether the supplier is active.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Supplier updated successfully."
     * }
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'supplier_name' => 'required|unique:suppliers,supplier_name,'.$id,
            'address' => 'nullable',
            'tin_no' => 'nullable',
            'phone' => 'nullable',
            'telephone' => 'nullable',
            'vat_no' => 'nullable',
            'contact_person' => 'nullable',
            'active' => 'required|boolean',
        ]);

        try {
            $supplier = $this->service->update($id, $validated);

            return response()->json([
                'data' => new SupplierResource($supplier),
                'message' => 'Supplier updated successfully.',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Supplier update failed.', $th, $validated);

            return response()->json([
                'message' => 'Supplier update failed. Please try again.',
            ], 422);
        }
    }
}
