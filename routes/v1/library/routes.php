<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\Library as LibraryControllers;

Route::middleware('auth:sanctum')->prefix('/libraries')->group(function() {
    Route::name('locations.')->prefix('/locations')->group(function () {
        Route::get('/', [LibraryControllers\LocationController::class, 'index'])
            ->name('index');
    });

    Route::name('bids-awards-committees.')->prefix('/bids-awards-committees')->group(function () {
        Route::get('/', [LibraryControllers\BidsAwardsCommitteeController::class, 'index'])
            ->middleware('ability:super:*,head:*,lib-bid-committee:*,lib-bid-committee:view')
            ->name('index');
        Route::post('/', [LibraryControllers\BidsAwardsCommitteeController::class, 'store'])
            ->middleware('ability:super:*,lib-bid-committee:*,lib-bid-committee:create')
            ->name('store');
        Route::get('/{bidsAwardsCommittee}', [LibraryControllers\BidsAwardsCommitteeController::class, 'show'])
            ->middleware('ability:super:*,lib-bid-committee:*,lib-bid-committee:view')
            ->name('show');
        Route::put('/{bidsAwardsCommittee}', [LibraryControllers\BidsAwardsCommitteeController::class, 'update'])
            ->middleware('ability:super:*,lib-bid-committee:*,lib-bid-committee:update')
            ->name('update');
    });

    Route::name('funding-sources.')->prefix('/funding-sources')->group(function () {
        Route::get('/', [LibraryControllers\FundingSourceController::class, 'index'])
            ->middleware('ability:super:*,head:*,lib-fund-source:*,lib-fund-source:view')
            ->name('index');
        Route::post('/', [LibraryControllers\FundingSourceController::class, 'store'])
            ->middleware('ability:super:*,lib-fund-source:*,lib-fund-source:create')
            ->name('store');
        Route::get('/{fundingSource}', [LibraryControllers\FundingSourceController::class, 'show'])
            ->middleware('ability:super:*,lib-fund-source:*,lib-fund-source:view')
            ->name('show');
        Route::put('/{fundingSource}', [LibraryControllers\FundingSourceController::class, 'update'])
            ->middleware('ability:super:*,lib-fund-source:*,lib-fund-source:update')
            ->name('update');
    });

    Route::name('item-classifications.')->prefix('/item-classifications')->group(function () {
        Route::get('/', [LibraryControllers\ItemClassificationController::class, 'index'])
            ->middleware('ability:super:*,head:*,lib-item-class:*,lib-item-class:view')
            ->name('index');
        Route::post('/', [LibraryControllers\ItemClassificationController::class, 'store'])
            ->middleware('ability:super:*,lib-item-class:*,lib-item-class:create')
            ->name('store');
        Route::get('/{itemClassification}', [LibraryControllers\ItemClassificationController::class, 'show'])
            ->middleware('ability:super:*,lib-item-class:*,lib-item-class:view')
            ->name('show');
        Route::put('/{itemClassification}', [LibraryControllers\ItemClassificationController::class, 'update'])
            ->middleware('ability:super:*,lib-item-class:*,lib-item-class:update')
            ->name('update');
    });

    Route::name('mfo-paps.')->prefix('/mfo-paps')->group(function () {
        Route::get('/', [LibraryControllers\MfoPapController::class, 'index'])
            ->middleware('ability:super:*,head:*,lib-mfo-pap:*,lib-mfo-pap:view')
            ->name('index');
        Route::post('/', [LibraryControllers\MfoPapController::class, 'store'])
            ->middleware('ability:super:*,lib-mfo-pap:*,lib-mfo-pap:create')
            ->name('store');
        Route::get('/{mfoPap}', [LibraryControllers\MfoPapController::class, 'show'])
            ->middleware('ability:super:*,lib-mfo-pap:*,lib-mfo-pap:view')
            ->name('show');
        Route::put('/{mfoPap}', [LibraryControllers\MfoPapController::class, 'update'])
            ->middleware('ability:super:*,lib-mfo-pap:*,lib-mfo-pap:update')
            ->name('update');
    });

    Route::name('procurement-modes.')->prefix('/procurement-modes')->group(function () {
        Route::get('/', [LibraryControllers\ProcurementModeController::class, 'index'])
            ->middleware('ability:super:*,head:*,lib-mode-proc:*,lib-mode-proc:view')
            ->name('index');
        Route::post('/', [LibraryControllers\ProcurementModeController::class, 'store'])
            ->middleware('ability:super:*,lib-mode-proc:*,lib-mode-proc:create')
            ->name('store');
        Route::get('/{procurementMode}', [LibraryControllers\ProcurementModeController::class, 'show'])
            ->middleware('ability:super:*,lib-mode-proc:*,lib-mode-proc:view')
            ->name('show');
        Route::put('/{procurementMode}', [LibraryControllers\ProcurementModeController::class, 'update'])
            ->middleware('ability:super:*,lib-mode-proc:*,lib-mode-proc:update')
            ->name('update');
    });

    Route::name('paper-sizes.')->prefix('/paper-sizes')->group(function () {
        Route::get('/', [LibraryControllers\PaperSizeController::class, 'index'])
            ->middleware('ability:super:*,head:*,lib-paper-size:*,lib-paper-size:view')
            ->name('index');
        Route::post('/', [LibraryControllers\PaperSizeController::class, 'store'])
            ->middleware('ability:super:*,lib-paper-size:*,lib-paper-size:create')
            ->name('store');
        Route::get('/{paperSize}', [LibraryControllers\PaperSizeController::class, 'show'])
            ->middleware('ability:super:*,lib-paper-size:*,lib-paper-size:view')
            ->name('show');
        Route::put('/{paperSize}', [LibraryControllers\PaperSizeController::class, 'update'])
            ->middleware('ability:super:*,lib-paper-size:*,lib-paper-size:update')
            ->name('update');
        Route::delete('/{paperSize}', [LibraryControllers\PaperSizeController::class, 'delete'])
            ->middleware('ability:super:*,lib-paper-size:*,lib-paper-size:delete')
            ->name('delete');
    });

    Route::name('responsibility-centers.')->prefix('/responsibility-centers')->group(function () {
        Route::get('/', [LibraryControllers\ResponsibilityCenterController::class, 'index'])
            ->middleware('ability:super:*,head:*,lib-responsibility-center:*,lib-responsibility-center:view')
            ->name('index');
        Route::post('/', [LibraryControllers\ResponsibilityCenterController::class, 'store'])
            ->middleware('ability:super:*,lib-responsibility-center:*,lib-responsibility-center:create')
            ->name('store');
        Route::get('/{responsibilityCenter}', [LibraryControllers\ResponsibilityCenterController::class, 'show'])
            ->middleware('ability:super:*,lib-responsibility-center:*,lib-responsibility-center:view')
            ->name('show');
        Route::put('/{responsibilityCenter}', [LibraryControllers\ResponsibilityCenterController::class, 'update'])
            ->middleware('ability:super:*,lib-responsibility-center:*,lib-responsibility-center:update')
            ->name('update');
    });

    Route::name('signatories.')->prefix('/signatories')->group(function () {
        Route::get('/', [LibraryControllers\SignatoryController::class, 'index'])
            ->middleware('ability:super:*,head:*,lib-signatory:*,lib-signatory:view')
            ->name('index');
        Route::post('/', [LibraryControllers\SignatoryController::class, 'store'])
            ->middleware('ability:super:*,lib-signatory:*,lib-signatory:create')
            ->name('store');
        Route::get('/{signatory}', [LibraryControllers\SignatoryController::class, 'show'])
            ->middleware('ability:super:*,lib-signatory:*,lib-signatory:view')
            ->name('show');
        Route::put('/{signatory}', [LibraryControllers\SignatoryController::class, 'update'])
            ->middleware('ability:super:*,lib-signatory:*,lib-signatory:update')
            ->name('update');
    });

    Route::name('suppliers.')->prefix('/suppliers')->group(function () {
        Route::get('/', [LibraryControllers\SupplierController::class, 'index'])
            ->middleware('ability:super:*,head:*,lib-supplier:*,lib-supplier:view')
            ->name('index');
        Route::post('/', [LibraryControllers\SupplierController::class, 'store'])
            ->middleware('ability:super:*,lib-supplier:*,lib-supplier:create')
            ->name('store');
        Route::get('/{supplier}', [LibraryControllers\SupplierController::class, 'show'])
            ->middleware('ability:super:*,lib-supplier:*,lib-supplier:view')
            ->name('show');
        Route::put('/{supplier}', [LibraryControllers\SupplierController::class, 'update'])
            ->middleware('ability:super:*,lib-supplier:*,lib-supplier:update')
            ->name('update');
    });

    Route::name('uacs-code-classifications.')->prefix('/uacs-code-classifications')->group(function () {
        Route::get('/', [LibraryControllers\UacsCodeClassificationController::class, 'index'])
            ->middleware('ability:super:*,head:*,lib-uacs-class:*,lib-uacs-class:view')
            ->name('index');
        Route::post('/', [LibraryControllers\UacsCodeClassificationController::class, 'store'])
            ->middleware('ability:super:*,lib-uacs-class:*,lib-uacs-class:create')
            ->name('store');
        Route::get('/{uacsCodeClassification}', [LibraryControllers\UacsCodeClassificationController::class, 'show'])
            ->middleware('ability:super:*,lib-uacs-class:*,lib-uacs-class:view')
            ->name('show');
        Route::put('/{uacsCodeClassification}', [LibraryControllers\UacsCodeClassificationController::class, 'update'])
            ->middleware('ability:super:*,lib-uacs-class:*,lib-uacs-class:update')
            ->name('update');
    });

    Route::name('uacs-codes.')->prefix('/uacs-codes')->group(function () {
        Route::get('/', [LibraryControllers\UacsCodeController::class, 'index'])
            ->middleware('ability:super:*,head:*,lib-uacs-code:*,lib-uacs-code:view')
            ->name('index');
        Route::post('/', [LibraryControllers\UacsCodeController::class, 'store'])
            ->middleware('ability:super:*,lib-uacs-code:*,lib-uacs-code:create')
            ->name('store');
        Route::get('/{uacsCode}', [LibraryControllers\UacsCodeController::class, 'show'])
            ->middleware('ability:super:*,lib-uacs-code:*,lib-uacs-code:view')
            ->name('show');
        Route::put('/{uacsCode}', [LibraryControllers\UacsCodeController::class, 'update'])
            ->middleware('ability:super:*,lib-uacs-code:*,lib-uacs-code:update')
            ->name('update');
    });

    Route::name('unit-issues.')->prefix('/unit-issues')->group(function () {
        Route::get('/', [LibraryControllers\UnitIssueController::class, 'index'])
            ->middleware('ability:super:*,head:*,lib-unit-issue:*,lib-unit-issue:view')
            ->name('index');
        Route::post('/', [LibraryControllers\UnitIssueController::class, 'store'])
            ->middleware('ability:super:*,lib-unit-issue:*,lib-unit-issue:create')
            ->name('store');
        Route::get('/{unitIssue}', [LibraryControllers\UnitIssueController::class, 'show'])
            ->middleware('ability:super:*,lib-unit-issue:*,lib-unit-issue:view')
            ->name('show');
        Route::put('/{unitIssue}', [LibraryControllers\UnitIssueController::class, 'update'])
            ->middleware('ability:super:*,lib-unit-issue:*,lib-unit-issue:update')
            ->name('update');
    });
});
