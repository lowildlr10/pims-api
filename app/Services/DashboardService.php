<?php

namespace App\Services;

use App\Enums\DisbursementVoucherStatus;
use App\Enums\ObligationRequestStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequestStatus;
use App\Models\DisbursementVoucher;
use App\Models\ObligationRequest;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getDashboardData(string $userId, array $permissions): array
    {
        $showPrWorkflow = in_array('super:*', $permissions) || in_array('head:*', $permissions)
            || in_array('supply:*', $permissions) || in_array('budget:*', $permissions)
            || in_array('accountant:*', $permissions) || in_array('user:*', $permissions)
            || in_array('pr:*', $permissions) || in_array('pr:view', $permissions);

        $showPoWorkflow = in_array('super:*', $permissions) || in_array('head:*', $permissions)
            || in_array('supply:*', $permissions) || in_array('po:*', $permissions)
            || in_array('po:view', $permissions);

        $showBudgetWorkflow = in_array('super:*', $permissions) || in_array('head:*', $permissions)
            || in_array('budget:*', $permissions) || in_array('obr:*', $permissions)
            || in_array('obr:view', $permissions);

        $showAccountingWorkflow = in_array('super:*', $permissions) || in_array('head:*', $permissions)
            || in_array('accountant:*', $permissions) || in_array('dv:*', $permissions)
            || in_array('dv:view', $permissions);

        $isPrivileged = in_array('super:*', $permissions) || in_array('head:*', $permissions)
            || in_array('supply:*', $permissions) || in_array('budget:*', $permissions)
            || in_array('accountant:*', $permissions);

        // Purchase Request counts
        $prRawCounts = $this->getPurchaseRequestCounts($userId, $isPrivileged);
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

        // Purchase Order counts
        $poRawCounts = $this->getPurchaseOrderCounts($userId, $isPrivileged);
        $poWorkflowCounts = $showPoWorkflow
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

        // Obligation Request counts
        $orRawCounts = $this->getObligationRequestCounts($userId, $isPrivileged);
        $budgetWorkflowCounts = $showBudgetWorkflow
            ? [
                'draft' => $orRawCounts[ObligationRequestStatus::DRAFT->value] ?? 0,
                'pending' => $orRawCounts[ObligationRequestStatus::PENDING->value] ?? 0,
                'disapproved' => $orRawCounts[ObligationRequestStatus::DISAPPROVED->value] ?? 0,
                'obligated' => $orRawCounts[ObligationRequestStatus::OBLIGATED->value] ?? 0,
            ]
            : [];

        // Disbursement Voucher counts
        $dvRawCounts = $this->getDisbursementVoucherCounts($userId, $isPrivileged);
        $accountingWorkflowCounts = $showAccountingWorkflow
            ? [
                'draft' => $dvRawCounts[DisbursementVoucherStatus::DRAFT->value] ?? 0,
                'pending' => $dvRawCounts[DisbursementVoucherStatus::PENDING->value] ?? 0,
                'disapproved' => $dvRawCounts[DisbursementVoucherStatus::DISAPPROVED->value] ?? 0,
                'for_payment' => $dvRawCounts[DisbursementVoucherStatus::FOR_PAYMENT->value] ?? 0,
                'paid' => $dvRawCounts[DisbursementVoucherStatus::PAID->value] ?? 0,
            ]
            : [];

        return [
            'active' => collect($prRawCounts)->only($prActiveStatuses)->sum(),
            'pending_approval' => collect($prRawCounts)->only($prPendingApprovalStatuses)->sum(),
            'disapproved' => $prRawCounts[PurchaseRequestStatus::DISAPPROVED->value] ?? 0,
            'completed' => $prRawCounts[PurchaseRequestStatus::COMPLETED->value] ?? 0,
            'show_pr_workflow' => $showPrWorkflow,
            'pr_workflow' => $prWorkflowCounts,
            'show_po_workflow' => $showPoWorkflow,
            'po_workflow' => $poWorkflowCounts,
            'show_budget_workflow' => $showBudgetWorkflow,
            'budget_workflow' => $budgetWorkflowCounts,
            'show_accounting_workflow' => $showAccountingWorkflow,
            'accounting_workflow' => $accountingWorkflowCounts,
        ];
    }

    private function getPurchaseRequestCounts(string $userId, bool $isPrivileged): array
    {
        $query = DB::table('purchase_requests')
            ->select('status', DB::raw('COUNT(*) as total'));

        if (! $isPrivileged) {
            $query->where('requested_by_id', $userId);
        }

        return $query->groupBy('status')->pluck('total', 'status')->toArray();
    }

    private function getPurchaseOrderCounts(string $userId, bool $isPrivileged): array
    {
        $query = PurchaseOrder::query()
            ->select('status', DB::raw('COUNT(*) as total'));

        if (! $isPrivileged) {
            $query->whereRelation('purchase_request', 'requested_by_id', $userId);
        }

        return $query->groupBy('status')->pluck('total', 'status')->toArray();
    }

    private function getObligationRequestCounts(string $userId, bool $isPrivileged): array
    {
        $query = ObligationRequest::query()
            ->select('status', DB::raw('COUNT(*) as total'));

        if (! $isPrivileged) {
            $query->whereRelation('purchase_request', 'requested_by_id', $userId);
        }

        return $query->groupBy('status')->pluck('total', 'status')->toArray();
    }

    private function getDisbursementVoucherCounts(string $userId, bool $isPrivileged): array
    {
        $query = DisbursementVoucher::query()
            ->select('status', DB::raw('COUNT(*) as total'));

        if (! $isPrivileged) {
            $query->whereRelation('purchase_request', 'requested_by_id', $userId);
        }

        return $query->groupBy('status')->pluck('total', 'status')->toArray();
    }
}
