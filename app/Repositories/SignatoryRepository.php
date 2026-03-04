<?php

namespace App\Repositories;

use App\Interfaces\SignatoryRepositoryInterface;
use App\Models\Designation;
use App\Models\Signatory;
use App\Models\SignatoryDetail;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class SignatoryRepository implements SignatoryRepositoryInterface
{
    public function __construct(protected Signatory $model) {}

    public function getModel(): string
    {
        return Signatory::class;
    }

    public function getAll(array $filters): LengthAwarePaginator
    {
        $query = $this->model->query()->with([
            'details' => function ($query) {
                $query->orderBy('document');
            },
            'user:id,firstname,middlename,lastname',
        ]);

        $search = $filters['search'] ?? '';
        $perPage = $filters['per_page'] ?? 50;
        $showAll = $filters['show_all'] ?? false;
        $showInactive = $filters['show_inactive'] ?? false;
        $paginated = $filters['paginated'] ?? true;
        $columnSort = $filters['column_sort'] ?? 'fullname';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('CAST(id AS TEXT) = ?', [$search])
                    ->orWhereRelation('user', 'firstname', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('user', 'middlename', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('user', 'lastname', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('details', 'position', 'ILIKE', "%{$search}%");
            });
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            if ($columnSort === 'fullname') {
                $query->orderBy(
                    User::select('firstname')->whereColumn('users.id', 'signatories.user_id')
                );
            } elseif ($columnSort) {
                $query->orderBy($columnSort, $sortDirection);
            }
        }

        if (! $paginated) {
            if (! $showInactive) {
                $query->where('active', true);
            }
            $items = $showAll ? $query->get() : $query->limit($perPage)->get();

            return new \Illuminate\Pagination\LengthAwarePaginator($items, $items->count(), $perPage, 1);
        }

        return $query->paginate($perPage);
    }

    public function getByDocumentAndType(string $document, string $signatoryType, array $filters): Collection
    {
        $perPage = $filters['per_page'] ?? 50;
        $showAll = $filters['show_all'] ?? false;
        $showInactive = $filters['show_inactive'] ?? false;

        $query = SignatoryDetail::with('signatory')
            ->where('document', $document)
            ->where('signatory_type', $signatoryType);

        if (! $showInactive) {
            $query->whereRelation('signatory', 'active', true);
        }

        return $showAll ? $query->get() : $query->limit($perPage)->get();
    }

    public function getById(string $id): ?Model
    {
        return $this->model->with([
            'details' => function ($query) {
                $query->orderBy('document');
            },
            'user:id,firstname,middlename,lastname',
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

    public function deleteDetails(string $signatoryId): void
    {
        SignatoryDetail::where('signatory_id', $signatoryId)->delete();
    }

    public function createDetail(array $data): void
    {
        if (! empty($data['position'])) {
            Designation::updateOrCreate(
                ['designation_name' => $data['position']],
                ['designation_name' => $data['position']]
            );

            SignatoryDetail::create([
                'signatory_id' => $data['signatory_id'],
                'document' => $data['document'],
                'signatory_type' => $data['signatory_type'],
                'position' => $data['position'],
            ]);
        }
    }
}
