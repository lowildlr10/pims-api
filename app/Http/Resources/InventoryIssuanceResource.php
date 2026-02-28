<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryIssuanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'purchase_order_id' => $this->purchase_order_id,
            'responsibility_center_id' => $this->responsibility_center_id,
            'inventory_no' => $this->inventory_no,
            'inventory_date' => $this->inventory_date,
            'inventory_date_formatted' => $this->inventory_date?->format('F d, Y'),
            'sai_no' => $this->sai_no,
            'sai_date' => $this->sai_date,
            'document_type' => $this->document_type,
            'requested_by_id' => $this->requested_by_id,
            'requested_date' => $this->requested_date,
            'sig_approved_by_id' => $this->sig_approved_by_id,
            'approved_date' => $this->approved_date,
            'sig_issued_by_id' => $this->sig_issued_by_id,
            'issued_date' => $this->issued_date,
            'received_by_id' => $this->received_by_id,
            'received_date' => $this->received_date,
            'status' => $this->status?->value,
            'status_formatted' => $this->status?->label(),
            'status_timestamps' => $this->status_timestamps,
            'requestor' => new UserResource($this->whenLoaded('requestor')),
            'signatory_approval' => new SignatoryResource($this->whenLoaded('signatory_approval')),
            'signatory_issuer' => new SignatoryResource($this->whenLoaded('signatory_issuer')),
            'recipient' => new UserResource($this->whenLoaded('recipient')),
            'responsibility_center' => new ResponsibilityCenterResource($this->whenLoaded('responsibility_center')),
            'purchase_order' => new PurchaseOrderResource($this->whenLoaded('purchase_order')),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'inventory_supply_id' => $item->inventory_supply_id,
                'quantity' => $item->quantity,
                'inventory_item_no' => $item->inventory_item_no,
                'property_no' => $item->property_no,
                'acquired_date' => $item->acquired_date,
                'estimated_useful_life' => $item->estimated_useful_life,
                'unit_cost' => $item->unit_cost,
                'total_cost' => $item->total_cost,
                'supply' => $item->supply ? [
                    'id' => $item->supply->id,
                    'sku' => $item->supply->sku,
                    'upc' => $item->supply->upc,
                    'name' => $item->supply->name,
                    'description' => $item->supply->description,
                    'unit_issue' => $item->supply->unit_issue ? [
                        'id' => $item->supply->unit_issue->id,
                        'unit_name' => $item->supply->unit_issue->unit_name,
                    ] : null,
                ] : null,
            ])),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
