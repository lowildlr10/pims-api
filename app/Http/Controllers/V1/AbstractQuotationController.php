<?php

namespace App\Http\Controllers\V1;

use App\Enums\PurchaseRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\AbstractQuotation;
use App\Models\FundingSource;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\User;
use App\Repositories\AbstractQuotationRepository;
use App\Repositories\LogRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class AbstractQuotationController extends Controller
{
    private LogRepository $logRepository;
    private AbstractQuotationRepository $abstractQuotationRepository;

    public function __construct(
        LogRepository $logRepository,
        AbstractQuotationRepository $abstractQuotationRepository
    )
    {
        $this->logRepository = $logRepository;
        $this->abstractQuotationRepository = $abstractQuotationRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse | LengthAwarePaginator
    {
        $user = auth()->user();

        $search = trim($request->get('search', ''));
        $perPage = $request->get('per_page', 50);
        $showAll = filter_var($request->get('show_all', false), FILTER_VALIDATE_BOOLEAN);
        $columnSort = $request->get('column_sort', 'pr_no');
        $sortDirection = $request->get('sort_direction', 'desc');
        $paginated = filter_var($request->get('paginated', true), FILTER_VALIDATE_BOOLEAN);

        $purchaseRequests = PurchaseRequest::query()
            ->select('id', 'pr_no', 'pr_date', 'purpose', 'status', 'requested_by_id')
            ->with([
                'funding_source:id,title',
                'requestor:id,firstname,lastname',
                'aoqs' => function ($query) {
                    $query->select(
                            'id',
                            'purchase_request_id',
                            'solicitation_no',
                            'solicitation_date',
                            'abstract_no',
                            'opened_on',
                            'mode_procurement_id',
                            'bac_action',
                            'status'
                        )
                        ->orderByRaw("CAST(REPLACE(abstract_no, '-', '') AS VARCHAR) asc");
                },
                'aoqs.mode_procurement:id,mode_name'
            ])->whereIn('status', [
                PurchaseRequestStatus::FOR_RECANVASSING,
                PurchaseRequestStatus::FOR_ABSTRACT,
                PurchaseRequestStatus::PARTIALLY_AWARDED,
                PurchaseRequestStatus::AWARDED,
                PurchaseRequestStatus::COMPLETED
            ]);

        if ($user->tokenCan('super:*')
            || $user->tokenCan('head:*')
            || $user->tokenCan('supply:*')
            || $user->tokenCan('budget:*')
            || $user->tokenCan('accounting:*')
        ) {} else {
            $purchaseRequests = $purchaseRequests->where('requested_by_id', $user->id);
        }

        if (!empty($search)) {
            $purchaseRequests = $purchaseRequests->where(function($query) use ($search){
                $query->where('id', 'ILIKE', "%{$search}%")
                    ->orWhere('pr_no', 'ILIKE', "%{$search}%")
                    ->orWhere('pr_date', 'ILIKE', "%{$search}%")
                    ->orWhere('sai_no', 'ILIKE', "%{$search}%")
                    ->orWhere('sai_date', 'ILIKE', "%{$search}%")
                    ->orWhere('alobs_no', 'ILIKE', "%{$search}%")
                    ->orWhere('alobs_date', 'ILIKE', "%{$search}%")
                    ->orWhere('purpose', 'ILIKE', "%{$search}%")
                    ->orWhere('status', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('funding_source', 'title', 'ILIKE' , "%{$search}%")
                    ->orWhereRelation('section', 'section_name', 'ILIKE' , "%{$search}%")
                    ->orWhereRelation('requestor', function ($query) use ($search) {
                        $query->where('firstname', 'ILIKE', "%{$search}%")
                            ->orWhere('lastname', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereRelation('signatory_cash_available.user', function ($query) use ($search) {
                        $query->where('firstname', 'ILIKE', "%{$search}%")
                            ->orWhere('lastname', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereRelation('signatory_approval.user', function ($query) use ($search) {
                        $query->where('firstname', 'ILIKE', "%{$search}%")
                            ->orWhere('lastname', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereRelation('aoqs', function ($query) use ($search) {
                        $query->where('id', 'ILIKE', "%{$search}%")
                            ->orWhere('solicitation_no', 'ILIKE', "%{$search}%")
                            ->orWhere('solicitation_date', 'ILIKE', "%{$search}%")
                            ->orWhere('abstract_no', 'ILIKE', "%{$search}%")
                            ->orWhere('bac_action', 'ILIKE', "%{$search}%")
                            ->orWhere('status', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereRelation('aoqs.bids_awards_committee', function ($query) use ($search) {
                        $query->where('committee_name', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereRelation('aoqs.mode_procurement', function ($query) use ($search) {
                        $query->where('mode_name', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereRelation('aoqs.signatory_twg_chairperson.user', function ($query) use ($search) {
                        $query->where('firstname', 'ILIKE', "%{$search}%")
                            ->orWhere('lastname', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereRelation('aoqs.signatory_twg_member_1.user', function ($query) use ($search) {
                        $query->where('firstname', 'ILIKE', "%{$search}%")
                            ->orWhere('lastname', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereRelation('aoqs.signatory_twg_member_2.user', function ($query) use ($search) {
                        $query->where('firstname', 'ILIKE', "%{$search}%")
                            ->orWhere('lastname', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereRelation('aoqs.signatory_chairman.user', function ($query) use ($search) {
                        $query->where('firstname', 'ILIKE', "%{$search}%")
                            ->orWhere('lastname', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereRelation('aoqs.signatory_vice_chairman.user', function ($query) use ($search) {
                        $query->where('firstname', 'ILIKE', "%{$search}%")
                            ->orWhere('lastname', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereRelation('aoqs.signatory_member_1.user', function ($query) use ($search) {
                        $query->where('firstname', 'ILIKE', "%{$search}%")
                            ->orWhere('lastname', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereRelation('aoqs.signatory_member_2.user', function ($query) use ($search) {
                        $query->where('firstname', 'ILIKE', "%{$search}%")
                            ->orWhere('lastname', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereRelation('aoqs.signatory_member_3.user', function ($query) use ($search) {
                        $query->where('firstname', 'ILIKE', "%{$search}%")
                            ->orWhere('lastname', 'ILIKE', "%{$search}%");
                    });
            });
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            switch ($columnSort) {
                case 'pr_no':
                    $purchaseRequests = $purchaseRequests->orderByRaw("CAST(REPLACE(pr_no, '-', '') AS INTEGER) {$sortDirection}");
                    break;

                case 'pr_date_formatted':
                    $purchaseRequests = $purchaseRequests->orderBy('pr_date', $sortDirection);
                    break;

                case 'funding_source_title':
                    $purchaseRequests = $purchaseRequests->orderBy(
                        FundingSource::select('title')->whereColumn('funding_sources.id', 'purchase_requests.funding_source_id'),
                        $sortDirection
                    );
                    break;

                case 'purpose_formatted':
                    $purchaseRequests = $purchaseRequests->orderBy('purpose', $sortDirection);
                    break;

                case 'requestor_fullname':
                    $purchaseRequests = $purchaseRequests->orderBy(
                        User::select('firstname')->whereColumn('users.id', 'purchase_requests.requested_by_id'),
                        $sortDirection
                    );
                    break;

                case 'status_formatted':
                    $purchaseRequests = $purchaseRequests->orderBy('status', $sortDirection);
                    break;

                default:
                    $purchaseRequests = $purchaseRequests->orderBy($columnSort, $sortDirection);
                    break;
            }
        }

        if ($paginated) {
            return $purchaseRequests->paginate($perPage);
        } else {
            $purchaseRequests = $showAll
                ? $purchaseRequests->get()
                : $purchaseRequests = $purchaseRequests->limit($perPage)->get();

            return response()->json([
                'data' => $purchaseRequests
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    // public function store(Request $request)
    // {
    //     //
    // }

    /**
     * Display the specified resource.
     */
    public function show(AbstractQuotation $abstractQuotation): JsonResponse
    {
        $abstractQuotation->load([
            'bids_awards_committee:id,committee_name',
            'mode_procurement:id,mode_name',
            'signatory_twg_chairperson:id,user_id',
            'signatory_twg_chairperson.user:id,firstname,middlename,lastname,allow_signature,signature',
            'signatory_twg_chairperson.detail' => function ($query) {
                $query->where('document', 'aoq')
                    ->where('signatory_type', 'twg_chairperson');
            },
            'signatory_twg_member_1:id,user_id',
            'signatory_twg_member_1.user:id,firstname,middlename,lastname,allow_signature,signature',
            'signatory_twg_member_1.detail' => function ($query) {
                $query->where('document', 'aoq')
                    ->where('signatory_type', 'twg_member_1');
            },
            'signatory_twg_member_2:id,user_id',
            'signatory_twg_member_2.user:id,firstname,middlename,lastname,allow_signature,signature',
            'signatory_twg_member_2.detail' => function ($query) {
                $query->where('document', 'aoq')
                    ->where('signatory_type', 'twg_member_2');
            },
            'signatory_chairman:id,user_id',
            'signatory_chairman.user:id,firstname,middlename,lastname,allow_signature,signature',
            'signatory_chairman.detail' => function ($query) {
                $query->where('document', 'aoq')
                    ->where('signatory_type', 'chairman');
            },
            'signatory_vice_chairman:id,user_id',
            'signatory_vice_chairman.user:id,firstname,middlename,lastname,allow_signature,signature',
            'signatory_vice_chairman.detail' => function ($query) {
                $query->where('document', 'aoq')
                    ->where('signatory_type', 'vice_chairman');
            },
            'signatory_member_1:id,user_id',
            'signatory_member_1.user:id,firstname,middlename,lastname,allow_signature,signature',
            'signatory_member_1.detail' => function ($query) {
                $query->where('document', 'aoq')
                    ->where('signatory_type', 'member_1');
            },
            'signatory_member_2:id,user_id',
            'signatory_member_2.user:id,firstname,middlename,lastname,allow_signature,signature',
            'signatory_member_2.detail' => function ($query) {
                $query->where('document', 'aoq')
                    ->where('signatory_type', 'member_2');
            },
            'signatory_member_3:id,user_id',
            'signatory_member_3.user:id,firstname,middlename,lastname,allow_signature,signature',
            'signatory_member_3.detail' => function ($query) {
                $query->where('document', 'aoq')
                    ->where('signatory_type', 'member_3');
            },
            'items' => function($query) {
                $query->orderBy(
                    PurchaseRequestItem::select('item_sequence')
                        ->whereColumn(
                            'abstract_quotation_items.pr_item_id', 'purchase_request_items.id'
                        ),
                    'asc'
                );
            },
            'items.awardee:id,supplier_name',
            'items.pr_item:id,unit_issue_id,item_sequence,quantity,description,stock_no',
            'items.pr_item.unit_issue:id,unit_name',
            'items.details',
            'items.details.supplier:id,supplier_name',
            'purchase_request:id,purpose'
        ]);

        return response()->json([
            'data' => [
                'data' => $abstractQuotation
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AbstractQuotation $abstractQuotation): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'bids_awards_committee_id' => 'required',
            'mode_procurement_id' => 'required',
            'solicitation_no' => 'required|string',
            'solicitation_date' => 'required',
            'opened_on' => 'nullable',
            'bac_action' => 'nullable',
            'sig_twg_chairperson_id' => 'required',
            'sig_twg_member_1_id' => 'required',
            'sig_twg_member_2_id' => 'required',
            'sig_chairman_id' => 'required',
            'sig_vice_chairman_id' => 'required',
            'sig_member_1_id' => 'required',
            'sig_member_2_id' => 'required',
            'sig_member_3_id' => 'required',
            'items' => 'required|string',
        ]);

        try {
            $message = 'Abstract of bids and quotation updated successfully.';

            $this->abstractQuotationRepository->storeUpdate($validated, $abstractQuotation);

            $abstractQuotation->load('items');

            $this->logRepository->create([
                'message' => $message,
                'log_id' => $abstractQuotation->id,
                'log_module' => 'aoq',
                'data' => $abstractQuotation
            ]);

            return response()->json([
                'data' => [
                    'data' => $abstractQuotation,
                    'message' => $message
                ]
            ]);
        } catch (\Throwable $th) {
            $message = 'Abstract of bids or quotation update failed.';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $abstractQuotation->id,
                'log_module' => 'aoq',
                'data' => $validated
            ], isError: true);

            return response()->json([
                'message' => "$message Please try again."
            ], 422);
        }
    }
}
