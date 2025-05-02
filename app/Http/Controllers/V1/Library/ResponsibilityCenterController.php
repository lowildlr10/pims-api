<?php

namespace App\Http\Controllers\V1\Library;

use App\Http\Controllers\Controller;
use App\Models\ResponsibilityCenter;
use App\Repositories\LogRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ResponsibilityCenterController extends Controller
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
        $columnSort = $request->get('column_sort', 'code');
        $sortDirection = $request->get('sort_direction', 'desc');
        $paginated = filter_var($request->get('paginated', true), FILTER_VALIDATE_BOOLEAN);

        $responsibilityCenter = ResponsibilityCenter::query();

        if (!empty($search)) {
            $responsibilityCenter = $responsibilityCenter->where(function($query) use ($search){
                $query->whereRaw("CAST(id AS TEXT) = ?", [$search])
                    ->orWhere('code', 'ILIKE', "%{$search}%")
                    ->orWhere('description', 'ILIKE', "%{$search}%");
            });
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            switch ($columnSort) {
                case 'code_formatted':
                    $columnSort = 'code';
                    break;
                default:
                    break;
            }

            $responsibilityCenter = $responsibilityCenter->orderBy($columnSort, $sortDirection);
        }

        if ($paginated) {
            return $responsibilityCenter->paginate($perPage);
        } else {
            if (!$showInactive) $responsibilityCenter = $responsibilityCenter->where('active', true);

            $responsibilityCenter = $showAll
                ? $responsibilityCenter->get()
                : $responsibilityCenter = $responsibilityCenter->limit($perPage)->get();

            return response()->json([
                'data' => $responsibilityCenter
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|unique:responsibility_centers,code',
            'description' => 'nullable|string',
            'active' => 'required|in:true,false'
        ]);

        $validated['active'] = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        try {
            $responsibilityCenter = ResponsibilityCenter::create($validated);

            $this->logRepository->create([
                'message' => "Responsibility center created successfully.",
                'log_id' => $responsibilityCenter->id,
                'log_module' => 'lib-responsibility-center',
                'data' => $responsibilityCenter
            ]);
        } catch (\Throwable $th) {
            $this->logRepository->create([
                'message' => "Responsibility center creation failed. Please try again.",
                'details' => $th->getMessage(),
                'log_module' => 'lib-responsibility-center',
                'data' => $validated
            ], isError: true);

            return response()->json([
                'message' => 'Responsibility center creation failed. Please try again.'
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $responsibilityCenter,
                'message' => 'Responsibility center created successfully.'
            ]
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(ResponsibilityCenter $responsibilityCenter)
    {
        return response()->json([
            'data' => [
                'data' => $responsibilityCenter
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ResponsibilityCenter $responsibilityCenter)
    {
        $validated = $request->validate([
            'code' => 'required|unique:responsibility_centers,code,' . $responsibilityCenter->id,
            'description' => 'nullable|string',
            'active' => 'required|in:true,false'
        ]);

        $validated['active'] = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        try {
            $responsibilityCenter->update($validated);

            $this->logRepository->create([
                'message' => "Responsibility center updated successfully.",
                'log_id' => $responsibilityCenter->id,
                'log_module' => 'lib-responsibility-center',
                'data' => $responsibilityCenter
            ]);
        } catch (\Throwable $th) {
            $this->logRepository->create([
                'message' => "Responsibility center update failed. Please try again.",
                'details' => $th->getMessage(),
                'log_id' => $responsibilityCenter->id,
                'log_module' => 'lib-responsibility-center',
                'data' => $validated
            ], isError: true);

            return response()->json([
                'message' => 'Responsibility center update failed. Please try again.'
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $responsibilityCenter,
                'message' => 'Responsibility center updated successfully.'
            ]
        ]);
    }
}
