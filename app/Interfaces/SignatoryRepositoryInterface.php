<?php

namespace App\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface SignatoryRepositoryInterface
{
    public function getAll(array $filters): LengthAwarePaginator;

    public function getByDocumentAndType(string $document, string $signatoryType, array $filters): Collection;

    public function getById(string $id): ?Model;

    public function create(array $data): Model;

    public function update(string $id, array $data): Model;

    public function delete(string $id): bool;

    public function deleteDetails(string $signatoryId): void;

    public function createDetail(array $data): void;

    public function getModel(): string;
}
