<?php

namespace App\Services;

use App\Interfaces\SectionRepositoryInterface;
use App\Models\Section;
use App\Repositories\LogRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SectionService
{
    public function __construct(
        protected SectionRepositoryInterface $repository,
        protected LogRepository $logRepository
    ) {}

    public function getAll(array $filters): LengthAwarePaginator
    {
        $user = auth()->user();
        $higherRoles = ['super:*', 'head:*', 'supply:*', 'budget:*', 'accountant:*', 'treasurer:*'];
        $isEndUserOnly = $user->tokenCan('user:*') && ! collect($higherRoles)->some(fn ($role) => $user->tokenCan($role));

        if ($isEndUserOnly && $user->section_id) {
            $filters['restrict_to_id'] = $user->section_id;
        }

        return $this->repository->getAll($filters);
    }

    public function getById(string $id): ?Section
    {
        return $this->repository->getById($id);
    }

    public function create(array $data): Section
    {
        $data['active'] = filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOLEAN);

        $section = $this->repository->create($data);

        $this->logRepository->create([
            'message' => 'Section created successfully.',
            'log_id' => $section->id,
            'log_module' => 'account-section',
            'data' => $section,
        ]);

        return $section;
    }

    public function update(string $id, array $data): Section
    {
        $data['active'] = filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOLEAN);

        $section = $this->repository->update($id, $data);

        $this->logRepository->create([
            'message' => 'Section updated successfully.',
            'log_id' => $section->id,
            'log_module' => 'account-section',
            'data' => $section,
        ]);

        return $section;
    }

    public function logError(string $message, \Throwable $th, array $data): void
    {
        $this->logRepository->create([
            'message' => $message,
            'details' => $th->getMessage(),
            'log_module' => 'account-section',
            'data' => $data,
        ], isError: true);
    }
}
