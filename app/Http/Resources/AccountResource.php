<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'classification_id' => $this->classification_id,
            'classification' => new AccountClassificationResource($this->whenLoaded('classification')),
            'account_title' => $this->account_title,
            'code' => $this->code,
            'description' => $this->description,
            'active' => $this->active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
