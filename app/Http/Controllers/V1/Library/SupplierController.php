<?php

namespace App\Http\Controllers\V1\Library;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Repositories\LogRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class SupplierController extends Controller
{
    private LogRepository $logRepository;

    public function __construct(LogRepository $logRepository)
    {
        $this->logRepository = $logRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse | LengthAwarePaginator
    {
        $search = trim($request->get('search', ''));
        $perPage = $request->get('per_page', 5);
        $showAll = filter_var($request->get('show_all', false), FILTER_VALIDATE_BOOLEAN);
        $showInactive = filter_var($request->get('show_inactive', false), FILTER_VALIDATE_BOOLEAN);
        $columnSort = $request->get('column_sort', 'supplier_name');
        $sortDirection = $request->get('sort_direction', 'desc');
        $paginated = filter_var($request->get('paginated', true), FILTER_VALIDATE_BOOLEAN);

        $suppliers = Supplier::query();

        if (!empty($search)) {
            $suppliers = $suppliers->where(function($query) use ($search){
                $query->whereRaw("CAST(id AS TEXT) = ?", [$search])
                    ->orWhere('supplier_name', 'ILIKE', "%{$search}%")
                    ->orWhere('address', 'ILIKE', "%{$search}%")
                    ->orWhere('tin_no', 'ILIKE', "%{$search}%")
                    ->orWhere('phone', 'ILIKE', "%{$search}%")
                    ->orWhere('telephone', 'ILIKE', "%{$search}%")
                    ->orWhere('vat_no', 'ILIKE', "%{$search}%")
                    ->orWhere('contact_person', 'ILIKE', "%{$search}%");
            });
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            switch ($columnSort) {
                case 'supplier_name_formatted':
                    $columnSort = 'supplier_name';
                    break;
                default:
                    break;
            }

            $suppliers = $suppliers->orderBy($columnSort, $sortDirection);
        }

        if ($paginated) {
            return $suppliers->paginate($perPage);
        } else {
            if (!$showInactive) $suppliers = $suppliers->where('active', true);

            $suppliers = $showAll
                ? $suppliers->get()
                : $suppliers = $suppliers->limit($perPage)->get();

            return response()->json([
                'data' => $suppliers
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_name' => 'required|unique:suppliers,supplier_name',
            'address' => 'nullable',
            'tin_no' => 'nullable',
            'phone' => 'nullable',
            'telephone' => 'nullable',
            'vat_no' => 'nullable',
            'contact_person' => 'nullable',
            'active' => 'required|in:true,false'
        ]);

        $validated['active'] = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        try {
            $supplier = Supplier::create($validated);

            $this->logRepository->create([
                'message' => "Supplier created successfully.",
                'log_id' => $supplier->id,
                'log_module' => 'lib-supplier',
                'data' => $supplier
            ]);
        } catch (\Throwable $th) {
            $this->logRepository->create([
                'message' => "Supplier creation failed. Please try again.",
                'details' => $th->getMessage(),
                'log_module' => 'lib-supplier',
                'data' => $validated
            ], isError: true);

            return response()->json([
                'message' => 'Supplier creation failed. Please try again.'
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $supplier,
                'message' => 'Supplier created successfully.'
            ]
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Supplier $supplier)
    {
        return response()->json([
            'data' => [
                'data' => $supplier
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'supplier_name' => 'required|unique:suppliers,supplier_name,' . $supplier->id,
            'address' => 'nullable',
            'tin_no' => 'nullable',
            'phone' => 'nullable',
            'telephone' => 'nullable',
            'vat_no' => 'nullable',
            'contact_person' => 'nullable',
            'active' => 'required|in:true,false'
        ]);

        $validated['active'] = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        try {
            $supplier->update($validated);

            $this->logRepository->create([
                'message' => "Supplier updated successfully.",
                'log_id' => $supplier->id,
                'log_module' => 'lib-supplier',
                'data' => $supplier
            ]);
        } catch (\Throwable $th) {
            $this->logRepository->create([
                'message' => "Supplier update failed. Please try again.",
                'details' => $th->getMessage(),
                'log_id' => $supplier->id,
                'log_module' => 'lib-supplier',
                'data' => $validated
            ], isError: true);

            return response()->json([
                'message' => 'Supplier update failed. Please try again.'
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $supplier,
                'message' => 'Supplier updated successfully.'
            ]
        ]);
    }
}
