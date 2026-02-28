<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_name' => $this->company_name,
            'address' => $this->address,
            'municipality' => $this->municipality,
            'province' => $this->province,
            'region' => $this->region,
            'company_type' => $this->company_type,
            'company_head_id' => $this->company_head_id,
            'head' => $this->whenLoaded('head'),
            'favicon' => $this->favicon,
            'company_logo' => $this->company_logo,
            'bagong_pilipinas_logo' => $this->bagong_pilipinas_logo,
            'login_background' => $this->login_background,
            'theme_colors' => $this->theme_colors,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
