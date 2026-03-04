<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxWithholdingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_vat' => $this->is_vat,
            'ewt_rate' => (float) $this->ewt_rate,
            'ptax_rate' => (float) $this->ptax_rate,
            'active' => $this->active,
        ];
    }
}
