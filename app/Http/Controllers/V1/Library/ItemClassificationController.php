<?php

namespace App\Http\Controllers\V1\Library;

use App\Http\Controllers\Controller;
use App\Models\ItemClassification;
use App\Repositories\LogRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ItemClassificationController extends Controller
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
        $columnSort = $request->get('column_sort', 'classification_name');
        $sortDirection = $request->get('sort_direction', 'desc');
        $paginated = filter_var($request->get('paginated', true), FILTER_VALIDATE_BOOLEAN);

        $itemClassifications = ItemClassification::query();

        if (!empty($search)) {
            $itemClassifications = $itemClassifications->where(function($query) use ($search){
                $query->whereRaw("CAST(id AS TEXT) = ?", [$search])
                    ->orWhere('classification_name', 'ILIKE', "%{$search}%");
            });
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            switch ($columnSort) {
                case 'classification_name_formatted':
                    $columnSort = 'classification_name';
                    break;
                default:
                    break;
            }

            $itemClassifications = $itemClassifications->orderBy($columnSort, $sortDirection);
        }

        if ($paginated) {
            return $itemClassifications->paginate($perPage);
        } else {
            if (!$showInactive) $itemClassifications = $itemClassifications->where('active', true);

            $itemClassifications = $showAll
                ? $itemClassifications->get()
                : $itemClassifications = $itemClassifications->limit($perPage)->get();

            return response()->json([
                'data' => $itemClassifications
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'classification_name' => 'required|unique:item_classifications,classification_name',
            'active' => 'required|boolean'
        ]);

        $validated['active'] = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        try {
            $itemClassifications = ItemClassification::create($validated);

            $this->logRepository->create([
                'message' => "Item classification created successfully.",
                'log_id' => $itemClassifications->id,
                'log_module' => 'lib-item-class',
                'data' => $itemClassifications
            ]);
        } catch (\Throwable $th) {
            $this->logRepository->create([
                'message' => "Item classification creation failed. Please try again.",
                'details' => $th->getMessage(),
                'log_module' => 'lib-item-class',
                'data' => $validated
            ], isError: true);

            return response()->json([
                'message' => 'Item classification creation failed. Please try again.'
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $itemClassifications,
                'message' => 'Item classification created successfully.'
            ]
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(ItemClassification $itemClassification)
    {
        return response()->json([
            'data' => [
                'data' => $itemClassification
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ItemClassification $itemClassification)
    {
        $validated = $request->validate([
            'classification_name' => 'required|unique:item_classifications,classification_name,' . $itemClassification->id,
            'active' => 'required|boolean'
        ]);

        $validated['active'] = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        try {
            $itemClassification->update($validated);

            $this->logRepository->create([
                'message' => "Item classification updated successfully.",
                'log_id' => $itemClassification->id,
                'log_module' => 'lib-item-class',
                'data' => $itemClassification
            ]);
        } catch (\Throwable $th) {
            $this->logRepository->create([
                'message' => "Item classification update failed. Please try again.",
                'details' => $th->getMessage(),
                'log_id' => $itemClassification->id,
                'log_module' => 'lib-item-class',
                'data' => $validated
            ], isError: true);

            return response()->json([
                'message' => 'Item classification update failed. Please try again.'
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $itemClassification,
                'message' => 'Item classification updated successfully.'
            ]
        ]);
    }
}
