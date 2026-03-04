<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventorySupplyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'iar_id' => $this->iar_id,
            'purchase_order_item_id' => $this->purchase_order_item_id,
            'purchase_request_item_id' => $this->purchase_request_item_id,
            'item_sequence' => $this->item_sequence,
            'sku' => $this->sku,
            'upc' => $this->upc,
            'name' => $this->name,
            'description' => $this->description,
            'item_classification_id' => $this->item_classification_id,
            'unit_issue_id' => $this->unit_issue_id,
            'required_document' => $this->required_document,
            'quantity' => $this->quantity,
            'available' => $this->available,
            'reserved' => $this->reserved,
            'issued' => $this->issued,
            'status' => $this->status,
            'unit_cost' => $this->unit_cost,
            'total_cost' => $this->total_cost,
            'remaining_quantity' => $this->remaining_quantity,
            'unit_issue' => new UnitIssueResource($this->whenLoaded('unit_issue')),
            'item_classification' => new ItemClassificationResource($this->whenLoaded('item_classification')),
            'issued_items' => $this->whenLoaded('issued_items', fn () => $this->issued_items->map(fn ($item) => [
                'id' => $item->id,
                'inventory_supply_id' => $item->inventory_supply_id,
                'inventory_issuance_id' => $item->inventory_issuance_id,
                'quantity' => $item->quantity,
                'inventory_item_no' => $item->inventory_item_no,
                'property_no' => $item->property_no,
                'acquired_date' => $item->acquired_date,
                'estimated_useful_life' => $item->estimated_useful_life,
                'issuance' => $item->issuance ? [
                    'id' => $item->issuance->id,
                    'inventory_no' => $item->issuance->inventory_no,
                    'inventory_date' => $item->issuance->inventory_date,
                    'document_type' => $item->issuance->document_type,
                    'recipient' => $item->issuance->recipient ? [
                        'id' => $item->issuance->recipient->id,
                        'fullname' => $item->issuance->recipient->fullname,
                    ] : null,
                ] : null,
            ])),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
