<?php

namespace App\Repositories;

use App\Interfaces\LogRepositoryInterface;
use App\Models\Log;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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

    public function getAll(array $filters, ?string $userId, bool $isSuper): LengthAwarePaginator
    {
        $query = Log::with('user:id,firstname,middlename,lastname');

        $search = $filters['search'] ?? '';
        $perPage = $filters['per_page'] ?? 50;
        $columnSort = $filters['column_sort'] ?? 'logged_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $logId = $filters['log_id'] ?? '';

        if (! $isSuper && empty($logId) && $userId) {
            $query->where('user_id', $userId);
        }

        if (! empty($search) && empty($logId)) {
            $query->where(function ($q) use ($search) {
                $q->where('log_id', 'ILIKE', "%{$search}%")
                    ->orWhere('log_module', 'ILIKE', "%{$search}%")
                    ->orWhere('log_type', 'ILIKE', "%{$search}%")
                    ->orWhere('message', 'ILIKE', "%{$search}%")
                    ->orWhere('details', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('user', 'firstname', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('user', 'middlename', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('user', 'lastname', 'ILIKE', "%{$search}%");
            });
        }

        if ($logId) {
            $query->where('log_id', $logId);
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            if ($columnSort === 'user_formatted') {
                $query->orderBy(
                    User::select('firstname')->whereColumn('users.id', 'logs.user_id')
                );
            } elseif ($columnSort === 'log_module_formatted') {
                $columnSort = 'log_module';
            } elseif ($columnSort === 'log_type_formatted') {
                $columnSort = 'log_type';
            } elseif ($columnSort === 'logged_at_formatted') {
                $columnSort = 'logged_at';
            }

            if ($columnSort) {
                $query->orderBy($columnSort, $sortDirection);
            }
        }

        return $query->paginate($perPage);
    }
}
