<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'purchase_request_id' => $this->purchase_request_id,
            'po_no' => $this->po_no,
            'po_date' => $this->po_date,
            'mode_procurement_id' => $this->mode_procurement_id,
            'supplier_id' => $this->supplier_id,
            'place_delivery_id' => $this->place_delivery_id,
            'delivery_date' => $this->delivery_date,
            'delivery_term_id' => $this->delivery_term_id,
            'payment_term_id' => $this->payment_term_id,
            'total_amount_words' => $this->total_amount_words,
            'total_amount' => $this->total_amount,
            'total_amount_formatted' => $this->when($this->total_amount, fn () => '₱'.number_format($this->total_amount, 2)),
            'sig_approval_id' => $this->sig_approval_id,
            'document_type' => $this->document_type,
            'status' => $this->status?->value,
            'status_formatted' => $this->status?->label(),
            'status_timestamps' => $this->status_timestamps,
            'supplier' => $this->whenLoaded('supplier', fn () => new SupplierResource($this->supplier)),
            'mode_procurement' => $this->whenLoaded('mode_procurement', fn () => new ProcurementModeResource($this->mode_procurement)),
            'place_delivery' => $this->whenLoaded('place_delivery', fn () => new LocationResource($this->place_delivery)),
            'delivery_term' => $this->whenLoaded('delivery_term', fn () => new DeliveryTermResource($this->delivery_term)),
            'payment_term' => $this->whenLoaded('payment_term', fn () => new PaymentTermResource($this->payment_term)),
            'signatory_approval' => $this->whenLoaded('signatory_approval', fn () => new SignatoryResource($this->signatory_approval)),
            'items' => $this->whenLoaded('items', fn () => PurchaseOrderItemResource::collection($this->items)),
            'supplies' => $this->whenLoaded('supplies', fn () => InventorySupplyResource::collection($this->supplies)),
            'purchase_request' => $this->whenLoaded('purchase_request', fn () => new PurchaseRequestResource($this->purchase_request)),
            'obligation_request' => $this->whenLoaded('obligation_request'),
            'disbursement_voucher' => $this->whenLoaded('disbursement_voucher'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
