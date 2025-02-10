<?php

namespace App\Http\Controllers\V1\Library;

use App\Http\Controllers\Controller;
use App\Models\UacsCodeClassification;
use App\Repositories\LogRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class UacsCodeClassificationController extends Controller
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

        $uacsCodeClassifications = UacsCodeClassification::query();

        if (!empty($search)) {
            $uacsCodeClassifications = $uacsCodeClassifications->where(function($query) use ($search){
                $query->where('classification_name', 'ILIKE', "%{$search}%");
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

            $uacsCodeClassifications = $uacsCodeClassifications->orderBy($columnSort, $sortDirection);
        }

        if ($paginated) {
            return $uacsCodeClassifications->paginate($perPage);
        } else {
            if (!$showInactive) $uacsCodeClassifications = $uacsCodeClassifications->where('active', true);

            $uacsCodeClassifications = $showAll
                ? $uacsCodeClassifications->get()
                : $uacsCodeClassifications = $uacsCodeClassifications->limit($perPage)->get();

            return response()->json([
                'data' => $uacsCodeClassifications
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'classification_name' => 'required|unique:uacs_code_classifications,classification_name',
            'active' => 'required|in:true,false'
        ]);

        $validated['active'] = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        try {
            $uacsCodeClassification = UacsCodeClassification::create($validated);

            $this->logRepository->create([
                'message' => "UACS code classification created successfully.",
                'log_id' => $uacsCodeClassification->id,
                'log_module' => 'lib-uacs-class',
                'data' => $uacsCodeClassification
            ]);
        } catch (\Throwable $th) {
            $this->logRepository->create([
                'message' => "UACS code classification creation failed. Please try again.",
                'details' => $th->getMessage(),
                'log_module' => 'lib-uacs-class',
                'data' => $validated
            ], isError: true);

            return response()->json([
                'message' => 'UACS code classification creation failed. Please try again.'
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $uacsCodeClassification,
                'message' => 'UACS code classification created successfully.'
            ]
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(UacsCodeClassification $uacsCodeClassification)
    {
        return response()->json([
            'data' => [
                'data' => $uacsCodeClassification
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UacsCodeClassification $uacsCodeClassification)
    {
        $validated = $request->validate([
            'classification_name' => 'required|unique:uacs_code_classifications,classification_name,' . $uacsCodeClassification->id,
            'active' => 'required|in:true,false'
        ]);

        $validated['active'] = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        try {
            $uacsCodeClassification->update($validated);

             $this->logRepository->create([
                'message' => "Section updated successfully.",
                'log_id' => $uacsCodeClassification->id,
                'log_module' => 'lib-uacs-class',
                'data' => $uacsCodeClassification
            ]);
        } catch (\Throwable $th) {
            $this->logRepository->create([
                'message' => "Section update failed.",
                'details' => $th->getMessage(),
                'log_id' => $uacsCodeClassification->id,
                'log_module' => 'lib-uacs-class',
                'data' => $validated
            ], isError: true);

            return response()->json([
                'message' => 'UACS code classification update failed. Please try again.'
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $uacsCodeClassification,
                'message' => 'UACS code classification updated successfully.'
            ]
        ]);
    }
}
