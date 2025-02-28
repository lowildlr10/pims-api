<?php

namespace App\Http\Controllers\V1;

use App\Enums\PurchaseRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\AbstractQuotation;
use App\Models\FundingSource;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\User;
use App\Repositories\LogRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class AbstractQuotationController extends Controller
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
        $user = auth()->user();

        $search = trim($request->get('search', ''));
        $perPage = $request->get('per_page', 50);
        $showAll = filter_var($request->get('show_all', false), FILTER_VALIDATE_BOOLEAN);
        $columnSort = $request->get('column_sort', 'pr_no');
        $sortDirection = $request->get('sort_direction', 'desc');
        $paginated = filter_var($request->get('paginated', true), FILTER_VALIDATE_BOOLEAN);

        $purchaseRequests = PurchaseRequest::query()->with([
            'funding_source:id,title,location_id',
            'funding_source.location:id,location_name',
            'section:id,section_name',

            'items' => function ($query) {
                $query->orderBy('item_sequence');
            },
            'items.unit_issue:id,unit_name',

            'aoqs' => function ($query) {
                $query->orderByRaw("CAST(REPLACE(abstract_no, '-', '') AS VARCHAR) asc");
            },
            'aoqs.bids_awards_committee:id,committee_name',
            'aoqs.mode_procurement:id,mode_name',
            'aoqs.signatory_twg_chairperson:id,user_id',
            'aoqs.signatory_twg_chairperson.user:id,firstname,middlename,lastname,allow_signature,signature',
            'aoqs.signatory_twg_chairperson.detail' => function ($query) {
                $query->where('document', 'aoq')
                    ->where('signatory_type', 'twg_chairperson');
            },
            'aoqs.signatory_twg_member_1:id,user_id',
            'aoqs.signatory_twg_member_1.user:id,firstname,middlename,lastname,allow_signature,signature',
            'aoqs.signatory_twg_member_1.detail' => function ($query) {
                $query->where('document', 'aoq')
                    ->where('signatory_type', 'twg_member_1');
            },
            'aoqs.signatory_twg_member_2:id,user_id',
            'aoqs.signatory_twg_member_2.user:id,firstname,middlename,lastname,allow_signature,signature',
            'aoqs.signatory_twg_member_2.detail' => function ($query) {
                $query->where('document', 'aoq')
                    ->where('signatory_type', 'twg_member_2');
            },
            'aoqs.signatory_chairman:id,user_id',
            'aoqs.signatory_chairman.user:id,firstname,middlename,lastname,allow_signature,signature',
            'aoqs.signatory_chairman.detail' => function ($query) {
                $query->where('document', 'aoq')
                    ->where('signatory_type', 'chairman');
            },
            'aoqs.signatory_vice_chairman:id,user_id',
            'aoqs.signatory_vice_chairman.user:id,firstname,middlename,lastname,allow_signature,signature',
            'aoqs.signatory_vice_chairman.detail' => function ($query) {
                $query->where('document', 'aoq')
                    ->where('signatory_type', 'vice_chairman');
            },
            'aoqs.signatory_member_1:id,user_id',
            'aoqs.signatory_member_1.user:id,firstname,middlename,lastname,allow_signature,signature',
            'aoqs.signatory_member_1.detail' => function ($query) {
                $query->where('document', 'aoq')
                    ->where('signatory_type', 'member_1');
            },
            'aoqs.signatory_member_2:id,user_id',
            'aoqs.signatory_member_2.user:id,firstname,middlename,lastname,allow_signature,signature',
            'aoqs.signatory_member_2.detail' => function ($query) {
                $query->where('document', 'aoq')
                    ->where('signatory_type', 'member_2');
            },
            'aoqs.signatory_member_3:id,user_id',
            'aoqs.signatory_member_3.user:id,firstname,middlename,lastname,allow_signature,signature',
            'aoqs.signatory_member_3.detail' => function ($query) {
                $query->where('document', 'aoq')
                    ->where('signatory_type', 'member_3');
            },
            'aoqs.items' => function($query) {
                $query->orderBy(
                    PurchaseRequestItem::select('item_sequence')
                        ->whereColumn(
                            'abstract_quotation_items.pr_item_id', 'purchase_request_items.id'
                        ),
                    'asc'
                );
            },
            'aoqs.items.pr_item:id,item_sequence,quantity,description,stock_no,awarded_to_id',
            'aoqs.items.details',
            'aoqs.items.details.supplier:id,supplier_name',

            'requestor:id,firstname,lastname,position_id,allow_signature,signature',
            'requestor.position:id,position_name',

            'signatory_cash_available:id,user_id',
            'signatory_cash_available.user:id,firstname,middlename,lastname,allow_signature,signature',
            'signatory_cash_available.detail' => function ($query) {
                $query->where('document', 'pr')
                    ->where('signatory_type', 'cash_availability');
            },

            'signatory_approval:id,user_id',
            'signatory_approval.user:id,firstname,middlename,lastname,allow_signature,signature',
            'signatory_approval.detail' => function ($query) {
                $query->where('document', 'pr')
                    ->where('signatory_type', 'approved_by');
            }
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
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(AbstractQuotation $abstractQuotation)
    {
        return response()->json([
            'data' => [
                'data' => $abstractQuotation
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AbstractQuotation $abstractQuotation)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AbstractQuotation $abstractQuotation)
    {
        //
    }
}
