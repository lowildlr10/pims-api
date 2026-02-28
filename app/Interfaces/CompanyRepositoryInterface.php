<?php

namespace App\Interfaces;

use Illuminate\Database\Eloquent\Model;

interface CompanyRepositoryInterface
{
    public function get(): ?Model;

    public function update(array $data): Model;

    public function getModel(): string;
}
