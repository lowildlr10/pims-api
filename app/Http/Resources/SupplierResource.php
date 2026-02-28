<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'supplier_name' => $this->supplier_name,
            'address' => $this->address,
            'tin_no' => $this->tin_no,
            'phone' => $this->phone,
            'telephone' => $this->telephone,
            'vat_no' => $this->vat_no,
            'contact_person' => $this->contact_person,
            'active' => $this->active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
