<?php

namespace App\Interfaces;

interface LogRepositoryInterface
{
    public function create(array $data, ?string $userId, ?bool $isError);
}
