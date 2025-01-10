<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\Library as LibraryControllers;

Route::middleware('auth:sanctum')->prefix('/libraries')->group(function() {
    Route::apiResource('/departments', LibraryControllers\DepartmentController::class);
    Route::apiResource('/funding-sources', LibraryControllers\FundingSourceController::class);
    Route::apiResource('/inventory-classifications', LibraryControllers\InventoryClassificationController::class);
    Route::apiResource('/item-classifications', LibraryControllers\ItemClassificationController::class);
    Route::apiResource('/mfo-paps', LibraryControllers\MfoPapController::class);
    Route::apiResource('/mode-procurements', LibraryControllers\ModeProcurementController::class);
    Route::apiResource('/paper-sizes', LibraryControllers\PaperSizeController::class);
    Route::apiResource('/sections', LibraryControllers\SectionController::class);
    Route::apiResource('/suppliers', LibraryControllers\SupplierController::class);
    Route::apiResource('/uacs-codes', LibraryControllers\UacsCodeController::class);
    Route::apiResource('/unit-issues', LibraryControllers\UnitIssueController::class);
});
