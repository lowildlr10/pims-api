<?php

namespace App\Repositories;

use App\Interfaces\ProcurementModeRepositoryInterface;
use App\Models\ProcurementMode;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class ProcurementModeRepository implements ProcurementModeRepositoryInterface
{
    public function __construct(
        protected ProcurementMode $model
    ) {}

    public function getModel(): string
    {
        return ProcurementMode::class;
    }

    public function getAll(array $filters): LengthAwarePaginator
    {
        $query = $this->model->query();

        $search = $filters['search'] ?? '';
        $perPage = $filters['per_page'] ?? 50;
        $showAll = $filters['show_all'] ?? false;
        $showInactive = $filters['show_inactive'] ?? false;
        $paginated = $filters['paginated'] ?? true;
        $columnSort = $filters['column_sort'] ?? 'mode_name';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('CAST(id AS TEXT) = ?', [$search])
                    ->orWhere('mode_name', 'ILIKE', "%{$search}%");
            });
        }

        if (! $showInactive) {
            $query->where('active', true);
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            switch ($columnSort) {
                case 'mode_name_formatted':
                    $columnSort = 'mode_name';
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
