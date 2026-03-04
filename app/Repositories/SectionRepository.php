<?php

namespace App\Repositories;

use App\Interfaces\SectionRepositoryInterface;
use App\Models\Section;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class SectionRepository implements SectionRepositoryInterface
{
    public function __construct(
        protected Section $model
    ) {}

    public function getModel(): string
    {
        return Section::class;
    }

    public function getAll(array $filters): LengthAwarePaginator
    {
        $query = $this->model->query()->with('department');

        $search = $filters['search'] ?? '';
        $perPage = $filters['per_page'] ?? 50;
        $columnSort = $filters['column_sort'] ?? 'section_name';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $filterByDepartment = $filters['filter_by_department'] ?? false;
        $restrictToId = $filters['restrict_to_id'] ?? null;

        if ($restrictToId) {
            $query->where('id', $restrictToId);
        }
        $departmentId = $filters['department_id'] ?? '';

        if (! empty($search)) {
            $query->where(function ($query) use ($search) {
                $query->where('section_name', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('department', 'department_name', 'ILIKE', "%{$search}%");
            });
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            $query->orderBy($columnSort, $sortDirection);
        }

        if ($filterByDepartment && ! empty($departmentId)) {
            $query->where('department_id', $departmentId);
        } elseif ($filterByDepartment && empty($departmentId)) {
            $query->limit(0);
        }

        return $query->paginate($perPage);
    }

    public function getById(string $id): ?Model
    {
        return $this->model->with(['department', 'head'])->find($id);
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
