<?php

namespace App\Http\Controllers\V1\Library;

use App\Http\Controllers\Controller;
use App\Models\FunctionProgramProject;
use App\Repositories\LogRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class FunctionProgramProjectController extends Controller
{
    private LogRepository $logRepository;

    public function __construct(LogRepository $logRepository)
    {
        $this->logRepository = $logRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse|LengthAwarePaginator
    {
        $search = trim($request->get('search', ''));
        $perPage = $request->get('per_page', 5);
        $showAll = filter_var($request->get('show_all', false), FILTER_VALIDATE_BOOLEAN);
        $showInactive = filter_var($request->get('show_inactive', false), FILTER_VALIDATE_BOOLEAN);
        $columnSort = $request->get('column_sort', 'code');
        $sortDirection = $request->get('sort_direction', 'desc');
        $paginated = filter_var($request->get('paginated', true), FILTER_VALIDATE_BOOLEAN);

        $functionProgramProjects = FunctionProgramProject::query();

        if (! empty($search)) {
            $functionProgramProjects = $functionProgramProjects->where(function ($query) use ($search) {
                $query->whereRaw('CAST(id AS TEXT) = ?', [$search])
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

            $functionProgramProjects = $functionProgramProjects->orderBy($columnSort, $sortDirection);
        }

        if ($paginated) {
            return $functionProgramProjects->paginate($perPage);
        } else {
            if (! $showInactive) {
                $functionProgramProjects = $functionProgramProjects->where('active', true);
            }

            $functionProgramProjects = $showAll
                ? $functionProgramProjects->get()
                : $functionProgramProjects = $functionProgramProjects->limit($perPage)->get();

            return response()->json([
                'data' => $functionProgramProjects,
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|unique:function_program_projects,code',
            'description' => 'nullable',
            'active' => 'required|boolean',
        ]);

        $validated['active'] = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        try {
            $functionProgramProject = FunctionProgramProject::create($validated);

            $this->logRepository->create([
                'message' => 'FPP created successfully.',
                'log_id' => $functionProgramProject->id,
                'log_module' => 'lib-fpp',
                'data' => $functionProgramProject,
            ]);
        } catch (\Throwable $th) {
            $this->logRepository->create([
                'message' => 'FPP creation failed. Please try again.',
                'details' => $th->getMessage(),
                'log_module' => 'lib-fpp',
                'data' => $validated,
            ], isError: true);

            return response()->json([
                'message' => 'FPP creation failed. Please try again.',
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $functionProgramProject,
                'message' => 'FPP created successfully.',
            ],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(FunctionProgramProject $functionProgramProject)
    {
        return response()->json([
            'data' => [
                'data' => $functionProgramProject,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FunctionProgramProject $functionProgramProject)
    {
        $validated = $request->validate([
            'code' => 'required|unique:function_program_projects,code,'.$functionProgramProject->id,
            'description' => 'nullable',
            'active' => 'required|boolean',
        ]);

        $validated['active'] = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        try {
            $functionProgramProject->update($validated);

            $this->logRepository->create([
                'message' => 'FPP updated successfully.',
                'log_id' => $functionProgramProject->id,
                'log_module' => 'lib-fpp',
                'data' => $functionProgramProject,
            ]);
        } catch (\Throwable $th) {
            $this->logRepository->create([
                'message' => 'FPP update failed. Please try again.',
                'details' => $th->getMessage(),
                'log_id' => $functionProgramProject->id,
                'log_module' => 'lib-fpp',
                'data' => $validated,
            ], isError: true);

            return response()->json([
                'message' => 'FPP update failed. Please try again.',
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $functionProgramProject,
                'message' => 'FPP updated successfully.',
            ],
        ]);
    }
}
