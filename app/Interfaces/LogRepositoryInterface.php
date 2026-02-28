<?php

namespace App\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

interface LogRepositoryInterface
{
    public function create(array $data, ?string $userId, ?bool $isError): Model;

    public function getAll(array $filters, ?string $userId, bool $isSuper): LengthAwarePaginator;
}
