<?php

namespace App\Http\Controllers\V1\Library;

use App\Http\Controllers\Controller;
use App\Models\AccountClassification;
use App\Repositories\LogRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class AccountClassificationController extends Controller
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
        $columnSort = $request->get('column_sort', 'classification_name');
        $sortDirection = $request->get('sort_direction', 'desc');
        $paginated = filter_var($request->get('paginated', true), FILTER_VALIDATE_BOOLEAN);

        $accountClassifications = AccountClassification::query();

        if (! empty($search)) {
            $accountClassifications = $accountClassifications->where(function ($query) use ($search) {
                $query->whereRaw('CAST(id AS TEXT) = ?', [$search])
                    ->orWhere('classification_name', 'ILIKE', "%{$search}%");
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

            $accountClassifications = $accountClassifications->orderBy($columnSort, $sortDirection);
        }

        if ($paginated) {
            return $accountClassifications->paginate($perPage);
        } else {
            if (! $showInactive) {
                $accountClassifications = $accountClassifications->where('active', true);
            }

            $accountClassifications = $showAll
                ? $accountClassifications->get()
                : $accountClassifications = $accountClassifications->limit($perPage)->get();

            return response()->json([
                'data' => $accountClassifications,
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'classification_name' => 'required|unique:account_classifications,classification_name',
            'active' => 'required|boolean',
        ]);

        $validated['active'] = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        try {
            $accountClassification = AccountClassification::create($validated);

            $this->logRepository->create([
                'message' => 'Account classification created successfully.',
                'log_id' => $accountClassification->id,
                'log_module' => 'lib-uacs-class',
                'data' => $accountClassification,
            ]);
        } catch (\Throwable $th) {
            $this->logRepository->create([
                'message' => 'Account classification creation failed. Please try again.',
                'details' => $th->getMessage(),
                'log_module' => 'lib-uacs-class',
                'data' => $validated,
            ], isError: true);

            return response()->json([
                'message' => 'Account classification creation failed. Please try again.',
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $accountClassification,
                'message' => 'Account classification created successfully.',
            ],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(AccountClassification $accountClassification)
    {
        return response()->json([
            'data' => [
                'data' => $accountClassification,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AccountClassification $accountClassification)
    {
        $validated = $request->validate([
            'classification_name' => 'required|unique:account_classifications,classification_name,'.$accountClassification->id,
            'active' => 'required|boolean',
        ]);

        $validated['active'] = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        try {
            $accountClassification->update($validated);

            $this->logRepository->create([
                'message' => 'Section updated successfully.',
                'log_id' => $accountClassification->id,
                'log_module' => 'lib-uacs-class',
                'data' => $accountClassification,
            ]);
        } catch (\Throwable $th) {
            $this->logRepository->create([
                'message' => 'Section update failed.',
                'details' => $th->getMessage(),
                'log_id' => $accountClassification->id,
                'log_module' => 'lib-uacs-class',
                'data' => $validated,
            ], isError: true);

            return response()->json([
                'message' => 'Account classification update failed. Please try again.',
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $accountClassification,
                'message' => 'Account classification updated successfully.',
            ],
        ]);
    }
}
