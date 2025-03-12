<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    // /**
    //  * Store a newly created resource in storage.
    //  */
    // public function store(Request $request)
    // {
    //     //
    // }

    /**
     * Display the specified resource.
     */
    public function show(PurchaseOrder $purchaseOrder)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        //
    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function pending(PurchaseOrder $purchaseOrder)
    {

    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function approve(PurchaseOrder $purchaseOrder)
    {

    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function issue(PurchaseOrder $purchaseOrder)
    {

    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function receive(PurchaseOrder $purchaseOrder)
    {

    }
}
