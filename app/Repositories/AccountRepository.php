<?php

namespace App\Repositories;

use App\Interfaces\AccountRepositoryInterface;
use App\Models\Account;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class AccountRepository implements AccountRepositoryInterface
{
    public function __construct(protected Account $model) {}

    public function getModel(): string
    {
        return Account::class;
    }

    public function getAll(array $filters): LengthAwarePaginator
    {
        $query = $this->model->query()->with('classification');
        $search = $filters['search'] ?? '';
        $perPage = $filters['per_page'] ?? 50;
        $showAll = $filters['show_all'] ?? false;
        $showInactive = $filters['show_inactive'] ?? false;
        $paginated = $filters['paginated'] ?? true;
        $columnSort = $filters['column_sort'] ?? 'code';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('CAST(id AS TEXT) = ?', [$search])
                    ->orWhere('account_title', 'ILIKE', "%{$search}%")
                    ->orWhere('code', 'ILIKE', "%{$search}%")
                    ->orWhere('description', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('classification', 'classification_name', 'ILIKE', "%{$search}%");
            });
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            if ($columnSort === 'code_formatted') {
                $columnSort = 'code';
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
        return $this->model->with('classification')->find($id);
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
