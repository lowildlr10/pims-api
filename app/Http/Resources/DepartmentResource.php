<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $sections = $this->whenLoaded('sections', function () {
            $sections = $this->sections;

            $user = auth()->user();
            if ($user) {
                $higherRoles = ['super:*', 'head:*', 'supply:*', 'budget:*', 'accountant:*', 'treasurer:*'];
                $isEndUserOnly = $user->tokenCan('user:*') && ! collect($higherRoles)->some(fn ($role) => $user->tokenCan($role));

                if ($isEndUserOnly && $user->section_id) {
                    $sections = $sections->where('id', $user->section_id);
                }
            }

            return $sections->values()->all();
        });

        return [
            'id' => $this->id,
            'department_name' => $this->department_name,
            'department_head_id' => $this->department_head_id,
            'active' => $this->active,
            'head' => $this->whenLoaded('head'),
            'sections' => $sections,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
