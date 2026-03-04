<?php

namespace App\Repositories;

use App\Interfaces\DepartmentRepositoryInterface;
use App\Models\Department;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class DepartmentRepository implements DepartmentRepositoryInterface
{
    public function __construct(
        protected Department $model
    ) {}

    public function getModel(): string
    {
        return Department::class;
    }

    public function getAll(array $filters): LengthAwarePaginator
    {
        $query = $this->model->query()->with([
            'sections' => function ($query) {
                $query->orderBy('section_name');
            },
            'sections.head:id,firstname,lastname',
            'head:id,firstname,lastname',
        ]);

        $search = $filters['search'] ?? '';
        $perPage = $filters['per_page'] ?? 50;
        $columnSort = $filters['column_sort'] ?? 'department_name';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $restrictToId = $filters['restrict_to_id'] ?? null;

        if ($restrictToId) {
            $query->where('id', $restrictToId);
        }

        if (! empty($search)) {
            $query->where(function ($query) use ($search) {
                $query->whereRaw('CAST(id AS TEXT) = ?', [$search])
                    ->orWhere('department_name', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('sections', function ($query) use ($search) {
                        $query->whereRaw('CAST(id AS TEXT) = ?', [$search])
                            ->orWhere('section_name', 'ILIKE', "%{$search}%");
                    });
            });
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            switch ($columnSort) {
                case 'headfullname':
                    $columnSort = 'department_head_id';
                    break;
                case 'department_name_formatted':
                    $columnSort = 'department_name';
                    break;
            }

            $query->orderBy($columnSort, $sortDirection);
        }

        return $query->paginate($perPage);
    }

    public function getById(string $id): ?Model
    {
        return $this->model->with('head')->find($id);
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
