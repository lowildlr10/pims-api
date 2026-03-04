<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RequestQuotationItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'request_quotation_id' => $this->request_quotation_id,
            'pr_item_id' => $this->pr_item_id,
            'supplier_id' => $this->supplier_id,
            'brand_model' => $this->brand_model,
            'unit_cost' => $this->unit_cost,
            'unit_cost_formatted' => $this->unit_cost_formatted,
            'total_cost' => $this->total_cost,
            'total_cost_formatted' => $this->total_cost_formatted,
            'included' => $this->included,
            'pr_item' => $this->whenLoaded('pr_item'),
        ];
    }
}
