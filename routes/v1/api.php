<?php

use App\Http\Controllers\V1\Account as AccountControllers;
use App\Http\Controllers\V1 as MainControllers;
use App\Http\Controllers\V1\Inventory as InventoryControllers;
use App\Http\Controllers\V1\Library as LibraryControllers;
use App\Http\Controllers\V1\Procurement as ProcurementControllers;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public Routes
    Route::get('/companies', [MainControllers\CompanyController::class, 'show'])
        ->name('companies.show');

    Route::name('auth.')->group(function () {
        Route::post('/login', [AccountControllers\AuthController::class, 'login'])
            ->name('login');
    });

    Route::name('media.')->prefix('/media')->group(function () {
        Route::get('/', [MainControllers\MediaController::class, 'show'])
            ->name('show');
    });

    // Authenticated Routes
    Route::middleware('auth:sanctum')->group(function () {
        // Auth
        Route::name('auth.')->group(function () {
            Route::post('/logout', [AccountControllers\AuthController::class, 'logout'])
                ->name('logout');
            Route::get('/me', [AccountControllers\AuthController::class, 'me'])
                ->name('me');
            Route::post('/refresh-token', [AccountControllers\AuthController::class, 'refreshToken'])
                ->name('refresh-token');
        });

        // Dashboard
        Route::name('dashboard.')->prefix('/dashboard')->group(function () {
            Route::get('/', [MainControllers\DashboardController::class, 'index'])
                ->name('index');
        });

        // Company
        Route::name('companies.')->prefix('/companies')->group(function () {
            Route::put('/', [MainControllers\CompanyController::class, 'update'])
                ->middleware('ability:super:*,company:*,company:update')
                ->name('update');
        });

        // Media
        Route::name('media.')->prefix('/media')->group(function () {
            Route::post('/', [MainControllers\MediaController::class, 'store'])
                ->name('store');
        });

        // Logs
        Route::name('logs.')->prefix('/logs')->group(function () {
            Route::get('/', [MainControllers\LogController::class, 'index'])
                ->middleware('ability:super:*,system-log:*,system-log:update')
                ->name('show');
        });

        // Notifications
        Route::name('notifications.')->prefix('/notifications')->group(function () {
            Route::get('/', [MainControllers\NotificationController::class, 'index'])
                ->name('show');
            Route::put('/{id}/read', [MainControllers\NotificationController::class, 'markAsRead'])
                ->name('read');
            Route::put('/read/all', [MainControllers\NotificationController::class, 'markAllRead'])
                ->name('read_all');
            Route::put('/delete/all', [MainControllers\NotificationController::class, 'deleteAll'])
                ->name('delete_all');
        });

        // Print
        Route::name('documents_prints.')->prefix('/documents')->group(function () {
            Route::post('/{document}/prints/{documentId}', [MainControllers\PrintController::class, 'index'])
                ->name('index');
        });

        // Positions
        Route::name('positions.')->prefix('/accounts/positions')->group(function () {
            Route::get('/', [AccountControllers\PositionController::class, 'index'])
                ->name('index');
        });

        // Designations
        Route::name('designations.')->prefix('/accounts/designations')->group(function () {
            Route::get('/', [AccountControllers\DesignationController::class, 'index'])
                ->name('index');
        });

        // Departments
        Route::name('departments.')->prefix('/accounts/departments')->group(function () {
            Route::get('/', [AccountControllers\DepartmentController::class, 'index'])
                ->middleware('ability:super:*,head:*,account-department:*,account-department:view')
                ->name('index');
            Route::post('/', [AccountControllers\DepartmentController::class, 'store'])
                ->middleware('ability:super:*,account-department:*,account-department:create')
                ->name('store');
            Route::get('/{department}', [AccountControllers\DepartmentController::class, 'show'])
                ->middleware('ability:super:*,account-department:*,account-department:view')
                ->name('show');
            Route::put('/{department}', [AccountControllers\DepartmentController::class, 'update'])
                ->middleware('ability:super:*,account-department:*,account-department:update')
                ->name('update');
        });

        // Sections
        Route::name('sections.')->prefix('/accounts/sections')->group(function () {
            Route::get('/', [AccountControllers\SectionController::class, 'index'])
                ->middleware('ability:super:*,head:*,account-section:*,account-section:view')
                ->name('index');
            Route::post('/', [AccountControllers\SectionController::class, 'store'])
                ->middleware('ability:super:*,account-section:*,account-section:create')
                ->name('store');
            Route::get('/{section}', [AccountControllers\SectionController::class, 'show'])
                ->middleware('ability:super:*,head:*,account-section:*,account-section:view')
                ->name('show');
            Route::put('/{section}', [AccountControllers\SectionController::class, 'update'])
                ->middleware('ability:super:*,head:*,account-section:*,account-section:update')
                ->name('update');
        });

        // Roles
        Route::name('roles.')->prefix('/accounts/roles')->group(function () {
            Route::get('/', [AccountControllers\RoleController::class, 'index'])
                ->middleware('ability:super:*,head:*,account-role:*,account-role:view')
                ->name('index');
            Route::post('/', [AccountControllers\RoleController::class, 'store'])
                ->middleware('ability:super:*,account-role:*,account-role:create')
                ->name('store');
            Route::get('/{role}', [AccountControllers\RoleController::class, 'show'])
                ->middleware('ability:super:*,head:*,account-role:*,account-role:view')
                ->name('show');
            Route::put('/{role}', [AccountControllers\RoleController::class, 'update'])
                ->middleware('ability:super:*,head:*,account-role:*,account-role:update')
                ->name('update');
        });

        // Users
        Route::name('users.')->prefix('/accounts/users')->group(function () {
            Route::get('/', [AccountControllers\UserController::class, 'index'])
                ->middleware('ability:super:*,head:*,account-user:*,account-user:view')
                ->name('index');
            Route::post('/', [AccountControllers\UserController::class, 'store'])
                ->middleware('ability:super:*,account-user:*,account-user:create')
                ->name('store');
            Route::get('/{user}', [AccountControllers\UserController::class, 'show'])
                ->middleware('ability:super:*,head:*,account-user:*,account-user:view')
                ->name('show');
            Route::put('/{user}', [AccountControllers\UserController::class, 'update'])
                ->name('update');
        });

        // Purchase Requests
        Route::name('purchase_requests.')->prefix('/purchase-requests')->group(function () {
            Route::get('/', [ProcurementControllers\PurchaseRequestController::class, 'index'])
                ->middleware('ability:super:*,head:*,supply:*,pr:*,pr:view')
                ->name('index');
            Route::post('/', [ProcurementControllers\PurchaseRequestController::class, 'store'])
                ->middleware('ability:super:*,supply:*,pr:*,pr:create')
                ->name('store');
            Route::get('/{purchaseRequest}', [ProcurementControllers\PurchaseRequestController::class, 'show'])
                ->middleware('ability:super:*,head:*,supply:*,pr:*,pr:view')
                ->name('show');
            Route::put('/{purchaseRequest}', [ProcurementControllers\PurchaseRequestController::class, 'update'])
                ->middleware('ability:super:*,supply:*,pr:*,pr:update')
                ->name('update');
            Route::put('/{purchaseRequest}/submit-approval', [ProcurementControllers\PurchaseRequestController::class, 'submitForApproval'])
                ->middleware('ability:super:*,supply:*,pr:*,pr:submit')
                ->name('submit');
            Route::put('/{purchaseRequest}/approve-cash-availability', [ProcurementControllers\PurchaseRequestController::class, 'approveForCashAvailability'])
                ->middleware('ability:super:*,supply:*,cashier:*,budget:*,accountant:*,pr:*,pr:approve-cash-available')
                ->name('approve_for_cash_availability');
            Route::put('/{purchaseRequest}/approve', [ProcurementControllers\PurchaseRequestController::class, 'approve'])
                ->middleware('ability:super:*,head:*,supply:*,pr:*,pr:approve')
                ->name('approve');
            Route::put('/{purchaseRequest}/disapprove', [ProcurementControllers\PurchaseRequestController::class, 'disapprove'])
                ->middleware('ability:super:*,head:*,supply:*,pr:*,pr:disapprove')
                ->name('disapprove');
            Route::put('/{purchaseRequest}/cancel', [ProcurementControllers\PurchaseRequestController::class, 'cancel'])
                ->middleware('ability:super:*,supply:*,pr:*,pr:cancel')
                ->name('cancel');
            Route::put('/{purchaseRequest}/issue-all-request-quotations', [ProcurementControllers\PurchaseRequestController::class, 'issueAllDraftRfq'])
                ->middleware('ability:super:*,head:*,supply:*,pr:*,pr:issue-rfq')
                ->name('issue_all_request_quotations');
            Route::put('/{purchaseRequest}/approve-request-quotations', [ProcurementControllers\PurchaseRequestController::class, 'approveRequestQuotations'])
                ->middleware('ability:super:*,head:*,supply:*,pr:*,pr:approve-rfq')
                ->name('approve_request_quotations');
            Route::put('/{purchaseRequest}/award-abstract-quotations', [ProcurementControllers\PurchaseRequestController::class, 'awardAbstractQuotations'])
                ->middleware('ability:super:*,head:*,supply:*,pr:*,pr:award-aoq')
                ->name('award_abstract_quotations');
        });

        // Request Quotations
        Route::name('request_quotations.')->prefix('/request-quotations')->group(function () {
            Route::get('/', [ProcurementControllers\RequestQuotationController::class, 'index'])
                ->middleware('ability:super:*,head:*,supply:*,rfq:*,rfq:view')
                ->name('index');
            Route::post('/', [ProcurementControllers\RequestQuotationController::class, 'store'])
                ->middleware('ability:super:*,supply:*,rfq:*,rfq:create')
                ->name('store');
            Route::get('/{requestQuotation}', [ProcurementControllers\RequestQuotationController::class, 'show'])
                ->middleware('ability:super:*,head:*,supply:*,rfq:*,rfq:view')
                ->name('show');
            Route::put('/{requestQuotation}', [ProcurementControllers\RequestQuotationController::class, 'update'])
                ->middleware('ability:super:*,supply:*,rfq:*,rfq:update')
                ->name('update');
            Route::put('/{requestQuotation}/issue-canvassing', [ProcurementControllers\RequestQuotationController::class, 'issueCanvassing'])
                ->middleware('ability:super:*,supply:*,rfq:*,rfq:issue')
                ->name('issue');
            Route::put('/{requestQuotation}/canvass-complete', [ProcurementControllers\RequestQuotationController::class, 'canvassComplete'])
                ->middleware('ability:super:*,supply:*,rfq:*,rfq:complete')
                ->name('complete');
            Route::put('/{requestQuotation}/cancel', [ProcurementControllers\RequestQuotationController::class, 'cancel'])
                ->middleware('ability:super:*,supply:*,rfq:*,rfq:cancel')
                ->name('cancel');
        });

        // Abstract Quotations
        Route::name('abstract_quotations.')->prefix('/abstract-quotations')->group(function () {
            Route::get('/', [ProcurementControllers\AbstractQuotationController::class, 'index'])
                ->middleware('ability:super:*,head:*,supply:*,aoq:*,aoq:view')
                ->name('index');
            Route::post('/', [ProcurementControllers\AbstractQuotationController::class, 'store'])
                ->middleware('ability:super:*,supply:*,aoq:*,aoq:create')
                ->name('store');
            Route::get('/{abstractQuotation}', [ProcurementControllers\AbstractQuotationController::class, 'show'])
                ->middleware('ability:super:*,head:*,supply:*,aoq:*,aoq:view')
                ->name('show');
            Route::put('/{abstractQuotation}', [ProcurementControllers\AbstractQuotationController::class, 'update'])
                ->middleware('ability:super:*,supply:*,aoq:*,aoq:update')
                ->name('update');
            Route::put('/{abstractQuotation}/revert', [ProcurementControllers\AbstractQuotationController::class, 'revert'])
                ->middleware('ability:super:*,supply:*,aoq:*,aoq:revert')
                ->name('revert');
            Route::put('/{abstractQuotation}/pending', [ProcurementControllers\AbstractQuotationController::class, 'pending'])
                ->middleware('ability:super:*,supply:*,aoq:*,aoq:pending')
                ->name('pending');
            Route::put('/{abstractQuotation}/approve', [ProcurementControllers\AbstractQuotationController::class, 'approve'])
                ->middleware('ability:super:*,head:*,supply:*,aoq:*,aoq:approve')
                ->name('approve');
        });

        // Purchase Orders
        Route::name('purchase_orders.')->prefix('/purchase-orders')->group(function () {
            Route::get('/', [ProcurementControllers\PurchaseOrderController::class, 'index'])
                ->middleware('ability:super:*,head:*,supply:*,po:*,po:view')
                ->name('index');
            Route::get('/{purchaseOrder}', [ProcurementControllers\PurchaseOrderController::class, 'show'])
                ->middleware('ability:super:*,head:*,supply:*,po:*,po:view')
                ->name('show');
            Route::put('/{purchaseOrder}', [ProcurementControllers\PurchaseOrderController::class, 'update'])
                ->middleware('ability:super:*,supply:*,po:*,po:update')
                ->name('update');
            Route::put('/{purchaseOrder}/pending', [ProcurementControllers\PurchaseOrderController::class, 'pending'])
                ->middleware('ability:super:*,supply:*,po:*,po:pending')
                ->name('pending');
            Route::put('/{purchaseOrder}/approve', [ProcurementControllers\PurchaseOrderController::class, 'approve'])
                ->middleware('ability:super:*,head:*,supply:*,po:*,po:approve')
                ->name('approve');
            Route::put('/{purchaseOrder}/issue', [ProcurementControllers\PurchaseOrderController::class, 'issue'])
                ->middleware('ability:super:*,supply:*,po:*,po:issue')
                ->name('issue');
            Route::put('/{purchaseOrder}/receive', [ProcurementControllers\PurchaseOrderController::class, 'receive'])
                ->middleware('ability:super:*,supply:*,po:*,po:receive')
                ->name('receive');
            Route::put('/{purchaseOrder}/delivered', [ProcurementControllers\PurchaseOrderController::class, 'delivered'])
                ->middleware('ability:super:*,supply:*,po:*,po:delivered')
                ->name('delivered');
        });

        // Inspection Acceptance Reports
        Route::name('inspection_acceptance_reports.')->prefix('/inspection-acceptance-reports')->group(function () {
            Route::get('/', [ProcurementControllers\InspectionAcceptanceReportController::class, 'index'])
                ->middleware('ability:super:*,head:*,supply:*,iar:*,iar:view')
                ->name('index');
            Route::get('/{inspectionAcceptanceReport}', [ProcurementControllers\InspectionAcceptanceReportController::class, 'show'])
                ->middleware('ability:super:*,head:*,supply:*,iar:*,iar:view')
                ->name('show');
            Route::put('/{inspectionAcceptanceReport}', [ProcurementControllers\InspectionAcceptanceReportController::class, 'update'])
                ->middleware('ability:super:*,supply:*,iar:*,iar:update')
                ->name('update');
            Route::put('/{inspectionAcceptanceReport}/pending', [ProcurementControllers\InspectionAcceptanceReportController::class, 'pending'])
                ->middleware('ability:super:*,supply:*,iar:*,iar:pending')
                ->name('pending');
            Route::put('/{inspectionAcceptanceReport}/inspect', [ProcurementControllers\InspectionAcceptanceReportController::class, 'inspect'])
                ->middleware('ability:super:*,supply:*,iar:*,iar:inspect')
                ->name('inspect');
        });

        // Obligation Requests
        Route::name('obligation_requests.')->prefix('/obligation-requests')->group(function () {
            Route::get('/', [ProcurementControllers\ObligationRequestController::class, 'index'])
                ->middleware('ability:super:*,head:*,budget:*,obr:*,obr:view')
                ->name('index');
            Route::get('/{obligationRequest}', [ProcurementControllers\ObligationRequestController::class, 'show'])
                ->middleware('ability:super:*,head:*,budget:*,obr:*,obr:view')
                ->name('show');
            Route::post('/', [ProcurementControllers\ObligationRequestController::class, 'store'])
                ->middleware('ability:super:*,budget:*,obr:*,obr:create')
                ->name('store');
            Route::put('/{obligationRequest}', [ProcurementControllers\ObligationRequestController::class, 'update'])
                ->middleware('ability:super:*,budget:*,obr:*,obr:update')
                ->name('update');
            Route::put('/{obligationRequest}/pending', [ProcurementControllers\ObligationRequestController::class, 'pending'])
                ->middleware('ability:super:*,budget:*,obr:*,obr:pending')
                ->name('pending');
            Route::put('/{obligationRequest}/disapprove', [ProcurementControllers\ObligationRequestController::class, 'disapprove'])
                ->middleware('ability:super:*,budget:*,obr:*,obr:disapprove')
                ->name('disapprove');
            Route::put('/{obligationRequest}/obligate', [ProcurementControllers\ObligationRequestController::class, 'obligate'])
                ->middleware('ability:super:*,budget:*,obr:*,obr:obligate')
                ->name('obligate');
        });

        // Disbursement Vouchers
        Route::name('disbursement_vouchers.')->prefix('/disbursement-vouchers')->group(function () {
            Route::get('/', [ProcurementControllers\DisbursementVoucherController::class, 'index'])
                ->middleware('ability:super:*,head:*,accountant:*,dv:*,dv:view')
                ->name('index');
            Route::get('/{disbursementVoucher}', [ProcurementControllers\DisbursementVoucherController::class, 'show'])
                ->middleware('ability:super:*,head:*,accountant:*,dv:*,dv:view')
                ->name('show');
            Route::put('/{disbursementVoucher}', [ProcurementControllers\DisbursementVoucherController::class, 'update'])
                ->middleware('ability:super:*,accountant:*,dv:*,dv:update')
                ->name('update');
            Route::put('/{disbursementVoucher}/pending', [ProcurementControllers\DisbursementVoucherController::class, 'pending'])
                ->middleware('ability:super:*,accountant:*,dv:*,dv:pending')
                ->name('pending');
            Route::put('/{disbursementVoucher}/disapprove', [ProcurementControllers\DisbursementVoucherController::class, 'disapprove'])
                ->middleware('ability:super:*,accountant:*,dv:*,dv:disapprove')
                ->name('disapprove');
            Route::put('/{disbursementVoucher}/disburse', [ProcurementControllers\DisbursementVoucherController::class, 'disburse'])
                ->middleware('ability:super:*,accountant:*,dv:*,dv:disburse')
                ->name('disburse');
            Route::put('/{disbursementVoucher}/paid', [ProcurementControllers\DisbursementVoucherController::class, 'paid'])
                ->middleware('ability:super:*,accountant:*,dv:*,dv:paid')
                ->name('paid');
        });

        // Inventory Supplies
        Route::name('inventories.')->prefix('/inventories')->group(function () {
            Route::name('supplies.')->prefix('/supplies')->group(function () {
                Route::get('/', [InventoryControllers\InventorySupplyController::class, 'index'])
                    ->middleware('ability:super:*,head:*,supply:*,inv-supply:*,inv-supply:view')
                    ->name('index');
                Route::post('/', [InventoryControllers\InventorySupplyController::class, 'store'])
                    ->middleware('ability:super:*,supply:*,inv-supply:*,inv-supply:create')
                    ->name('store');
                Route::get('/{inventorySupply}', [InventoryControllers\InventorySupplyController::class, 'show'])
                    ->middleware('ability:super:*,head:*,supply:*,inv-supply:*,inv-supply:view')
                    ->name('show');
                Route::put('/{inventorySupply}', [InventoryControllers\InventorySupplyController::class, 'update'])
                    ->middleware('ability:super:*,supply:*,inv-supply:*,inv-supply:update')
                    ->name('update');
            });

            Route::name('issuances.')->prefix('/issuances')->group(function () {
                Route::get('/', [InventoryControllers\InventoryIssuanceController::class, 'index'])
                    ->middleware('ability:super:*,head:*,supply:*,inv-issuance:*,inv-issuance:view')
                    ->name('index');
                Route::post('/', [InventoryControllers\InventoryIssuanceController::class, 'store'])
                    ->middleware('ability:super:*,supply:*,inv-issuance:*,inv-issuance:create')
                    ->name('store');
                Route::get('/{inventoryIssuance}', [InventoryControllers\InventoryIssuanceController::class, 'show'])
                    ->middleware('ability:super:*,head:*,supply:*,inv-issuance:*,inv-issuance:view')
                    ->name('show');
                Route::put('/{inventoryIssuance}', [InventoryControllers\InventoryIssuanceController::class, 'update'])
                    ->middleware('ability:super:*,supply:*,inv-issuance:*,inv-issuance:update')
                    ->name('update');
                Route::put('/{inventoryIssuance}/pending', [InventoryControllers\InventoryIssuanceController::class, 'pending'])
                    ->middleware('ability:super:*,supply:*,inv-issuance:*,inv-issuance:pending')
                    ->name('pending');
                Route::put('/{inventoryIssuance}/issue', [InventoryControllers\InventoryIssuanceController::class, 'issue'])
                    ->middleware('ability:super:*,supply:*,inv-issuance:*,inv-issuance:issue')
                    ->name('issue');
                Route::put('/{inventoryIssuance}/cancel', [InventoryControllers\InventoryIssuanceController::class, 'cancel'])
                    ->middleware('ability:super:*,supply:*,inv-issuance:*,inv-issuance:cancel')
                    ->name('cancel');
            });
        });

        // Library - Locations
        Route::name('locations.')->prefix('/libraries/locations')->group(function () {
            Route::get('/', [LibraryControllers\LocationController::class, 'index'])
                ->name('index');
        });

        // Library - Delivery Terms
        Route::name('delivery-terms.')->prefix('/libraries/delivery-terms')->group(function () {
            Route::get('/', [LibraryControllers\DeliveryTermController::class, 'index'])
                ->name('index');
        });

        // Library - Payment Terms
        Route::name('payment-terms.')->prefix('/libraries/payment-terms')->group(function () {
            Route::get('/', [LibraryControllers\PaymentTermController::class, 'index'])
                ->name('index');
        });

        // Library - Account Classifications
        Route::name('account-classifications.')->prefix('/libraries/account-classifications')->group(function () {
            Route::get('/', [LibraryControllers\AccountClassificationController::class, 'index'])
                ->middleware('ability:super:*,head:*,lib-account-class:*,lib-account-class:view')
                ->name('index');
            Route::post('/', [LibraryControllers\AccountClassificationController::class, 'store'])
                ->middleware('ability:super:*,lib-account-class:*,lib-account-class:create')
                ->name('store');
            Route::get('/{accountClassification}', [LibraryControllers\AccountClassificationController::class, 'show'])
                ->middleware('ability:super:*,head:*,lib-account-class:*,lib-account-class:view')
                ->name('show');
            Route::put('/{accountClassification}', [LibraryControllers\AccountClassificationController::class, 'update'])
                ->middleware('ability:super:*,lib-account-class:*,lib-account-class:update')
                ->name('update');
        });

        // Library - Accounts
        Route::name('accounts.')->prefix('/libraries/accounts')->group(function () {
            Route::get('/', [LibraryControllers\AccountController::class, 'index'])
                ->middleware('ability:super:*,head:*,lib-account:*,lib-account:view')
                ->name('index');
            Route::post('/', [LibraryControllers\AccountController::class, 'store'])
                ->middleware('ability:super:*,lib-account:*,lib-account:create')
                ->name('store');
            Route::get('/{account}', [LibraryControllers\AccountController::class, 'show'])
                ->middleware('ability:super:*,head:*,lib-account:*,lib-account:view')
                ->name('show');
            Route::put('/{account}', [LibraryControllers\AccountController::class, 'update'])
                ->middleware('ability:super:*,lib-account:*,lib-account:update')
                ->name('update');
        });

        // Library - Bids Awards Committees
        Route::name('bids-awards-committees.')->prefix('/libraries/bids-awards-committees')->group(function () {
            Route::get('/', [LibraryControllers\BidsAwardsCommitteeController::class, 'index'])
                ->middleware('ability:super:*,head:*,lib-bid-committee:*,lib-bid-committee:view')
                ->name('index');
            Route::post('/', [LibraryControllers\BidsAwardsCommitteeController::class, 'store'])
                ->middleware('ability:super:*,lib-bid-committee:*,lib-bid-committee:create')
                ->name('store');
            Route::get('/{bidsAwardsCommittee}', [LibraryControllers\BidsAwardsCommitteeController::class, 'show'])
                ->middleware('ability:super:*,head:*,lib-bid-committee:*,lib-bid-committee:view')
                ->name('show');
            Route::put('/{bidsAwardsCommittee}', [LibraryControllers\BidsAwardsCommitteeController::class, 'update'])
                ->middleware('ability:super:*,lib-bid-committee:*,lib-bid-committee:update')
                ->name('update');
        });

        // Library - Funding Sources
        Route::name('funding-sources.')->prefix('/libraries/funding-sources')->group(function () {
            Route::get('/', [LibraryControllers\FundingSourceController::class, 'index'])
                ->middleware('ability:super:*,head:*,lib-fund-source:*,lib-fund-source:view')
                ->name('index');
            Route::post('/', [LibraryControllers\FundingSourceController::class, 'store'])
                ->middleware('ability:super:*,lib-fund-source:*,lib-fund-source:create')
                ->name('store');
            Route::get('/{fundingSource}', [LibraryControllers\FundingSourceController::class, 'show'])
                ->middleware('ability:super:*,head:*,lib-fund-source:*,lib-fund-source:view')
                ->name('show');
            Route::put('/{fundingSource}', [LibraryControllers\FundingSourceController::class, 'update'])
                ->middleware('ability:super:*,lib-fund-source:*,lib-fund-source:update')
                ->name('update');
        });

        // Library - Item Classifications
        Route::name('item-classifications.')->prefix('/libraries/item-classifications')->group(function () {
            Route::get('/', [LibraryControllers\ItemClassificationController::class, 'index'])
                ->middleware('ability:super:*,head:*,lib-item-class:*,lib-item-class:view')
                ->name('index');
            Route::post('/', [LibraryControllers\ItemClassificationController::class, 'store'])
                ->middleware('ability:super:*,lib-item-class:*,lib-item-class:create')
                ->name('store');
            Route::get('/{itemClassification}', [LibraryControllers\ItemClassificationController::class, 'show'])
                ->middleware('ability:super:*,head:*,lib-item-class:*,lib-item-class:view')
                ->name('show');
            Route::put('/{itemClassification}', [LibraryControllers\ItemClassificationController::class, 'update'])
                ->middleware('ability:super:*,lib-item-class:*,lib-item-class:update')
                ->name('update');
        });

        // Library - Function Program Projects
        Route::name('function-program-projects.')->prefix('/libraries/function-program-projects')->group(function () {
            Route::get('/', [LibraryControllers\FunctionProgramProjectController::class, 'index'])
                ->middleware('ability:super:*,head:*,lib-fpp:*,lib-fpp:view')
                ->name('index');
            Route::post('/', [LibraryControllers\FunctionProgramProjectController::class, 'store'])
                ->middleware('ability:super:*,lib-fpp:*,lib-fpp:create')
                ->name('store');
            Route::get('/{functionProgramProject}', [LibraryControllers\FunctionProgramProjectController::class, 'show'])
                ->middleware('ability:super:*,head:*,lib-fpp:*,lib-fpp:view')
                ->name('show');
            Route::put('/{functionProgramProject}', [LibraryControllers\FunctionProgramProjectController::class, 'update'])
                ->middleware('ability:super:*,lib-fpp:*,lib-fpp:update')
                ->name('update');
        });

        // Library - Procurement Modes
        Route::name('procurement-modes.')->prefix('/libraries/procurement-modes')->group(function () {
            Route::get('/', [LibraryControllers\ProcurementModeController::class, 'index'])
                ->middleware('ability:super:*,head:*,lib-mode-proc:*,lib-mode-proc:view')
                ->name('index');
            Route::post('/', [LibraryControllers\ProcurementModeController::class, 'store'])
                ->middleware('ability:super:*,lib-mode-proc:*,lib-mode-proc:create')
                ->name('store');
            Route::get('/{procurementMode}', [LibraryControllers\ProcurementModeController::class, 'show'])
                ->middleware('ability:super:*,head:*,lib-mode-proc:*,lib-mode-proc:view')
                ->name('show');
            Route::put('/{procurementMode}', [LibraryControllers\ProcurementModeController::class, 'update'])
                ->middleware('ability:super:*,lib-mode-proc:*,lib-mode-proc:update')
                ->name('update');
        });

        // Library - Paper Sizes
        Route::name('paper-sizes.')->prefix('/libraries/paper-sizes')->group(function () {
            Route::get('/', [LibraryControllers\PaperSizeController::class, 'index'])
                ->middleware('ability:super:*,head:*,lib-paper-size:*,lib-paper-size:view')
                ->name('index');
            Route::post('/', [LibraryControllers\PaperSizeController::class, 'store'])
                ->middleware('ability:super:*,lib-paper-size:*,lib-paper-size:create')
                ->name('store');
            Route::get('/{paperSize}', [LibraryControllers\PaperSizeController::class, 'show'])
                ->middleware('ability:super:*,head:*,lib-paper-size:*,lib-paper-size:view')
                ->name('show');
            Route::put('/{paperSize}', [LibraryControllers\PaperSizeController::class, 'update'])
                ->middleware('ability:super:*,lib-paper-size:*,lib-paper-size:update')
                ->name('update');
            Route::delete('/{paperSize}', [LibraryControllers\PaperSizeController::class, 'delete'])
                ->middleware('ability:super:*,lib-paper-size:*,lib-paper-size:delete')
                ->name('delete');
        });

        // Library - Responsibility Centers
        Route::name('responsibility-centers.')->prefix('/libraries/responsibility-centers')->group(function () {
            Route::get('/', [LibraryControllers\ResponsibilityCenterController::class, 'index'])
                ->middleware('ability:super:*,head:*,lib-responsibility-center:*,lib-responsibility-center:view')
                ->name('index');
            Route::post('/', [LibraryControllers\ResponsibilityCenterController::class, 'store'])
                ->middleware('ability:super:*,lib-responsibility-center:*,lib-responsibility-center:create')
                ->name('store');
            Route::get('/{responsibilityCenter}', [LibraryControllers\ResponsibilityCenterController::class, 'show'])
                ->middleware('ability:super:*,head:*,lib-responsibility-center:*,lib-responsibility-center:view')
                ->name('show');
            Route::put('/{responsibilityCenter}', [LibraryControllers\ResponsibilityCenterController::class, 'update'])
                ->middleware('ability:super:*,lib-responsibility-center:*,lib-responsibility-center:update')
                ->name('update');
        });

        // Library - Signatories
        Route::name('signatories.')->prefix('/libraries/signatories')->group(function () {
            Route::get('/', [LibraryControllers\SignatoryController::class, 'index'])
                ->middleware('ability:super:*,head:*,lib-signatory:*,lib-signatory:view')
                ->name('index');
            Route::post('/', [LibraryControllers\SignatoryController::class, 'store'])
                ->middleware('ability:super:*,lib-signatory:*,lib-signatory:create')
                ->name('store');
            Route::get('/{signatory}', [LibraryControllers\SignatoryController::class, 'show'])
                ->middleware('ability:super:*,head:*,lib-signatory:*,lib-signatory:view')
                ->name('show');
            Route::put('/{signatory}', [LibraryControllers\SignatoryController::class, 'update'])
                ->middleware('ability:super:*,lib-signatory:*,lib-signatory:update')
                ->name('update');
        });

        // Library - Payees (combined suppliers and users)
        Route::get('/libraries/payees', [LibraryControllers\PayeeController::class, 'index'])
            ->middleware('ability:super:*,head:*,budget:*,obr:*,obr:view')
            ->name('payees.index');

        // Library - Suppliers
        Route::name('suppliers.')->prefix('/libraries/suppliers')->group(function () {
            Route::get('/', [LibraryControllers\SupplierController::class, 'index'])
                ->middleware('ability:super:*,head:*,lib-supplier:*,lib-supplier:view')
                ->name('index');
            Route::post('/', [LibraryControllers\SupplierController::class, 'store'])
                ->middleware('ability:super:*,lib-supplier:*,lib-supplier:create')
                ->name('store');
            Route::get('/{supplier}', [LibraryControllers\SupplierController::class, 'show'])
                ->middleware('ability:super:*,head:*,lib-supplier:*,lib-supplier:view')
                ->name('show');
            Route::put('/{supplier}', [LibraryControllers\SupplierController::class, 'update'])
                ->middleware('ability:super:*,lib-supplier:*,lib-supplier:update')
                ->name('update');
        });

        // Library - Unit Issues
        Route::name('unit-issues.')->prefix('/libraries/unit-issues')->group(function () {
            Route::get('/', [LibraryControllers\UnitIssueController::class, 'index'])
                ->middleware('ability:super:*,head:*,lib-unit-issue:*,lib-unit-issue:view')
                ->name('index');
            Route::post('/', [LibraryControllers\UnitIssueController::class, 'store'])
                ->middleware('ability:super:*,lib-unit-issue:*,lib-unit-issue:create')
                ->name('store');
            Route::get('/{unitIssue}', [LibraryControllers\UnitIssueController::class, 'show'])
                ->middleware('ability:super:*,head:*,lib-unit-issue:*,lib-unit-issue:view')
                ->name('show');
            Route::put('/{unitIssue}', [LibraryControllers\UnitIssueController::class, 'update'])
                ->middleware('ability:super:*,lib-unit-issue:*,lib-unit-issue:update')
                ->name('update');
        });

        // Library - Tax Withholdings
        Route::name('tax-withholdings.')->prefix('/libraries/tax-withholdings')->group(function () {
            Route::get('/', [LibraryControllers\TaxWithholdingController::class, 'index'])
                ->middleware('ability:super:*,head:*,accountant:*,lib-tax-withholding:*,lib-tax-withholding:view,dv:view')
                ->name('index');
            Route::post('/', [LibraryControllers\TaxWithholdingController::class, 'store'])
                ->middleware('ability:super:*,lib-tax-withholding:*,lib-tax-withholding:create')
                ->name('store');
            Route::get('/{taxWithholding}', [LibraryControllers\TaxWithholdingController::class, 'show'])
                ->middleware('ability:super:*,head:*,accountant:*,lib-tax-withholding:*,lib-tax-withholding:view,dv:view')
                ->name('show');
            Route::put('/{taxWithholding}', [LibraryControllers\TaxWithholdingController::class, 'update'])
                ->middleware('ability:super:*,lib-tax-withholding:*,lib-tax-withholding:update')
                ->name('update');
        });
    });
});
