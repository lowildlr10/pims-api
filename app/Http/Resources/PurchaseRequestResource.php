<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'department_id' => $this->department_id,
            'section_id' => $this->section_id,
            'pr_no' => $this->pr_no,
            'pr_date' => $this->pr_date,
            'sai_no' => $this->sai_no,
            'sai_date' => $this->sai_date,
            'alobs_no' => $this->alobs_no,
            'alobs_date' => $this->alobs_date,
            'purpose' => $this->purpose,
            'funding_source_id' => $this->funding_source_id,
            'requested_by_id' => $this->requested_by_id,
            'sig_cash_availability_id' => $this->sig_cash_availability_id,
            'sig_approved_by_id' => $this->sig_approved_by_id,
            'rfq_batch' => $this->rfq_batch,
            'disapproved_reason' => $this->disapproved_reason,
            'status' => $this->status,
            'status_timestamps' => $this->status_timestamps,
            'total_estimated_cost' => $this->total_estimated_cost,
            'total_estimated_cost_formatted' => $this->total_estimated_cost_formatted,
            'department' => $this->whenLoaded('department'),
            'section' => $this->whenLoaded('section'),
            'funding_source' => $this->whenLoaded('funding_source'),
            'requestor' => $this->whenLoaded('requestor'),
            'signatory_cash_available' => $this->whenLoaded('signatory_cash_available'),
            'signatory_approval' => $this->whenLoaded('signatory_approval'),
            'items' => $this->whenLoaded('items', fn () => PurchaseRequestItemResource::collection($this->items)),
            'rfqs' => $this->whenLoaded('rfqs', fn () => RequestQuotationResource::collection($this->rfqs)),
            'aoqs' => $this->whenLoaded('aoqs', fn () => AbstractQuotationResource::collection($this->aoqs)),
            'pos' => $this->whenLoaded('pos', fn () => PurchaseOrderResource::collection($this->pos)),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
