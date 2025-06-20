<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\Procurement as ProcurementControllers;

Route::middleware('auth:sanctum')->group(function() {
    Route::name('purchase_requests.')->prefix('/purchase-requests')->group(function () {
        Route::get('/', [ProcurementControllers\PurchaseRequestController::class, 'index'])
            ->middleware('ability:super:*,head:*,supply:*,pr:*,pr:view')
            ->name('index');
        Route::post('/', [ProcurementControllers\PurchaseRequestController::class, 'store'])
            ->middleware('ability:super:*,supply:*,pr:*,pr:create')
            ->name('store');
        Route::get('/{purchaseRequest}', [ProcurementControllers\PurchaseRequestController::class, 'show'])
            ->middleware('ability:super:*,supply:*,pr:*,pr:view')
            ->name('show');
        Route::put('/{purchaseRequest}', [ProcurementControllers\PurchaseRequestController::class, 'update'])
            ->middleware('ability:super:*,supply:*,pr:*,pr:update')
            ->name('update');
        Route::put('/{purchaseRequest}/submit-approval', [ProcurementControllers\PurchaseRequestController::class, 'submitForApproval'])
            ->name('submit');
        Route::put('/{purchaseRequest}/approve-cash-availability', [ProcurementControllers\PurchaseRequestController::class, 'approveForCashAvailability'])
            ->middleware('ability:super:*,supply:*,cashier:*,budget:*,accounting:*,pr:*,pr:approve-cash-available')
            ->name('approve_for_cash_availability');
        Route::put('/{purchaseRequest}/approve', [ProcurementControllers\PurchaseRequestController::class, 'approve'])
            ->middleware('ability:super:*,head:*,supply:*,pr:*,pr:approve')
            ->name('approve');
        Route::put('/{purchaseRequest}/disapprove', [ProcurementControllers\PurchaseRequestController::class, 'disapprove'])
            ->middleware('ability:super:*,head:*,supply:*,pr:*,pr:disapprove')
            ->name('update');
        Route::put('/{purchaseRequest}/cancel', [ProcurementControllers\PurchaseRequestController::class, 'cancel'])
            ->name('cancel');
        Route::put('/{purchaseRequest}/issue-all-request-quotations', [ProcurementControllers\PurchaseRequestController::class, 'issueAllDraftRfq'])
            ->middleware('ability:super:*,head:*,supply:*,pr:*,pr:issue-rfq')
            ->name('approve_request_quotations');
        Route::put('/{purchaseRequest}/approve-request-quotations', [ProcurementControllers\PurchaseRequestController::class, 'approveRequestQuotations'])
            ->middleware('ability:super:*,head:*,supply:*,pr:*,pr:approve-rfq')
            ->name('approve_request_quotations');
        Route::put('/{purchaseRequest}/award-abstract-quotations', [ProcurementControllers\PurchaseRequestController::class, 'awardAbstractQuotations'])
            ->middleware('ability:super:*,head:*,supply:*,pr:*,pr:award-aoq')
            ->name('approve_request_quotations');
    });

    Route::name('request_quotations.')->prefix('/request-quotations')->group(function () {
        Route::get('/', [ProcurementControllers\RequestQuotationController::class, 'index'])
            ->middleware('ability:super:*,head:*,supply:*,rfq:*,rfq:view')
            ->name('index');
        Route::post('/', [ProcurementControllers\RequestQuotationController::class, 'store'])
            ->middleware('ability:super:*,supply:*,rfq:*,rfq:create')
            ->name('store');
        Route::get('/{requestQuotation}', [ProcurementControllers\RequestQuotationController::class, 'show'])
            ->middleware('ability:super:*,supply:*,rfq:*,rfq:view')
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

    Route::name('abstract_quotations.')->prefix('/abstract-quotations')->group(function () {
        Route::get('/', [ProcurementControllers\AbstractQuotationController::class, 'index'])
            ->middleware('ability:super:*,head:*,supply:*,aoq:*,aoq:view')
            ->name('index');
        Route::post('/', [ProcurementControllers\AbstractQuotationController::class, 'store'])
            ->middleware('ability:super:*,supply:*,aoq:*,aoq:create')
            ->name('store');
        Route::get('/{abstractQuotation}', [ProcurementControllers\AbstractQuotationController::class, 'show'])
            ->middleware('ability:super:*,supply:*,aoq:*,aoq:view')
            ->name('show');
        Route::put('/{abstractQuotation}', [ProcurementControllers\AbstractQuotationController::class, 'update'])
            ->middleware('ability:super:*,supply:*,aoq:*,aoq:update')
            ->name('update');
        Route::put('/{abstractQuotation}/pending', [ProcurementControllers\AbstractQuotationController::class, 'pending'])
            ->middleware('ability:super:*,supply:*,aoq:*,aoq:pending')
            ->name('pending');
        Route::put('/{abstractQuotation}/approve', [ProcurementControllers\AbstractQuotationController::class, 'approve'])
            ->middleware('ability:super:*,supply:*,aoq:*,aoq:approve')
            ->name('approve');
    });

    Route::name('purchase_orders.')->prefix('/purchase-orders')->group(function () {
        Route::get('/', [ProcurementControllers\PurchaseOrderController::class, 'index'])
            ->middleware('ability:super:*,head:*,supply:*,po:*,po:view')
            ->name('index');
        Route::get('/{purchaseOrder}', [ProcurementControllers\PurchaseOrderController::class, 'show'])
            ->middleware('ability:super:*,supply:*,po:*,po:view')
            ->name('show');
        Route::put('/{purchaseOrder}', [ProcurementControllers\PurchaseOrderController::class, 'update'])
            ->middleware('ability:super:*,supply:*,po:*,po:update')
            ->name('update');
        Route::put('/{purchaseOrder}/pending', [ProcurementControllers\PurchaseOrderController::class, 'pending'])
            ->middleware('ability:super:*,supply:*,po:*,po:pending')
            ->name('pending');
        Route::put('/{purchaseOrder}/approve', [ProcurementControllers\PurchaseOrderController::class, 'approve'])
            ->middleware('ability:super:*,supply:*,po:*,po:approve')
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

    Route::name('inspection_acceptance_reports.')->prefix('/inspection-acceptance-reports')->group(function () {
        Route::get('/', [ProcurementControllers\InspectionAcceptanceReportController::class, 'index'])
            ->middleware('ability:super:*,head:*,supply:*,iar:*,iar:view')
            ->name('index');
        Route::get('/{inspectionAcceptanceReport}', [ProcurementControllers\InspectionAcceptanceReportController::class, 'show'])
            ->middleware('ability:super:*,supply:*,iar:*,iar:view')
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
        // Route::put('/{inspectionAcceptanceReport}/accept', [ProcurementControllers\InspectionAcceptanceReportController::class, 'accept'])
        //     ->middleware('ability:super:*,supply:*,iar:*,iar:accept')
        //     ->name('accept');
    });
});
