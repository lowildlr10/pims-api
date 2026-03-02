<?php

namespace App\Repositories;

use App\Interfaces\AccountClassificationRepositoryInterface;
use App\Models\AccountClassification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class AccountClassificationRepository implements AccountClassificationRepositoryInterface
{
    public function __construct(protected AccountClassification $model) {}

    public function getModel(): string
    {
        return AccountClassification::class;
    }

    public function getAll(array $filters): LengthAwarePaginator
    {
        $query = $this->model->query();
        $search = $filters['search'] ?? '';
        $perPage = $filters['per_page'] ?? 50;
        $showAll = $filters['show_all'] ?? false;
        $showInactive = $filters['show_inactive'] ?? false;
        $paginated = $filters['paginated'] ?? true;
        $columnSort = $filters['column_sort'] ?? 'classification_name';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('CAST(id AS TEXT) = ?', [$search])
                    ->orWhere('classification_name', 'ILIKE', "%{$search}%");
            });
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            if ($columnSort === 'classification_name_formatted') {
                $columnSort = 'classification_name';
            }
            $query->orderBy($columnSort, $sortDirection);
        }
        if (! $paginated) {
            $items = $showAll ? $query->get() : $query->limit($perPage)->get();

            return new \Illuminate\Pagination\LengthAwarePaginator($items, $items->count(), $perPage, 1);
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
