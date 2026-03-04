<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RequestQuotationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'purchase_request_id' => $this->purchase_request_id,
            'signed_type' => $this->signed_type,
            'rfq_date' => $this->rfq_date,
            'rfq_no' => $this->rfq_no,
            'supplier_id' => $this->supplier_id,
            'opening_dt' => $this->opening_dt,
            'sig_approval_id' => $this->sig_approval_id,
            'vat_registered' => $this->vat_registered,
            'batch' => $this->batch,
            'status' => $this->status?->value,
            'status_formatted' => $this->status?->label(),
            'status_timestamps' => $this->status_timestamps,
            'grand_total_cost' => $this->grand_total_cost,
            'grand_total_cost_formatted' => $this->when(
                $this->grand_total_cost,
                fn () => '₱'.number_format($this->grand_total_cost, 2)
            ),
            'supplier' => $this->whenLoaded('supplier', fn () => new SupplierResource($this->supplier)),
            'signatory_approval' => $this->whenLoaded('signatory_approval', fn () => new SignatoryResource($this->signatory_approval)),
            'canvassers' => $this->whenLoaded('canvassers'),
            'items' => $this->whenLoaded('items', fn () => RequestQuotationItemResource::collection($this->items)),
            'purchase_request' => $this->whenLoaded('purchase_request', fn () => new PurchaseRequestResource($this->purchase_request)),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
