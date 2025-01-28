<?php

namespace App\Http\Controllers\V1\Library;

use App\Http\Controllers\Controller;
use App\Models\ResposibilityCenter;
use App\Repositories\LogRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ResposibilityCenterController extends Controller
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

        $resposibilityCenter = ResposibilityCenter::query();

        if (!empty($search)) {
            $resposibilityCenter = $resposibilityCenter->where(function($query) use ($search){
                $query->where('code', 'ILIKE', "%{$search}%")
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

            $resposibilityCenter = $resposibilityCenter->orderBy($columnSort, $sortDirection);
        }

        if ($paginated) {
            return $resposibilityCenter->paginate($perPage);
        } else {
            if (!$showInactive) $resposibilityCenter = $resposibilityCenter->where('active', true);

            $resposibilityCenter = $showAll
                ? $resposibilityCenter->get()
                : $resposibilityCenter = $resposibilityCenter->limit($perPage)->get();

            return response()->json([
                'data' => $resposibilityCenter
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
            'description' => 'required|string',
            'active' => 'required|in:true,false'
        ]);

        $validated['active'] = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        try {
            $resposibilityCenter = ResposibilityCenter::create($validated);

            $this->logRepository->create([
                'message' => "Responsibility center created successfully.",
                'log_id' => $resposibilityCenter->id,
                'log_module' => 'lib-responsibility-center',
                'data' => $resposibilityCenter
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
                'data' => $resposibilityCenter,
                'message' => 'Responsibility center created successfully.'
            ]
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(ResposibilityCenter $resposibilityCenter)
    {
        return response()->json([
            'data' => [
                'data' => $resposibilityCenter
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ResposibilityCenter $resposibilityCenter)
    {
        $validated = $request->validate([
            'code' => 'required|unique:responsibility_centers,code,' . $resposibilityCenter->id,
            'description' => 'required|string',
            'active' => 'required|in:true,false'
        ]);

        $validated['active'] = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        try {
            $resposibilityCenter->update($validated);

            $this->logRepository->create([
                'message' => "Responsibility center updated successfully.",
                'log_id' => $resposibilityCenter->id,
                'log_module' => 'lib-responsibility-center',
                'data' => $resposibilityCenter
            ]);
        } catch (\Throwable $th) {
            $this->logRepository->create([
                'message' => "Responsibility center update failed. Please try again.",
                'details' => $th->getMessage(),
                'log_id' => $resposibilityCenter->id,
                'log_module' => 'lib-responsibility-center',
                'data' => $validated
            ], isError: true);

            return response()->json([
                'message' => 'Responsibility center update failed. Please try again.'
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $resposibilityCenter,
                'message' => 'Responsibility center updated successfully.'
            ]
        ]);
    }
}
