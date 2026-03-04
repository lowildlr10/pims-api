<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'purchase_order_id' => $this->purchase_order_id,
            'pr_item_id' => $this->pr_item_id,
            'description' => $this->description,
            'brand_model' => $this->brand_model,
            'unit_cost' => $this->unit_cost,
            'unit_cost_formatted' => $this->when($this->unit_cost, fn () => '₱'.number_format($this->unit_cost, 2)),
            'total_cost' => $this->total_cost,
            'total_cost_formatted' => $this->when($this->total_cost, fn () => '₱'.number_format($this->total_cost, 2)),
            'pr_item' => $this->whenLoaded('pr_item'),
        ];
    }
}
