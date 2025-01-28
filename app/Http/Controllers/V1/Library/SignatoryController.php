<?php

namespace App\Http\Controllers\V1\Library;

use App\Http\Controllers\Controller;
use App\Models\Designation;
use App\Models\Signatory;
use App\Models\SignatoryDetail;
use App\Models\User;
use App\Repositories\LogRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class SignatoryController extends Controller
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
        $columnSort = $request->get('column_sort', 'fullname');
        $sortDirection = $request->get('sort_direction', 'desc');
        $paginated = filter_var($request->get('paginated', true), FILTER_VALIDATE_BOOLEAN);

        $signatories = Signatory::query()->with([
            'details' => function ($query) {
                $query->orderBy('document');
            },
            'user'
        ]);

        if (!empty($search)) {
            $signatories = $signatories->where(function($query) use ($search){
                $query->whereRelation('user', 'firstname', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('user', 'middlename', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('user', 'lastname', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('details', 'position', 'ILIKE', "%{$search}%");;
            });
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            switch ($columnSort) {
                case 'fullname':
                    // $columnSort = 'user.firstname';
                    $columnSort = '';
                    $signatories = $signatories->orderBy(
                        User::select('firstname')->whereColumn('users.id', 'signatories.user_id')
                    );
                    break;
                default:
                    break;
            }

            if ($columnSort) {
                $signatories = $signatories->orderBy($columnSort, $sortDirection);
            }
        }

        if ($paginated) {
            return $signatories->paginate($perPage);
        } else {
            if (!$showInactive) $signatories = $signatories->where('active', true);

            $signatories = $showAll
                ? $signatories->get()
                : $signatories = $signatories->limit($perPage)->get();

            return response()->json([
                'data' => $signatories
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|unique:signatories,user_id',
            'details' => 'required|string',
            'active' => 'required|in:true,false'
        ]);

        $validated['active'] = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        try {
            $details = json_decode($validated['details']);

            $signatory = Signatory::create($validated);

            foreach ($details ?? [] as $detail) {
                if (!empty($detail->position)) {
                    $designation = Designation::updateOrCreate([
                        'designation_name' => $detail->position,
                    ], [
                        'designation_name' => $detail->position
                    ]);

                    SignatoryDetail::create([
                        'signatory_id' => $signatory->id,
                        'document' => $detail->document,
                        'signatory_type' => $detail->signatory_type,
                        'position' => $detail->position
                    ]);
                }
            }

            $this->logRepository->create([
                'message' => "Signatory created successfully.",
                'log_id' => $signatory->id,
                'log_module' => 'lib-signatory',
                'data' => $signatory
            ]);
        } catch (\Throwable $th) {
            $this->logRepository->create([
                'message' => "Signatory creation failed. Please try again.",
                'details' => $th->getMessage(),
                'log_module' => 'lib-signatory',
                'data' => $validated
            ], isError: true);

            return response()->json([
                'message' => 'Signatory creation failed. Please try again.'
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $signatory,
                'message' => 'Signatory created successfully.'
            ]
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Signatory $signatory)
    {
        return response()->json([
            'data' => [
                'data' => $signatory
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Signatory $signatory)
    {
        $validated = $request->validate([
            'user_id' => 'required|unique:signatories,user_id,' . $signatory->id,
            'details' => 'required|string',
            'active' => 'required|in:true,false'
        ]);

        $validated['active'] = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        try {
            $details = json_decode($validated['details']);

            $signatory->update($validated);

            SignatoryDetail::where('signatory_id', $signatory->id)
                ->delete();

            foreach ($details ?? [] as $detail) {
                if (!empty($detail->position)) {
                    $designation = Designation::updateOrCreate([
                        'designation_name' => $detail->position,
                    ], [
                        'designation_name' => $detail->position
                    ]);

                    SignatoryDetail::create([
                        'signatory_id' => $signatory->id,
                        'document' => $detail->document,
                        'signatory_type' => $detail->signatory_type,
                        'position' => $detail->position
                    ]);
                }
            }

            $this->logRepository->create([
                'message' => "Signatory updated successfully.",
                'log_id' => $signatory->id,
                'log_module' => 'lib-signatory',
                'data' => $signatory
            ]);
        } catch (\Throwable $th) {
            $this->logRepository->create([
                'message' => "Signatory update failed. Please try again.",
                'details' => $th->getMessage(),
                'log_id' => $signatory->id,
                'log_module' => 'lib-signatory',
                'data' => $validated
            ], isError: true);

            return response()->json([
                'message' => 'Signatory update failed. Please try again.'
            ], 422);
        }

        return response()->json([
            'data' => [
                'data' => $signatory,
                'message' => 'Signatory updated successfully.'
            ]
        ]);
    }
}
