<?php

namespace App\Repositories;

use App\Interfaces\LogRepositoryInterface;
use App\Models\Log;

class LogRepository implements LogRepositoryInterface
{
    public function create(array $data, ?string $userId = null, ?bool $isError = false): Log
    {
        return Log::create([
            'user_id' => $userId ?? auth()->user()->id ?? null,
            'log_id' => $data['log_id'] ?? null,
            'log_module' => $data['log_module'],
            'log_type' => $isError ? 'error' : 'log',
            'message' => $data['message'],
            'details' => $data['details'] ?? null,
            'data' => $data['data'] ?? null,
        ]);
    }
}
