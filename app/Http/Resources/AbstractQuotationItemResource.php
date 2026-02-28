<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AbstractQuotationItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'abstract_quotation_id' => $this->abstract_quotation_id,
            'pr_item_id' => $this->pr_item_id,
            'awardee_id' => $this->awardee_id,
            'document_type' => $this->document_type,
            'included' => $this->included,
            'awardee' => $this->whenLoaded('awardee', fn () => new SupplierResource($this->awardee)),
            'pr_item' => $this->whenLoaded('pr_item'),
            'details' => $this->whenLoaded('details'),
        ];
    }
}
