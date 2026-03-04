<?php

namespace App\Repositories;

use App\Interfaces\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        protected User $model
    ) {}

    public function getModel(): string
    {
        return User::class;
    }

    public function getAll(array $filters): LengthAwarePaginator
    {
        $query = $this->model->query()->with([
            'department:id,department_name',
            'section:id,section_name',
            'position:id,position_name',
            'designation:id,designation_name',
            'roles:id,role_name',
        ]);

        $search = $filters['search'] ?? '';
        $perPage = $filters['per_page'] ?? 50;
        $columnSort = $filters['column_sort'] ?? 'firstname';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $showInactive = $filters['show_inactive'] ?? false;
        $sectionId = $filters['section_id'] ?? null;

        if (! empty($search)) {
            $query->where(function ($query) use ($search) {
                $query->whereRaw('CAST(id AS TEXT) = ?', [$search])
                    ->orWhere('firstname', 'ILIKE', "%{$search}%")
                    ->orWhere('middlename', 'ILIKE', "%{$search}%")
                    ->orWhere('lastname', 'ILIKE', "%{$search}%")
                    ->orWhere('email', 'ILIKE', "%{$search}%")
                    ->orWhere('phone', 'ILIKE', "%{$search}%")
                    ->orWhere('sex', 'ILIKE', "%{$search}%")
                    ->orWhere('username', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('position', 'position_name', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('designation', 'designation_name', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('department', 'department_name', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('section', 'section_name', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('roles', 'role_name', 'ILIKE', "%{$search}%");
            });
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            switch ($columnSort) {
                case 'fullname_formatted':
                    $query->orderBy('firstname', $sortDirection);
                    break;
                case 'department_section':
                    $query->orderBy('department_id', $sortDirection)
                        ->orderBy('section_id', $sortDirection);
                    break;
                case 'position_designation':
                    $query->orderBy('position_id', $sortDirection)
                        ->orderBy('designation_id', $sortDirection);
                    break;
                default:
                    $query->orderBy($columnSort, $sortDirection);
                    break;
            }
        }

        if (! $showInactive) {
            $query->where('restricted', false);
        }

        if ($sectionId) {
            $query->where('section_id', $sectionId);
        }

        return $query->paginate($perPage);
    }

    public function getById(string $id): ?Model
    {
        return $this->model->with([
            'department:id,department_name',
            'section:id,section_name',
            'position:id,position_name',
            'designation:id,designation_name',
            'roles:id,role_name',
        ])->find($id);
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
