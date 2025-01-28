<?php

namespace App\Http\Controllers\V1\Library;

use App\Http\Controllers\Controller;
use App\Models\MfoPap;
use App\Repositories\LogRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class MfoPapController extends Controller
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

        $mfoPaps = MfoPap::query();

        if (!empty($search)) {
            $mfoPaps = $mfoPaps->where(function($query) use ($search){
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

            $mfoPaps = $mfoPaps->orderBy($columnSort, $sortDirection);
        }

        if ($paginated) {
            return $mfoPaps->paginate($perPage);
        } else {
            if (!$showInactive) $mfoPaps = $mfoPaps->where('active', true);

            $mfoPaps = $showAll
                ? $mfoPaps->get()
                : $mfoPaps = $mfoPaps->limit($perPage)->get();

            return response()->json([
                'data' => $mfoPaps
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|unique:mfo_paps,code',
            'description' => 'nullable',
            'active' => 'required|in:true,false'
        ]);

        $validated['active'] = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        try {
            $mfoPap = MfoPap::create($validated);

            $this->logRepository->create([
                'message' => "MFO/PAP created successfully.",
                'log_id' => $mfoPap->id,
                'log_module' => 'lib-mfo-pap',
                'data' => $mfoPap
            ]);
        } catch (\Throwable $th) {
            $this->logRepository->create([
                'message' => "MFO/PAP creation failed. Please try again.",
                'details' => $th->getMessage(),
                'log_module' => 'lib-mfo-pap',
                'data' => $validated
            ], isError: true);

            return response()->json([
                'message' => 'MFO/PAP creation failed. Please try again.'
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $mfoPap,
                'message' => 'MFO/PAP created successfully.'
            ]
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(MfoPap $mfoPap)
    {
        return response()->json([
            'data' => [
                'data' => $mfoPap
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MfoPap $mfoPap)
    {
        $validated = $request->validate([
            'code' => 'required|unique:mfo_paps,code,' . $mfoPap->id,
            'description' => 'nullable',
            'active' => 'required|in:true,false'
        ]);

        $validated['active'] = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        try {
            $mfoPap->update($validated);

            $this->logRepository->create([
                'message' => "MFO/PAP updated successfully.",
                'log_id' => $mfoPap->id,
                'log_module' => 'lib-mfo-pap',
                'data' => $mfoPap
            ]);
        } catch (\Throwable $th) {
            $this->logRepository->create([
                'message' => "MFO/PAP update failed. Please try again.",
                'details' => $th->getMessage(),
                'log_id' => $mfoPap->id,
                'log_module' => 'lib-mfo-pap',
                'data' => $validated
            ], isError: true);

            return response()->json([
                'message' => 'MFO/PAP update failed. Please try again.'
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $mfoPap,
                'message' => 'MFO/PAP updated successfully.'
            ]
        ]);
    }
}
