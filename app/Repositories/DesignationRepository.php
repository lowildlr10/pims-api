<?php

namespace App\Repositories;

use App\Interfaces\DesignationRepositoryInterface;
use App\Models\Designation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class DesignationRepository implements DesignationRepositoryInterface
{
    public function __construct(
        protected Designation $model
    ) {}

    public function getModel(): string
    {
        return Designation::class;
    }

    public function getAll(array $filters): LengthAwarePaginator
    {
        $query = $this->model->query();

        $search = $filters['search'] ?? '';
        $perPage = $filters['per_page'] ?? 50;
        $columnSort = $filters['column_sort'] ?? 'designation_name';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        if (! empty($search)) {
            $query->where('designation_name', 'ILIKE', "%{$search}%");
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            $query->orderBy($columnSort, $sortDirection);
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
