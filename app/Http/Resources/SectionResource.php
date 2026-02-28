<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'section_name' => $this->section_name,
            'department_id' => $this->department_id,
            'section_head_id' => $this->section_head_id,
            'active' => $this->active,
            'department' => $this->whenLoaded('department'),
            'head' => $this->whenLoaded('head'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
