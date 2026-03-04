<?php

namespace App\Repositories;

use App\Interfaces\PaperSizeRepositoryInterface;
use App\Models\PaperSize;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class PaperSizeRepository implements PaperSizeRepositoryInterface
{
    public function __construct(
        protected PaperSize $model
    ) {}

    public function getModel(): string
    {
        return PaperSize::class;
    }

    public function getAll(array $filters): LengthAwarePaginator
    {
        $query = $this->model->query();

        $search = $filters['search'] ?? '';
        $perPage = $filters['per_page'] ?? 50;
        $showAll = $filters['show_all'] ?? false;
        $paginated = $filters['paginated'] ?? true;
        $columnSort = $filters['column_sort'] ?? 'paper_type';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('CAST(id AS TEXT) = ?', [$search])
                    ->orWhere('paper_type', 'ILIKE', "%{$search}%")
                    ->orWhere('unit', 'ILIKE', "%{$search}%")
                    ->orWhere('width', 'ILIKE', "%{$search}%")
                    ->orWhere('height', 'ILIKE', "%{$search}%");
            });
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
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
