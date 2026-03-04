<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InspectionAcceptanceReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'purchase_request_id' => $this->purchase_request_id,
            'purchase_order_id' => $this->purchase_order_id,
            'supplier_id' => $this->supplier_id,
            'iar_no' => $this->iar_no,
            'iar_date' => $this->iar_date,
            'iar_date_formatted' => $this->iar_date?->format('F d, Y'),
            'invoice_no' => $this->invoice_no,
            'invoice_date' => $this->invoice_date,
            'invoice_date_formatted' => $this->invoice_date,
            'inspected_date' => $this->inspected_date,
            'inspected_date_formatted' => $this->inspected_date?->format('F d, Y'),
            'inspected' => $this->inspected,
            'sig_inspection_id' => $this->sig_inspection_id,
            'received_date' => $this->received_date,
            'received_date_formatted' => $this->received_date?->format('F d, Y'),
            'acceptance_completed' => $this->acceptance_completed,
            'acceptance_id' => $this->acceptance_id,
            'status' => $this->status?->value,
            'status_formatted' => $this->status?->label(),
            'status_timestamps' => $this->status_timestamps,
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'purchase_order' => new PurchaseOrderResource($this->whenLoaded('purchase_order')),
            'purchase_request' => new PurchaseRequestResource($this->whenLoaded('purchase_request')),
            'items' => InspectionAcceptanceReportItemResource::collection($this->whenLoaded('items')),
            'signatory_inspection' => new SignatoryResource($this->whenLoaded('signatory_inspection')),
            'acceptance' => new UserResource($this->whenLoaded('acceptance')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
