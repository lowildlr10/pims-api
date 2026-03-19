<?php

namespace App\Repositories;

use App\Enums\NotificationType;
use App\Interfaces\NotificationRepositoryInterface;
use App\Models\AbstractQuotation;
use App\Models\DisbursementVoucher;
use App\Models\InventoryIssuance;
use App\Models\ObligationRequest;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\RequestQuotation;
use App\Models\User;
use App\Notifications\DisbursementVoucherNotifications\CreatedNotification as DisbursementVoucherCreatedNotification;
use App\Notifications\DisbursementVoucherNotifications\ForPaymentNotification as DisbursementVoucherForPaymentNotification;
use App\Notifications\InventoryIssuanceNotifications\IssuedNotification as InventoryIssuanceIssuedNotification;
use App\Notifications\ObligationRequestNotifications\CreatedNotification as ObligationRequestCreatedNotification;
use App\Notifications\PurchaseOrderNotifications\ApprovedNotification as PurchaseOrderApprovedNotification;
use App\Notifications\PurchaseOrderNotifications\CompletedNotification as PurchaseOrderCompletedNotification;
use App\Notifications\PurchaseOrderNotifications\DeliveredNotification as PurchaseOrderDeliveredNotification;
use App\Notifications\PurchaseOrderNotifications\ForDeliveryNotification as PurchaseOrderForDeliveryNotification;
use App\Notifications\PurchaseOrderNotifications\ForDisbursementNotification as PurchaseOrderForDisbursementNotification;
use App\Notifications\PurchaseOrderNotifications\ForInspectionNotification as PurchaseOrderForInspectionNotification;
use App\Notifications\PurchaseOrderNotifications\ForObligationNotification as PurchaseOrderForObligationNotification;
use App\Notifications\PurchaseOrderNotifications\ForPaymentNotification as PurchaseOrderForPaymentNotification;
use App\Notifications\PurchaseOrderNotifications\InspectedNotification as PurchaseOrderInspectedNotification;
use App\Notifications\PurchaseOrderNotifications\IssuedNotification as PurchaseOrderIssuedNotification;
use App\Notifications\PurchaseOrderNotifications\ObligatedNotification as PurchaseOrderObligatedNotification;
use App\Notifications\PurchaseOrderNotifications\PendingNotification as PurchaseOrderPendingNotification;
use App\Notifications\PurchaseRequestNofications\ApprovedCashAvailableNotifcation as PurchaseRequestApprovedCashAvailableNotifcation;
use App\Notifications\PurchaseRequestNofications\ApprovedNotification as PurchaseRequestApprovedNotifcation;
use App\Notifications\PurchaseRequestNofications\AwardedNotification;
use App\Notifications\PurchaseRequestNofications\CancelledNotification as PurchaseRequestCancelledNotification;
use App\Notifications\PurchaseRequestNofications\CanvassingNotification;
use App\Notifications\PurchaseRequestNofications\CompletedNotification as PurchaseRequestCompletedNotification;
use App\Notifications\PurchaseRequestNofications\DisapprovedNotification as PurchaseRequestDisapprovedNotification;
use App\Notifications\PurchaseRequestNofications\ForAbstractNotification;
use App\Notifications\PurchaseRequestNofications\PartiallyAwardedNotification;
use App\Notifications\PurchaseRequestNofications\PendingNotification as PurchaseRequestPendingNotification;

class NotificationRepository implements NotificationRepositoryInterface
{
    public function notify(NotificationType $notificationType, array $data): void
    {
        try {
            switch ($notificationType) {
                case $notificationType::PR_PENDING:
                    $this->notifyPurchaseRequestPending($data['pr']);
                    break;

                case $notificationType::PR_APPROVED_CASH_AVAILABLE:
                    $this->notifyPurchaseRequestApprovedCashAvailable($data['pr']);
                    break;

                case $notificationType::PR_APPROVED:
                    $this->notifyPurchaseRequestApproved($data['pr']);
                    break;

                case $notificationType::PR_DISAPPROVED:
                    $this->notifyPurchaseRequestDisapproved($data['pr']);
                    break;

                case $notificationType::PR_CANCELLED:
                    $this->notifyPurchaseRequestCancelled($data['pr']);
                    break;

                case $notificationType::PR_CAMVASSING:
                    $this->notifyCanvassing($data['pr'], $data['rfq']);
                    break;

                case $notificationType::PR_FOR_ABSTRACT:
                    $this->notifyForAbstract($data['pr'], $data['rfq']);
                    break;

                case $notificationType::PR_PARTIALLY_AWARDED:
                    $this->notifyPartiallyAwarded($data['pr'], $data['aoq']);
                    break;

                case $notificationType::PR_AWARDED:
                    $this->notifyAwarded($data['pr'], $data['aoq']);
                    break;

                case $notificationType::PR_COMPLETED:
                    $this->notifyCompleted($data['pr']);
                    break;

                case $notificationType::PO_PENDING:
                    $this->notifyPurchaseOrderPending($data['po']);
                    break;

                case $notificationType::PO_APPROVED:
                    $this->notifyPurchaseOrderApproved($data['po']);
                    break;

                case $notificationType::PO_ISSUED:
                    $this->notifyPurchaseOrderIssued($data['po']);
                    break;

                case $notificationType::PO_FOR_DELIVERY:
                    $this->notifyPurchaseOrderForDelivery($data['po']);
                    break;

                case $notificationType::PO_DELIVERED:
                    $this->notifyPurchaseOrderDelivered($data['po']);
                    break;

                case $notificationType::PO_FOR_INSPECTION:
                    $this->notifyPurchaseOrderForInspection($data['po']);
                    break;

                case $notificationType::PO_INSPECTED:
                    $this->notifyPurchaseOrderInspected($data['po']);
                    break;

                case $notificationType::PO_FOR_OBLIGATION:
                    $this->notifyPurchaseOrderForObligation($data['po']);
                    break;

                case $notificationType::PO_OBLIGATED:
                    $this->notifyPurchaseOrderObligated($data['po']);
                    break;

                case $notificationType::PO_FOR_DISBURSEMENT:
                    $this->notifyPurchaseOrderForDisbursement($data['po']);
                    break;

                case $notificationType::PO_FOR_PAYMENT:
                    $this->notifyPurchaseOrderForPayment($data['po']);
                    break;

                case $notificationType::PO_COMPLETED:
                    $this->notifyPurchaseOrderCompleted($data['po']);
                    break;

                case $notificationType::OBR_CREATED:
                    $this->notifyObligationRequestCreated($data['obr']);
                    break;

                case $notificationType::DV_CREATED:
                    $this->notifyDisbursementVoucherCreated($data['dv']);
                    break;

                case $notificationType::DV_FOR_PAYMENT:
                    $this->notifyDisbursementVoucherForPayment($data['dv']);
                    break;

                case $notificationType::INV_ISSUANCE_ISSUED:
                    $this->notifyInventoryIssuanceIssued($data['issuance']);
                    break;

                default:
                    // code...
                    break;
            }
        } catch (\Throwable $th) {
            // throw $th;
        }
    }

    private function notifyPurchaseRequestPending(PurchaseRequest $pr): void
    {
        $pr->load('signatory_cash_available');

        $notifiables = User::whereHas('roles', function ($query) {
            $query->whereJsonContains('permissions', 'pr:*')
                ->orWhereJsonContains('permissions', 'pr:approve-cash-available')
                ->orWhereJsonContains('permissions', 'supply:*')
                ->orWhereJsonContains('permissions', 'budget:*')
                ->orWhereJsonContains('permissions', 'super:*');
        })->pluck('id')->toArray();

        $userIds = array_unique([
            $pr->requested_by_id,
            $pr->signatory_cash_available->user_id,
            ...$notifiables,
        ]);

        $users = User::whereIn('id', $userIds)->get();

        $users->each(function ($user) use ($pr) {
            $user->notify((
                new PurchaseRequestPendingNotification($pr)
            )->onQueue('notification'));
        });
    }

    private function notifyPurchaseRequestApprovedCashAvailable(PurchaseRequest $pr): void
    {
        $pr->load('signatory_approval');

        $notifiables = User::whereHas('roles', function ($query) {
            $query->whereJsonContains('permissions', 'pr:*')
                ->orWhereJsonContains('permissions', 'pr:approve')
                ->orWhereJsonContains('permissions', 'supply:*')
                ->orWhereJsonContains('permissions', 'head:*')
                ->orWhereJsonContains('permissions', 'super:*');
        })->pluck('id')->toArray();

        $userIds = array_unique([
            $pr->requested_by_id,
            $pr->signatory_approval->user_id,
            ...$notifiables,
        ]);

        $users = User::whereIn('id', $userIds)->get();

        $users->each(function ($user) use ($pr) {
            $user->notify((
                new PurchaseRequestApprovedCashAvailableNotifcation($pr)
            )->onQueue('notification'));
        });
    }

    private function notifyPurchaseRequestApproved(PurchaseRequest $pr): void
    {
        $notifiables = User::whereHas('roles', function ($query) {
            $query->whereJsonContains('permissions', 'pr:*')
                ->orWhereJsonContains('permissions', 'supply:*')
                ->orWhereJsonContains('permissions', 'super:*');
        })->pluck('id')->toArray();

        $userIds = array_unique([
            $pr->requested_by_id,
            ...$notifiables,
        ]);

        $users = User::whereIn('id', $userIds)->get();

        $users->each(function ($user) use ($pr) {
            $user->notify((
                new PurchaseRequestApprovedNotifcation($pr)
            )->onQueue('notification'));
        });
    }

    private function notifyPurchaseRequestDisapproved(PurchaseRequest $pr): void
    {
        $notifiables = User::whereHas('roles', function ($query) {
            $query->whereJsonContains('permissions', 'pr:*')
                ->orWhereJsonContains('permissions', 'supply:*')
                ->orWhereJsonContains('permissions', 'super:*');
        })->pluck('id')->toArray();

        $userIds = array_unique([
            $pr->requested_by_id,
            ...$notifiables,
        ]);

        $users = User::whereIn('id', $userIds)->get();

        $users->each(function ($user) use ($pr) {
            $user->notify((
                new PurchaseRequestDisapprovedNotification($pr)
            )->onQueue('notification'));
        });
    }

    private function notifyPurchaseRequestCancelled(PurchaseRequest $pr): void
    {
        $notifiables = User::whereHas('roles', function ($query) {
            $query->whereJsonContains('permissions', 'pr:*')
                ->orWhereJsonContains('permissions', 'supply:*')
                ->orWhereJsonContains('permissions', 'super:*');
        })->pluck('id')->toArray();

        $userIds = array_unique([
            $pr->requested_by_id,
            ...$notifiables,
        ]);

        $users = User::whereIn('id', $userIds)->get();

        $users->each(function ($user) use ($pr) {
            $user->notify((
                new PurchaseRequestCancelledNotification($pr)
            )->onQueue('notification'));
        });
    }

    private function notifyCanvassing(PurchaseRequest $pr, RequestQuotation $rfq): void
    {
        $notifiables = User::whereHas('roles', function ($query) {
            $query->whereJsonContains('permissions', 'pr:*')
                ->orWhereJsonContains('permissions', 'supply:*')
                ->orWhereJsonContains('permissions', 'super:*');
        })->pluck('id')->toArray();

        $userIds = array_unique([
            $pr->requested_by_id,
            ...$notifiables,
        ]);

        $users = User::whereIn('id', $userIds)->get();

        $users->each(function ($user) use ($pr, $rfq) {
            $user->notify((
                new CanvassingNotification($pr, $rfq)
            )->onQueue('notification'));
        });
    }

    private function notifyForAbstract(PurchaseRequest $pr, RequestQuotation $rfq): void
    {
        $notifiables = User::whereHas('roles', function ($query) {
            $query->whereJsonContains('permissions', 'pr:*')
                ->orWhereJsonContains('permissions', 'supply:*')
                ->orWhereJsonContains('permissions', 'super:*');
        })->pluck('id')->toArray();

        $userIds = array_unique([
            $pr->requested_by_id,
            ...$notifiables,
        ]);

        $users = User::whereIn('id', $userIds)->get();

        $users->each(function ($user) use ($pr, $rfq) {
            $user->notify((
                new ForAbstractNotification($pr, $rfq)
            )->onQueue('notification'));
        });
    }

    private function notifyPartiallyAwarded(PurchaseRequest $pr, AbstractQuotation $aoq): void
    {
        $notifiables = User::whereHas('roles', function ($query) {
            $query->whereJsonContains('permissions', 'pr:*')
                ->orWhereJsonContains('permissions', 'supply:*')
                ->orWhereJsonContains('permissions', 'super:*');
        })->pluck('id')->toArray();

        $userIds = array_unique([
            $pr->requested_by_id,
            ...$notifiables,
        ]);

        $users = User::whereIn('id', $userIds)->get();

        $users->each(function ($user) use ($pr, $aoq) {
            $user->notify((
                new PartiallyAwardedNotification($pr, $aoq)
            )->onQueue('notification'));
        });
    }

    private function notifyAwarded(PurchaseRequest $pr, AbstractQuotation $aoq): void
    {
        $notifiables = User::whereHas('roles', function ($query) {
            $query->whereJsonContains('permissions', 'pr:*')
                ->orWhereJsonContains('permissions', 'supply:*')
                ->orWhereJsonContains('permissions', 'super:*');
        })->pluck('id')->toArray();

        $userIds = array_unique([
            $pr->requested_by_id,
            ...$notifiables,
        ]);

        $users = User::whereIn('id', $userIds)->get();

        $users->each(function ($user) use ($pr, $aoq) {
            $user->notify((
                new AwardedNotification($pr, $aoq)
            )->onQueue('notification'));
        });
    }

    private function notifyCompleted(PurchaseRequest $pr): void
    {
        $notifiables = User::whereHas('roles', function ($query) {
            $query->whereJsonContains('permissions', 'pr:*')
                ->orWhereJsonContains('permissions', 'supply:*')
                ->orWhereJsonContains('permissions', 'super:*');
        })->pluck('id')->toArray();

        $userIds = array_unique([
            $pr->requested_by_id,
            ...$notifiables,
        ]);

        $users = User::whereIn('id', $userIds)->get();

        $users->each(function ($user) use ($pr) {
            $user->notify((
                new PurchaseRequestCompletedNotification($pr)
            )->onQueue('notification'));
        });
    }

    private function getPurchaseOrderNotifiables(PurchaseOrder $po, array $permissions): array
    {
        $notifiables = User::whereHas('roles', function ($query) use ($permissions) {
            $first = array_shift($permissions);
            $query->whereJsonContains('permissions', $first);
            foreach ($permissions as $permission) {
                $query->orWhereJsonContains('permissions', $permission);
            }
        })->pluck('id')->toArray();

        $prCreatorId = PurchaseRequest::where('id', $po->purchase_request_id)
            ->value('requested_by_id');

        return array_unique(array_filter([
            $prCreatorId,
            ...$notifiables,
        ]));
    }

    private function notifyPurchaseOrderPending(PurchaseOrder $po): void
    {
        $userIds = $this->getPurchaseOrderNotifiables($po, [
            'super:*', 'supply:*', 'head:*', 'po:*', 'po:approve',
        ]);

        User::whereIn('id', $userIds)->get()->each(function ($user) use ($po) {
            $user->notify((new PurchaseOrderPendingNotification($po))->onQueue('notification'));
        });
    }

    private function notifyPurchaseOrderApproved(PurchaseOrder $po): void
    {
        $userIds = $this->getPurchaseOrderNotifiables($po, ['super:*', 'supply:*', 'po:*']);

        User::whereIn('id', $userIds)->get()->each(function ($user) use ($po) {
            $user->notify((new PurchaseOrderApprovedNotification($po))->onQueue('notification'));
        });
    }

    private function notifyPurchaseOrderIssued(PurchaseOrder $po): void
    {
        $userIds = $this->getPurchaseOrderNotifiables($po, ['super:*', 'supply:*', 'po:*']);

        User::whereIn('id', $userIds)->get()->each(function ($user) use ($po) {
            $user->notify((new PurchaseOrderIssuedNotification($po))->onQueue('notification'));
        });
    }

    private function notifyPurchaseOrderForDelivery(PurchaseOrder $po): void
    {
        $userIds = $this->getPurchaseOrderNotifiables($po, ['super:*', 'supply:*', 'po:*']);

        User::whereIn('id', $userIds)->get()->each(function ($user) use ($po) {
            $user->notify((new PurchaseOrderForDeliveryNotification($po))->onQueue('notification'));
        });
    }

    private function notifyPurchaseOrderDelivered(PurchaseOrder $po): void
    {
        $userIds = $this->getPurchaseOrderNotifiables($po, ['super:*', 'supply:*', 'po:*']);

        User::whereIn('id', $userIds)->get()->each(function ($user) use ($po) {
            $user->notify((new PurchaseOrderDeliveredNotification($po))->onQueue('notification'));
        });
    }

    private function notifyPurchaseOrderForInspection(PurchaseOrder $po): void
    {
        $userIds = $this->getPurchaseOrderNotifiables($po, ['super:*', 'supply:*', 'po:*']);

        User::whereIn('id', $userIds)->get()->each(function ($user) use ($po) {
            $user->notify((new PurchaseOrderForInspectionNotification($po))->onQueue('notification'));
        });
    }

    private function notifyPurchaseOrderInspected(PurchaseOrder $po): void
    {
        $userIds = $this->getPurchaseOrderNotifiables($po, ['super:*', 'supply:*', 'po:*']);

        User::whereIn('id', $userIds)->get()->each(function ($user) use ($po) {
            $user->notify((new PurchaseOrderInspectedNotification($po))->onQueue('notification'));
        });
    }

    private function notifyPurchaseOrderForObligation(PurchaseOrder $po): void
    {
        $userIds = $this->getPurchaseOrderNotifiables($po, ['super:*', 'supply:*', 'po:*']);

        User::whereIn('id', $userIds)->get()->each(function ($user) use ($po) {
            $user->notify((new PurchaseOrderForObligationNotification($po))->onQueue('notification'));
        });
    }

    private function notifyPurchaseOrderObligated(PurchaseOrder $po): void
    {
        $userIds = $this->getPurchaseOrderNotifiables($po, ['super:*', 'supply:*', 'po:*']);

        User::whereIn('id', $userIds)->get()->each(function ($user) use ($po) {
            $user->notify((new PurchaseOrderObligatedNotification($po))->onQueue('notification'));
        });
    }

    private function notifyPurchaseOrderForDisbursement(PurchaseOrder $po): void
    {
        $userIds = $this->getPurchaseOrderNotifiables($po, ['super:*', 'supply:*', 'po:*']);

        User::whereIn('id', $userIds)->get()->each(function ($user) use ($po) {
            $user->notify((new PurchaseOrderForDisbursementNotification($po))->onQueue('notification'));
        });
    }

    private function notifyPurchaseOrderForPayment(PurchaseOrder $po): void
    {
        $userIds = $this->getPurchaseOrderNotifiables($po, ['super:*', 'supply:*', 'po:*']);

        User::whereIn('id', $userIds)->get()->each(function ($user) use ($po) {
            $user->notify((new PurchaseOrderForPaymentNotification($po))->onQueue('notification'));
        });
    }

    private function notifyPurchaseOrderCompleted(PurchaseOrder $po): void
    {
        $userIds = $this->getPurchaseOrderNotifiables($po, ['super:*', 'supply:*', 'po:*']);

        User::whereIn('id', $userIds)->get()->each(function ($user) use ($po) {
            $user->notify((new PurchaseOrderCompletedNotification($po))->onQueue('notification'));
        });
    }

    private function notifyObligationRequestCreated(ObligationRequest $obr): void
    {
        $notifiables = User::whereHas('roles', function ($query) {
            $query->whereJsonContains('permissions', 'budget:*')
                ->orWhereJsonContains('permissions', 'obr:*')
                ->orWhereJsonContains('permissions', 'obr:view')
                ->orWhereJsonContains('permissions', 'obr:update');
        })->pluck('id')->toArray();

        User::whereIn('id', $notifiables)->get()->each(function ($user) use ($obr) {
            $user->notify((new ObligationRequestCreatedNotification($obr))->onQueue('notification'));
        });
    }

    private function notifyDisbursementVoucherCreated(DisbursementVoucher $dv): void
    {
        $notifiables = User::whereHas('roles', function ($query) {
            $query->whereJsonContains('permissions', 'accountant:*')
                ->orWhereJsonContains('permissions', 'dv:*')
                ->orWhereJsonContains('permissions', 'dv:view')
                ->orWhereJsonContains('permissions', 'dv:update');
        })->pluck('id')->toArray();

        User::whereIn('id', $notifiables)->get()->each(function ($user) use ($dv) {
            $user->notify((new DisbursementVoucherCreatedNotification($dv))->onQueue('notification'));
        });
    }

    private function notifyDisbursementVoucherForPayment(DisbursementVoucher $dv): void
    {
        $notifiables = User::whereHas('roles', function ($query) {
            $query->whereJsonContains('permissions', 'accountant:*')
                ->orWhereJsonContains('permissions', 'treasurer:*')
                ->orWhereJsonContains('permissions', 'dv:*')
                ->orWhereJsonContains('permissions', 'dv:paid');
        })->pluck('id')->toArray();

        User::whereIn('id', $notifiables)->get()->each(function ($user) use ($dv) {
            $user->notify((new DisbursementVoucherForPaymentNotification($dv))->onQueue('notification'));
        });
    }

    private function notifyInventoryIssuanceIssued(InventoryIssuance $issuance): void
    {
        $notifiables = User::whereHas('roles', function ($query) {
            $query->whereJsonContains('permissions', 'super:*')
                ->orWhereJsonContains('permissions', 'supply:*')
                ->orWhereJsonContains('permissions', 'inv-issuance:*')
                ->orWhereJsonContains('permissions', 'inv-issuance:issue');
        })->pluck('id')->toArray();

        $userIds = array_unique(array_filter([
            $issuance->received_by_id,
            ...$notifiables,
        ]));

        User::whereIn('id', $userIds)->get()->each(function ($user) use ($issuance) {
            $user->notify((new InventoryIssuanceIssuedNotification($issuance))->onQueue('notification'));
        });
    }
}
