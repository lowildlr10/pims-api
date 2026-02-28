<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SignatoryDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'signatory_id' => $this->signatory_id,
            'document' => $this->document,
            'signatory_type' => $this->signatory_type,
            'position' => $this->position,
            'signatory' => new SignatoryResource($this->whenLoaded('signatory')),
        ];
    }
}
