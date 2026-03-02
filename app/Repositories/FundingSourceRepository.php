<?php

namespace App\Repositories;

use App\Interfaces\FundingSourceRepositoryInterface;
use App\Models\FundingSource;
use App\Models\Location;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class FundingSourceRepository implements FundingSourceRepositoryInterface
{
    public function __construct(
        protected FundingSource $model
    ) {}

    public function getModel(): string
    {
        return FundingSource::class;
    }

    public function getAll(array $filters): LengthAwarePaginator
    {
        $query = $this->model->query()->with('location');

        $search = $filters['search'] ?? '';
        $perPage = $filters['per_page'] ?? 50;
        $showAll = $filters['show_all'] ?? false;
        $showInactive = $filters['show_inactive'] ?? false;
        $paginated = $filters['paginated'] ?? true;
        $columnSort = $filters['column_sort'] ?? 'title';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('CAST(id AS TEXT) = ?', [$search])
                    ->orWhere('title', 'ILIKE', "%{$search}%")
                    ->orWhere('total_cost', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('location', 'location_name', 'ILIKE', "%{$search}%");
            });
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            switch ($columnSort) {
                case 'title_formatted':
                    $columnSort = 'title';
                    break;
                case 'location_name':
                    $columnSort = 'location_id';
                    break;
                case 'total_cost_formatted':
                    $columnSort = 'total_cost';
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
        return $this->model->with('location')->find($id);
    }

    public function create(array $data): Model
    {
        $location = Location::updateOrCreate(
            ['location_name' => $data['location']],
            ['location_name' => $data['location']]
        );

        return $this->model->create([
            'title' => $data['title'],
            'location_id' => $location->id,
            'total_cost' => $data['total_cost'],
            'active' => $data['active'] ?? true,
        ]);
    }

    public function update(string $id, array $data): Model
    {
        $model = $this->model->findOrFail($id);

        $location = Location::updateOrCreate(
            ['location_name' => $data['location']],
            ['location_name' => $data['location']]
        );

        $model->update([
            'title' => $data['title'],
            'location_id' => $location->id,
            'total_cost' => $data['total_cost'],
            'active' => $data['active'] ?? true,
        ]);

        return $model;
    }

    public function delete(string $id): bool
    {
        return $this->model->findOrFail($id)->delete();
    }
}
