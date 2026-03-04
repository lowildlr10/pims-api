<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InspectionAcceptanceReportItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'inspection_acceptance_report_id' => $this->inspection_acceptance_report_id,
            'pr_item_id' => $this->pr_item_id,
            'po_item_id' => $this->po_item_id,
            'accepted' => $this->accepted,
            'unit' => $this->when(
                $this->relationLoaded('pr_item'),
                fn () => $this->pr_item?->unit_issue?->unit_name,
                $this->when(
                    $this->relationLoaded('po_item') && $this->po_item?->relationLoaded('pr_item'),
                    fn () => $this->po_item?->pr_item?->unit_issue?->unit_name
                )
            ),
            'unit_cost' => $this->unit_cost,
            'pr_item' => new PurchaseRequestItemResource($this->whenLoaded('pr_item')),
            'po_item' => new PurchaseOrderItemResource($this->whenLoaded('po_item')),
        ];
    }
}
