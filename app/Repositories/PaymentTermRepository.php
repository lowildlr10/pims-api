<?php

namespace App\Repositories;

use App\Interfaces\PaymentTermRepositoryInterface;
use App\Models\PaymentTerm;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class PaymentTermRepository implements PaymentTermRepositoryInterface
{
    public function __construct(
        protected PaymentTerm $model
    ) {}

    public function getModel(): string
    {
        return PaymentTerm::class;
    }

    public function getAll(array $filters): LengthAwarePaginator
    {
        $query = $this->model->query();

        $search = $filters['search'] ?? '';
        $perPage = $filters['per_page'] ?? 50;
        $showAll = $filters['show_all'] ?? false;
        $paginated = $filters['paginated'] ?? true;
        $columnSort = $filters['column_sort'] ?? 'term_name';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        if (! empty($search)) {
            $query->where('term_name', 'ILIKE', "%{$search}%");
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
}
