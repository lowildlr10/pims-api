<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'firstname' => $this->firstname,
            'middlename' => $this->middlename,
            'lastname' => $this->lastname,
            'fullname' => $this->fullname,
            'sex' => $this->sex,
            'username' => $this->username,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar,
            'restricted' => $this->restricted,
            'allow_signature' => $this->allow_signature,
            'department' => $this->whenLoaded('department'),
            'section' => $this->whenLoaded('section'),
            'position' => $this->whenLoaded('position'),
            'designation' => $this->whenLoaded('designation'),
            'roles' => $this->whenLoaded('roles'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
