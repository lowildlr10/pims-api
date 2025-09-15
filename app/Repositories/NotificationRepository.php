<?php

namespace App\Repositories;

use App\Enums\NotificationType;
use App\Interfaces\NotificationRepositoryInterface;
use App\Models\AbstractQuotation;
use App\Models\PurchaseRequest;
use App\Models\RequestQuotation;
use App\Models\User;
use App\Notifications\PurchaseRequestNofications\ApprovedCashAvailableNotifcation as PurchaseRequestApprovedCashAvailableNotifcation;
use App\Notifications\PurchaseRequestNofications\ApprovedNotification as PurchaseRequestApprovedNotifcation;
use App\Notifications\PurchaseRequestNofications\AwardedNotification;
use App\Notifications\PurchaseRequestNofications\CancelledNotification as PurchaseRequestCancelledNotification;
use App\Notifications\PurchaseRequestNofications\CanvassingNotification;
use App\Notifications\PurchaseRequestNofications\DisapprovedNotification as PurchaseRequestDisapprovedNotification;
use App\Notifications\PurchaseRequestNofications\ForAbstractNotification;
use App\Notifications\PurchaseRequestNofications\PendingNotification as PurchaseRequestPendingNotification;
use Exception;
use ValueError;

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
                
                default:
                    # code...
                    break;
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    private function notifyPurchaseRequestPending(PurchaseRequest $pr): void
    {
        $pr->load('signatory_cash_available');

        $notifiables = User::whereHas('roles', function ($query) {
            $query->whereJsonContains('permissions', 'pr:*')
                ->orWhereJsonContains('permissions', 'pr:approve-cash-available')
                ->orWhereJsonContains('permissions', 'supply:*')
                ->orWhereJsonContains('permissions', 'super:*');
        })->pluck('id')->toArray();

        $userIds = array_unique([
            $pr->requested_by_id,
            $pr->signatory_cash_available->user_id,
            ...$notifiables
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
            ...$notifiables
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
            $query->whereJsonContains('permissions', 'supply:*')
                ->orWhereJsonContains('permissions', 'super:*');
        })->pluck('id')->toArray();

        $userIds = array_unique([
            $pr->requested_by_id,
            ...$notifiables
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
            $query->whereJsonContains('permissions', 'supply:*')
                ->orWhereJsonContains('permissions', 'super:*');
        })->pluck('id')->toArray();

        $userIds = array_unique([
            $pr->requested_by_id,
            ...$notifiables
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
            $query->whereJsonContains('permissions', 'supply:*')
                ->orWhereJsonContains('permissions', 'super:*');
        })->pluck('id')->toArray();

        $userIds = array_unique([
            $pr->requested_by_id,
            ...$notifiables
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
            $query->whereJsonContains('permissions', 'supply:*')
                ->orWhereJsonContains('permissions', 'super:*');
        })->pluck('id')->toArray();

        $userIds = array_unique([
            $pr->requested_by_id,
            ...$notifiables
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
            $query->whereJsonContains('permissions', 'supply:*')
                ->orWhereJsonContains('permissions', 'super:*');
        })->pluck('id')->toArray();

        $userIds = array_unique([
            $pr->requested_by_id,
            ...$notifiables
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
            $query->whereJsonContains('permissions', 'supply:*')
                ->orWhereJsonContains('permissions', 'super:*');
        })->pluck('id')->toArray();

        $userIds = array_unique([
            $pr->requested_by_id,
            ...$notifiables
        ]);

        $users = User::whereIn('id', $userIds)->get();

        $users->each(function ($user) use ($pr, $aoq) {
            $user->notify((
                new AwardedNotification($pr, $aoq)
            )->onQueue('notification'));
        });
    }

    private function notifyAwarded(PurchaseRequest $pr, AbstractQuotation $aoq): void
    {
        $notifiables = User::whereHas('roles', function ($query) {
            $query->whereJsonContains('permissions', 'supply:*')
                ->orWhereJsonContains('permissions', 'super:*');
        })->pluck('id')->toArray();

        $userIds = array_unique([
            $pr->requested_by_id,
            ...$notifiables
        ]);

        $users = User::whereIn('id', $userIds)->get();

        $users->each(function ($user) use ($pr, $aoq) {
            $user->notify((
                new AwardedNotification($pr, $aoq)
            )->onQueue('notification'));
        });
    }
}
