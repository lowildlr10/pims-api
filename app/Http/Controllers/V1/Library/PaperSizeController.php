<?php

namespace App\Http\Controllers\V1\Library;

use App\Http\Controllers\Controller;
use App\Models\PaperSize;
use App\Repositories\LogRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class PaperSizeController extends Controller
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
        $columnSort = $request->get('column_sort', 'paper_type');
        $sortDirection = $request->get('sort_direction', 'desc');
        $paginated = filter_var($request->get('paginated', true), FILTER_VALIDATE_BOOLEAN);

        $paperSizes = PaperSize::query();

        if (!empty($search)) {
            $paperSizes = $paperSizes->where(function($query) use ($search){
                $query->whereRaw("CAST(id AS TEXT) = ?", [$search])
                    ->orWhere('paper_type', 'ILIKE', "%{$search}%")
                    ->orWhere('unit', 'ILIKE', "%{$search}%")
                    ->orWhere('width', 'ILIKE', "%{$search}%")
                    ->orWhere('height', 'ILIKE', "%{$search}%");
            });
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            $paperSizes = $paperSizes->orderBy($columnSort, $sortDirection);
        }

        if ($paginated) {
            return $paperSizes->paginate($perPage);
        } else {
            $paperSizes = $showAll
                ? $paperSizes->get()
                : $paperSizes = $paperSizes->limit($perPage)->get();

            return response()->json([
                'data' => $paperSizes
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'paper_type' => 'required|unique:paper_sizes,paper_type',
            'unit' => 'required|in:mm,cm,in',
            'width' => 'required|numeric',
            'height' => 'required|numeric'
        ]);

        try {
            $paperSize = PaperSize::create($validated);

            $this->logRepository->create([
                'message' => "Paper type created successfully.",
                'log_id' => $paperSize->id,
                'log_module' => 'lib-paper-size',
                'data' => $paperSize
            ]);
        } catch (\Throwable $th) {
            $this->logRepository->create([
                'message' => "Paper type creation failed. Please try again.",
                'details' => $th->getMessage(),
                'log_module' => 'lib-paper-size',
                'data' => $validated
            ], isError: true);

            return response()->json([
                'message' => 'Paper type creation failed. Please try again.'
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $paperSize,
                'message' => 'Paper type created successfully.'
            ]
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(PaperSize $paperSize)
    {
        return response()->json([
            'data' => [
                'data' => $paperSize
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PaperSize $paperSize)
    {
        $validated = $request->validate([
            'paper_type' => 'required|unique:paper_sizes,paper_type,' . $paperSize->id,
            'unit' => 'required|in:mm,cm,in',
            'width' => 'required|numeric',
            'height' => 'required|numeric'
        ]);

        try {
            $paperSize->update($validated);

            $this->logRepository->create([
                'message' => "Paper type updated successfully.",
                'log_id' => $paperSize->id,
                'log_module' => 'lib-paper-size',
                'data' => $paperSize
            ]);
        } catch (\Throwable $th) {
            $this->logRepository->create([
                'message' => "Paper type update failed. Please try again.",
                'details' => $th->getMessage(),
                'log_id' => $paperSize->id,
                'log_module' => 'lib-paper-size',
                'data' => $validated
            ], isError: true);

            return response()->json([
                'message' => 'Paper type update failed. Please try again.'
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $paperSize,
                'message' => 'Paper type updated successfully.'
            ]
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function delete(PaperSize $paperSize)
    {
        //
    }
}
