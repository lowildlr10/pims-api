<?php

namespace App\Http\Controllers\V1\Account;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Section;
use App\Repositories\LogRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class DepartmentController extends Controller
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
        $user = Auth::user();
        
        $search = trim($request->get('search', ''));
        $perPage = $request->get('per_page', 5);
        $showAll = filter_var($request->get('show_all', false), FILTER_VALIDATE_BOOLEAN);
        $showInactive = filter_var($request->get('show_inactive', false), FILTER_VALIDATE_BOOLEAN);
        $columnSort = $request->get('column_sort', 'department_name');
        $sortDirection = $request->get('sort_direction', 'desc');
        $paginated = filter_var($request->get('paginated', true), FILTER_VALIDATE_BOOLEAN);

        $departments = Department::query()->with([
            'sections' => function ($query) {
                $query->orderBy('section_name');
            },
            'sections.head:id,firstname,lastname',
            'head:id,firstname,lastname',
        ]);

        if (! empty($search)) {
            $departments = $departments->where(function ($query) use ($search) {
                $query->whereRaw('CAST(id AS TEXT) = ?', [$search])
                    ->orWhere('department_name', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('sections', function ($query) use ($search) {
                        $query->whereRaw('CAST(id AS TEXT) = ?', [$search])
                            ->orWhere('section_name', 'ILIKE', "%{$search}%");
                    });
            });
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            switch ($columnSort) {
                case 'headfullname':
                    $columnSort = 'department_head_id';
                    break;
                case 'department_name_formatted':
                    $columnSort = 'department_name';
                    break;
                default:
                    break;
            }

            $departments = $departments->orderBy($columnSort, $sortDirection);
        }

        if ($paginated) {
            return $departments->paginate($perPage);
        } else {
            if (! $showInactive) {
                $departments = $departments->where('active', true);
            }

            if ($user->tokenCan('super:*')
                || $user->tokenCan('head:*')
                || $user->tokenCan('supply:*')
                || $user->tokenCan('budget:*')
                || $user->tokenCan('accountant:*')
            ) {
            } else {
                $departments = $departments->where('id', $user->department_id);
            }

            $departments = $showAll
                ? $departments->get()
                : $departments = $departments->limit($perPage)->get();

            return response()->json([
                'data' => $departments,
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'department_name' => 'required|unique:departments,department_name',
            'department_head_id' => 'nullable',
            'active' => 'required|boolean',
        ]);

        $validated['active'] = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        try {
            $department = Department::create($validated);

            $this->logRepository->create([
                'message' => 'Department created successfully.',
                'log_id' => $department->id,
                'log_module' => 'account-department',
                'data' => $department,
            ]);
        } catch (\Throwable $th) {
            $this->logRepository->create([
                'message' => 'Department creation failed.',
                'details' => $th->getMessage(),
                'log_module' => 'account-department',
                'data' => $validated,
            ], isError: true);

            return response()->json([
                'message' => 'Department creation failed. Please try again.',
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $department,
                'message' => 'Department created successfully.',
            ],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(department $department): JsonResponse
    {
        $department->load('head');

        return response()->json([
            'data' => [
                'data' => $department,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Department $department): JsonResponse
    {
        $validated = $request->validate([
            'department_name' => 'required|unique:departments,department_name,'.$department->id,
            'department_head_id' => 'nullable',
            'active' => 'required|boolean',
        ]);

        $validated['active'] = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        try {
            Section::where('department_id', $department->id)
                ->update([
                    'active' => $validated['active'],
                ]);

            $department->update($validated);

            $this->logRepository->create([
                'message' => 'Department updated successfully.',
                'log_id' => $department->id,
                'log_module' => 'account-department',
                'data' => $department,
            ]);
        } catch (\Throwable $th) {
            $this->logRepository->create([
                'message' => 'Department update failed.',
                'details' => $th->getMessage(),
                'log_id' => $department->id,
                'log_module' => 'account-department',
                'data' => $validated,
            ], isError: true);

            return response()->json([
                'message' => 'Department update failed. Please try again.',
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $department,
                'message' => 'Department updated successfully.',
            ],
        ]);
    }
}
