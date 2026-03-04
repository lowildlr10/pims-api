<?php

namespace App\Repositories;

use App\Interfaces\TaxWithholdingRepositoryInterface;
use App\Models\TaxWithholding;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class TaxWithholdingRepository implements TaxWithholdingRepositoryInterface
{
    public function getAll(array $filters): LengthAwarePaginator|Collection
    {
        $search = $filters['search'] ?? '';
        $perPage = $filters['per_page'] ?? 50;
        $showAll = $filters['show_all'] ?? false;
        $columnSort = $filters['column_sort'] ?? 'name';
        $sortDirection = $filters['sort_direction'] ?? 'asc';
        $paginated = $filters['paginated'] ?? true;

        $query = TaxWithholding::query()
            ->when($search, function ($q) use ($search) {
                $q->whereRaw('CAST(id AS TEXT) = ?', [$search])
                    ->orWhere('name', 'ILIKE', "%{$search}%");
            })
            ->orderBy($columnSort, $sortDirection);

        if ($paginated) {
            return $query->paginate($perPage);
        }

        return $showAll ? $query->get() : $query->limit($perPage)->get();
    }

    public function getById(string $id): ?TaxWithholding
    {
        return TaxWithholding::find($id);
    }

    public function create(array $data): TaxWithholding
    {
        return TaxWithholding::create($data);
    }

    public function update(string $id, array $data): TaxWithholding
    {
        $taxWithholding = TaxWithholding::findOrFail($id);
        $taxWithholding->update($data);

        return $taxWithholding->fresh();
    }
}
