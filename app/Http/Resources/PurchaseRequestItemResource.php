<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseRequestItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'purchase_request_id' => $this->purchase_request_id,
            'item_sequence' => $this->item_sequence,
            'quantity' => $this->quantity,
            'unit_issue_id' => $this->unit_issue_id,
            'description' => $this->description,
            'stock_no' => $this->stock_no,
            'estimated_cost' => $this->estimated_cost,
            'estimated_cost_formatted' => $this->estimated_cost_formatted,
            'estimated_unit_cost' => $this->estimated_unit_cost,
            'estimated_unit_cost_formatted' => $this->when($this->estimated_unit_cost, fn () => '₱'.number_format($this->estimated_unit_cost, 2)),
            'estimated_total_cost' => $this->estimated_total_cost,
            'estimated_total_cost_formatted' => $this->when($this->estimated_total_cost, fn () => '₱'.number_format($this->estimated_total_cost, 2)),
            'awarded_to_id' => $this->awarded_to_id,
            'awarded_to' => $this->whenLoaded('awarded_to'),
            'unit_issue' => $this->whenLoaded('unit_issue', fn () => new UnitIssueResource($this->unit_issue)),
        ];
    }
}
