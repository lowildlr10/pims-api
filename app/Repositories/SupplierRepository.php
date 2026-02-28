<?php

namespace App\Repositories;

use App\Interfaces\SupplierRepositoryInterface;
use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class SupplierRepository implements SupplierRepositoryInterface
{
    public function __construct(
        protected Supplier $model
    ) {}

    public function getModel(): string
    {
        return Supplier::class;
    }

    public function getAll(array $filters): LengthAwarePaginator
    {
        $query = $this->model->query();

        $search = $filters['search'] ?? '';
        $perPage = $filters['per_page'] ?? 50;
        $showAll = $filters['show_all'] ?? false;
        $showInactive = $filters['show_inactive'] ?? false;
        $paginated = $filters['paginated'] ?? true;
        $columnSort = $filters['column_sort'] ?? 'supplier_name';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('CAST(id AS TEXT) = ?', [$search])
                    ->orWhere('supplier_name', 'ILIKE', "%{$search}%")
                    ->orWhere('address', 'ILIKE', "%{$search}%")
                    ->orWhere('tin_no', 'ILIKE', "%{$search}%")
                    ->orWhere('phone', 'ILIKE', "%{$search}%")
                    ->orWhere('telephone', 'ILIKE', "%{$search}%")
                    ->orWhere('vat_no', 'ILIKE', "%{$search}%")
                    ->orWhere('contact_person', 'ILIKE', "%{$search}%");
            });
        }

        if (! $showInactive) {
            $query->where('active', true);
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            switch ($columnSort) {
                case 'supplier_name_formatted':
                    $columnSort = 'supplier_name';
                    break;
            }

            $query->orderBy($columnSort, $sortDirection);
        }

        if (! $paginated) {
            $items = $showAll ? $query->get() : $query->limit($perPage)->get();

            return new \Illuminate\Pagination\LengthAwarePaginator(
                $items,
                $items->count(),
                $perPage,
                1
            );
        }

        return $query->paginate($perPage);
    }

    public function getById(string $id): ?Model
    {
        return $this->model->find($id);
    }

    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    public function update(string $id, array $data): Model
    {
        $model = $this->model->findOrFail($id);
        $model->update($data);

        return $model;
    }

    public function delete(string $id): bool
    {
        return $this->model->findOrFail($id)->delete();
    }
}
