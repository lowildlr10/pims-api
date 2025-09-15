<?php

namespace App\Http\Controllers\V1;

use App\Enums\DisbursementVoucherStatus;
use App\Enums\ObligationRequestStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\DisbursementVoucher;
use App\Models\ObligationRequest;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $showPrWorkflow = $user->tokenCan('super:*')
            || $user->tokenCan('head:*')
            || $user->tokenCan('supply:*')
            || $user->tokenCan('budget:*')
            || $user->tokenCan('accountant:*')
            || $user->tokenCan('user:*')
            || $user->tokenCan('pr:*')
            || $user->tokenCan('pr:view');
        $showP0Workflow = $user->tokenCan('super:*')
            || $user->tokenCan('head:*')
            || $user->tokenCan('supply:*')
            || $user->tokenCan('po:*')
            || $user->tokenCan('po:view');
        $showBudgetWorkflow = $user->tokenCan('super:*')
            || $user->tokenCan('head:*')
            || $user->tokenCan('budget:*')
            || $user->tokenCan('obr:*')
            || $user->tokenCan('obr:view');
        $showAccountingWorkflow = $user->tokenCan('super:*')
            || $user->tokenCan('head:*')
            || $user->tokenCan('accountant:*')
            || $user->tokenCan('dv:*')
            || $user->tokenCan('dv:view');

        // Fetch all status counts in one query for purchase request
        $prRawCounts = DB::table('purchase_requests')
            ->select('status', DB::raw('COUNT(*) as total'));

        if (
            $user->tokenCan('super:*') ||
            $user->tokenCan('head:*') ||
            $user->tokenCan('supply:*') ||
            $user->tokenCan('budget:*') ||
            $user->tokenCan('accountant:*')
        ) {}
        else {
            $prRawCounts = $prRawCounts->where('requested_by_id', $user->id);
        }

        $prRawCounts = $prRawCounts->groupBy('status')
            ->pluck('total', 'status');

        // Define status groups
        $prActiveStatuses = [
            PurchaseRequestStatus::PENDING->value,
            PurchaseRequestStatus::APPROVED_CASH_AVAILABLE->value,
            PurchaseRequestStatus::APPROVED->value,
            PurchaseRequestStatus::DISAPPROVED->value,
            PurchaseRequestStatus::FOR_CANVASSING->value,
            PurchaseRequestStatus::FOR_RECANVASSING->value,
            PurchaseRequestStatus::FOR_ABSTRACT->value,
            PurchaseRequestStatus::PARTIALLY_AWARDED->value,
            PurchaseRequestStatus::AWARDED->value,
        ];

        $prPendingApprovalStatuses = [
            PurchaseRequestStatus::PENDING->value,
            PurchaseRequestStatus::APPROVED_CASH_AVAILABLE->value,
        ];

        $prWorkflowCounts = $showPrWorkflow 
            ? [
                'draft' => $prRawCounts[PurchaseRequestStatus::DRAFT->value] ?? 0,
                'pending' => $prRawCounts[PurchaseRequestStatus::PENDING->value] ?? 0,
                'approved_cash_available' => $prRawCounts[PurchaseRequestStatus::APPROVED_CASH_AVAILABLE->value] ?? 0,
                'disapproved' => $prRawCounts[PurchaseRequestStatus::DISAPPROVED->value] ?? 0,
                'approved' => $prRawCounts[PurchaseRequestStatus::APPROVED->value] ?? 0,
                'for_canvassing' => $prRawCounts[PurchaseRequestStatus::FOR_CANVASSING->value] ?? 0,
                'for_abstract' => $prRawCounts[PurchaseRequestStatus::FOR_ABSTRACT->value] ?? 0,
                'completed' => $prRawCounts[PurchaseRequestStatus::COMPLETED->value] ?? 0,
            ]
            : [];

        // Fetch all status counts in one query for purchase order
        $poRawCounts = PurchaseOrder::query()
            ->select('status', DB::raw('COUNT(*) as total'));

        if (
            $user->tokenCan('super:*') ||
            $user->tokenCan('head:*') ||
            $user->tokenCan('supply:*') ||
            $user->tokenCan('budget:*') ||
            $user->tokenCan('accountant:*')
        ) {}
        else {
            $poRawCounts = $poRawCounts->whereRelation('purchase_request', 'requested_by_id', $user->id);
        }

        $poRawCounts = $poRawCounts->groupBy('status')
            ->pluck('total', 'status');

        $poWorkflowCounts = $showP0Workflow 
            ? [
                'draft' => $poRawCounts[PurchaseOrderStatus::DRAFT->value] ?? 0,
                'pending' => $poRawCounts[PurchaseOrderStatus::PENDING->value] ?? 0,
                'approved' => $poRawCounts[PurchaseOrderStatus::APPROVED->value] ?? 0,
                'issued' => $poRawCounts[PurchaseOrderStatus::ISSUED->value] ?? 0,
                'for_delivery' => $poRawCounts[PurchaseOrderStatus::FOR_DELIVERY->value] ?? 0,
                'delivered' => $poRawCounts[PurchaseOrderStatus::DELIVERED->value] ?? 0,
                'for_inspection' => $poRawCounts[PurchaseOrderStatus::FOR_INSPECTION->value] ?? 0,
                'inspected' => $poRawCounts[PurchaseOrderStatus::INSPECTED->value] ?? 0,
                'completed' => $poRawCounts[PurchaseOrderStatus::COMPLETED->value] ?? 0,
            ]
            : [];

        // Fetch all status counts in one query for obligation request
        $orRawCounts = ObligationRequest::query()
            ->select('status', DB::raw('COUNT(*) as total'));

        if (
            $user->tokenCan('super:*') ||
            $user->tokenCan('head:*') ||
            $user->tokenCan('supply:*') ||
            $user->tokenCan('budget:*') ||
            $user->tokenCan('accountant:*')
        ) {}
        else {
            $orRawCounts = $orRawCounts->whereRelation('purchase_request', 'requested_by_id', $user->id);
        }

        $orRawCounts = $orRawCounts->groupBy('status')
            ->pluck('total', 'status');

        // Fetch all status counts in one query for disbursement voucher
        $dvRawCounts = DisbursementVoucher::query()
            ->select('status', DB::raw('COUNT(*) as total'));

        if (
            $user->tokenCan('super:*') ||
            $user->tokenCan('head:*') ||
            $user->tokenCan('supply:*') ||
            $user->tokenCan('budget:*') ||
            $user->tokenCan('accountant:*')
        ) {}
        else {
            $dvRawCounts = $dvRawCounts->whereRelation('purchase_request', 'requested_by_id', $user->id);
        }

        $dvRawCounts = $dvRawCounts->groupBy('status')
            ->pluck('total', 'status');

        $budgetWorkflowCounts = $showBudgetWorkflow
            ? [
                'draft' => $orRawCounts[ObligationRequestStatus::DRAFT->value] ?? 0,
                'pending' => $orRawCounts[ObligationRequestStatus::PENDING->value] ?? 0,
                'disapproved' => $orRawCounts[ObligationRequestStatus::DISAPPROVED->value] ?? 0,
                'obligated' => $orRawCounts[ObligationRequestStatus::OBLIGATED->value] ?? 0,
            ]
            : [];

        $accountingWorkflowCounts = $showAccountingWorkflow
            ? [
                'draft' => $dvRawCounts[DisbursementVoucherStatus::DRAFT->value] ?? 0,
                'pending' => $dvRawCounts[DisbursementVoucherStatus::PENDING->value] ?? 0,
                'disapproved' => $dvRawCounts[DisbursementVoucherStatus::DISAPPROVED->value] ?? 0,
                'for_payment' => $dvRawCounts[DisbursementVoucherStatus::FOR_PAYMENT->value] ?? 0,
                'paid' => $dvRawCounts[DisbursementVoucherStatus::PAID->value] ?? 0,
            ]
            : [];

        return response()->json([
            'data' => [
                'active' => collect($prRawCounts)->only($prActiveStatuses)->sum(),
                'pending_approval' => collect($prRawCounts)->only($prPendingApprovalStatuses)->sum(),
                'disapproved' => $prRawCounts[PurchaseRequestStatus::DISAPPROVED->value] ?? 0,
                'completed' => $prRawCounts[PurchaseRequestStatus::COMPLETED->value] ?? 0,
                'show_pr_workflow' => $showPrWorkflow,
                'pr_workflow' => $prWorkflowCounts,
                'show_po_workflow' => $showP0Workflow,
                'po_workflow' => $poWorkflowCounts,
                'show_budget_workflow' => $showBudgetWorkflow,
                'budget_workflow' => $budgetWorkflowCounts,
                'show_accounting_workflow' => $showAccountingWorkflow,
                'accounting_workflow' => $accountingWorkflowCounts,
            ]
        ]);
    }
}
