<?php

namespace App\Http\Controllers\V1\Account;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\Section;
use App\Repositories\LogRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class DivisionController extends Controller
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
        $columnSort = $request->get('column_sort', 'division_name');
        $sortDirection = $request->get('sort_direction', 'desc');
        $paginated = filter_var($request->get('paginated', true), FILTER_VALIDATE_BOOLEAN);

        $divisions = Division::query()->with([
            'sections' => function ($query) {
                $query->orderBy('section_name');
            },
            'sections.head:id,firstname,lastname',
            'head:id,firstname,lastname'
        ]);

        if (!empty($search)) {
            $divisions = $divisions->where(function($query) use ($search) {
                $query->where('id', $search)
                    ->orWhere('division_name', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('sections', function($query) use ($search) {
                        $query->where('id', $search)
                            ->orWhere('section_name', 'ILIKE', "%{$search}%");
                    });
            });
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            switch ($columnSort) {
                case 'headfullname':
                    $columnSort = 'division_head_id';
                    break;
                case 'division_name_formatted':
                    $columnSort = 'division_name';
                    break;
                default:
                    break;
            }

            $divisions = $divisions->orderBy($columnSort, $sortDirection);
        }

        if ($paginated) {
            return $divisions->paginate($perPage);
        } else {
            if (!$showInactive) $divisions = $divisions->where('active', true);

            $divisions = $showAll
                ? $divisions->get()
                : $divisions = $divisions->limit($perPage)->get();

            return response()->json([
                'data' => $divisions
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'division_name' => 'required|unique:divisions,division_name',
            'division_head_id' => 'nullable',
            'active' => 'required|in:true,false'
        ]);

        $validated['active'] = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        try {
            $division = Division::create($validated);

            $this->logRepository->create([
                'message' => "Division created successfully.",
                'log_id' => $division->id,
                'log_module' => 'account-division',
                'data' => $division
            ]);
        } catch (\Throwable $th) {
            $this->logRepository->create([
                'message' => "Division creation failed.",
                'details' => $th->getMessage(),
                'log_module' => 'account-division',
                'data' => $validated
            ], isError: true);

            return response()->json([
                'message' => 'Division creation failed. Please try again.'
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $division,
                'message' => 'Division created successfully.'
            ]
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Division $division): JsonResponse
    {
        $division->load('head');

        return response()->json([
            'data' => [
                'data' => $division
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Division $division): JsonResponse
    {
        $validated = $request->validate([
            'division_name' => 'required|unique:divisions,division_name,' . $division->id,
            'division_head_id' => 'nullable',
            'active' => 'required|in:true,false'
        ]);

        $validated['active'] = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        try {
            Section::where('division_id', $division->id)
                ->update([
                    'active' => $validated['active']
                ]);

            $division->update($validated);

            $this->logRepository->create([
                'message' => "Division updated successfully.",
                'log_id' => $division->id,
                'log_module' => 'account-division',
                'data' => $division
            ]);
        } catch (\Throwable $th) {
            $this->logRepository->create([
                'message' => "Division update failed.",
                'details' => $th->getMessage(),
                'log_id' => $division->id,
                'log_module' => 'account-division',
                'data' => $validated
            ], isError: true);

            return response()->json([
                'message' => 'Division update failed. Please try again.'
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $division,
                'message' => 'Division updated successfully.'
            ]
        ]);
    }
}
