<?php

namespace App\Http\Controllers\V1\Library;

use App\Http\Controllers\Controller;
use App\Models\UacsCode;
use App\Repositories\LogRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class UacsCodeController extends Controller
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

        $uacsCodes = UacsCode::query()->with('classification');

        if (! empty($search)) {
            $uacsCodes = $uacsCodes->where(function ($query) use ($search) {
                $query->whereRaw('CAST(id AS TEXT) = ?', [$search])
                    ->orWhere('account_title', 'ILIKE', "%{$search}%")
                    ->orWhere('code', 'ILIKE', "%{$search}%")
                    ->orWhere('description', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('classification', 'classification_name', 'ILIKE', "%{$search}%");
            });
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            switch ($columnSort) {
                case 'code_formatted':
                    $columnSort = 'code';
                    break;
                case 'classification_name':
                    $columnSort = 'classification.classification_name';
                    break;
                default:
                    break;
            }

            $uacsCodes = $uacsCodes->orderBy($columnSort, $sortDirection);
        }

        if ($paginated) {
            return $uacsCodes->paginate($perPage);
        } else {
            if (! $showInactive) {
                $uacsCodes = $uacsCodes->where('active', true);
            }

            $uacsCodes = $showAll
                ? $uacsCodes->get()
                : $uacsCodes = $uacsCodes->limit($perPage)->get();

            return response()->json([
                'data' => $uacsCodes,
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'classification_id' => 'required',
            'account_title' => 'required|string',
            'code' => 'required|unique:uacs_codes,code',
            'description' => 'nullable',
            'active' => 'required|boolean',
        ]);

        $validated['active'] = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        try {
            $uacsCode = UacsCode::create($validated);

            $this->logRepository->create([
                'message' => 'UACS code created successfully.',
                'log_id' => $uacsCode->id,
                'log_module' => 'lib-uacs-code',
                'data' => $uacsCode,
            ]);
        } catch (\Throwable $th) {
            $this->logRepository->create([
                'message' => 'UACS code creation failed. Please try again.',
                'details' => $th->getMessage(),
                'log_module' => 'lib-uacs-code',
                'data' => $validated,
            ], isError: true);

            return response()->json([
                'message' => 'UACS code creation failed. Please try again.',
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $uacsCode,
                'message' => 'UACS code created successfully.',
            ],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(UacsCode $uacsCode)
    {
        $uacsCode->load('classification');

        return response()->json([
            'data' => [
                'data' => $uacsCode,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UacsCode $uacsCode)
    {
        $validated = $request->validate([
            'classification_id' => 'required',
            'account_title' => 'required|string',
            'code' => 'required|unique:uacs_codes,code,'.$uacsCode->id,
            'description' => 'nullable',
            'active' => 'required|boolean',
        ]);

        $validated['active'] = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        try {
            $uacsCode->update($validated);

            $this->logRepository->create([
                'message' => 'Section updated successfully.',
                'log_id' => $uacsCode->id,
                'log_module' => 'lib-uacs-code',
                'data' => $uacsCode,
            ]);
        } catch (\Throwable $th) {
            $this->logRepository->create([
                'message' => 'Section update failed.',
                'details' => $th->getMessage(),
                'log_id' => $uacsCode->id,
                'log_module' => 'lib-uacs-code',
                'data' => $validated,
            ], isError: true);

            return response()->json([
                'message' => 'UACS code update failed. Please try again.',
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $uacsCode,
                'message' => 'UACS code updated successfully.',
            ],
        ]);
    }
}
