<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AbstractQuotationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'purchase_request_id' => $this->purchase_request_id,
            'bids_awards_committee_id' => $this->bids_awards_committee_id,
            'mode_procurement_id' => $this->mode_procurement_id,
            'solicitation_no' => $this->solicitation_no,
            'solicitation_date' => $this->solicitation_date,
            'opened_on' => $this->opened_on,
            'abstract_no' => $this->abstract_no,
            'bac_action' => $this->bac_action,
            'status' => $this->status?->value,
            'status_formatted' => $this->status?->label(),
            'status_timestamps' => $this->status_timestamps,
            'bids_awards_committee' => $this->whenLoaded('bids_awards_committee', fn () => new BidsAwardsCommitteeResource($this->bids_awards_committee)),
            'mode_procurement' => $this->whenLoaded('mode_procurement', fn () => new ProcurementModeResource($this->mode_procurement)),
            'sig_twg_chairperson_id' => $this->sig_twg_chairperson_id,
            'sig_twg_member_1_id' => $this->sig_twg_member_1_id,
            'sig_twg_member_2_id' => $this->sig_twg_member_2_id,
            'sig_chairman_id' => $this->sig_chairman_id,
            'sig_vice_chairman_id' => $this->sig_vice_chairman_id,
            'sig_member_1_id' => $this->sig_member_1_id,
            'sig_member_2_id' => $this->sig_member_2_id,
            'sig_member_3_id' => $this->sig_member_3_id,
            'signatory_twg_chairperson' => $this->whenLoaded('signatory_twg_chairperson', fn () => new SignatoryResource($this->signatory_twg_chairperson)),
            'signatory_twg_member_1' => $this->whenLoaded('signatory_twg_member_1', fn () => new SignatoryResource($this->signatory_twg_member_1)),
            'signatory_twg_member_2' => $this->whenLoaded('signatory_twg_member_2', fn () => new SignatoryResource($this->signatory_twg_member_2)),
            'signatory_chairman' => $this->whenLoaded('signatory_chairman', fn () => new SignatoryResource($this->signatory_chairman)),
            'signatory_vice_chairman' => $this->whenLoaded('signatory_vice_chairman', fn () => new SignatoryResource($this->signatory_vice_chairman)),
            'signatory_member_1' => $this->whenLoaded('signatory_member_1', fn () => new SignatoryResource($this->signatory_member_1)),
            'signatory_member_2' => $this->whenLoaded('signatory_member_2', fn () => new SignatoryResource($this->signatory_member_2)),
            'signatory_member_3' => $this->whenLoaded('signatory_member_3', fn () => new SignatoryResource($this->signatory_member_3)),
            'items' => $this->whenLoaded('items', fn () => AbstractQuotationItemResource::collection($this->items)),
            'purchase_request' => $this->whenLoaded('purchase_request', fn () => new PurchaseRequestResource($this->purchase_request)),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
