<?php

namespace App\Http\Controllers\V1\Library;

use App\Http\Controllers\Controller;
use App\Models\ProcurementMode;
use App\Repositories\LogRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ProcurementModeController extends Controller
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
        $columnSort = $request->get('column_sort', 'mode_name');
        $sortDirection = $request->get('sort_direction', 'desc');
        $paginated = filter_var($request->get('paginated', true), FILTER_VALIDATE_BOOLEAN);

        $procurementModes = ProcurementMode::query();

        if (!empty($search)) {
            $procurementModes = $procurementModes->where(function($query) use ($search){
                $query->where('id', $search)
                    ->orWhere('mode_name', 'ILIKE', "%{$search}%");
            });
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            switch ($columnSort) {
                case 'mode_name_formatted':
                    $columnSort = 'mode_name';
                    break;
                default:
                    break;
            }

            $procurementModes = $procurementModes->orderBy($columnSort, $sortDirection);
        }

        if ($paginated) {
            return $procurementModes->paginate($perPage);
        } else {
            if (!$showInactive) $procurementModes = $procurementModes->where('active', true);

            $procurementModes = $showAll
                ? $procurementModes->get()
                : $procurementModes = $procurementModes->limit($perPage)->get();

            return response()->json([
                'data' => $procurementModes
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'mode_name' => 'required|unique:procurement_modes,mode_name',
            'active' => 'required|in:true,false'
        ]);

        $validated['active'] = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        try {
            $procurementMode = ProcurementMode::create($validated);

            $this->logRepository->create([
                'message' => "Mode of procurement created successfully.",
                'log_id' => $procurementMode->id,
                'log_module' => 'lib-mode-proc',
                'data' => $procurementMode
            ]);
        } catch (\Throwable $th) {
            $this->logRepository->create([
                'message' => "Mode of procurement creation failed. Please try again.",
                'details' => $th->getMessage(),
                'log_module' => 'lib-mode-proc',
                'data' => $validated
            ], isError: true);

            return response()->json([
                'message' => 'Mode of procurement creation failed. Please try again.'
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $procurementMode,
                'message' => 'Mode of procurement created successfully.'
            ]
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(ProcurementMode $procurementMode)
    {
        return response()->json([
            'data' => [
                'data' => $procurementMode
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProcurementMode $procurementMode)
    {
        $validated = $request->validate([
            'mode_name' => 'required|unique:procurement_modes,mode_name,' . $procurementMode->id,
            'active' => 'required|in:true,false'
        ]);

        $validated['active'] = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        try {
            $procurementMode->update($validated);

            $this->logRepository->create([
                'message' => "Mode of procurement updated successfully.",
                'log_id' => $procurementMode->id,
                'log_module' => 'lib-mode-proc',
                'data' => $procurementMode
            ]);
        } catch (\Throwable $th) {
            $this->logRepository->create([
                'message' => "Mode of procurement update failed. Please try again.",
                'details' => $th->getMessage(),
                'log_id' => $procurementMode->id,
                'log_module' => 'lib-mode-proc',
                'data' => $validated
            ], isError: true);

            return response()->json([
                'message' => 'Mode of procurement update failed. Please try again.'
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $procurementMode,
                'message' => 'Mode of procurement updated successfully.'
            ]
        ]);
    }
}
