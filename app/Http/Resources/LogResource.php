<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'log_id' => $this->log_id,
            'log_module' => $this->log_module,
            'log_type' => $this->log_type,
            'message' => $this->message,
            'details' => $this->details,
            'data' => $this->data,
            'logged_at' => $this->logged_at?->toISOString(),
        ];
    }
}
