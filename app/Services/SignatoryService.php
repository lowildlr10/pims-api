<?php

namespace App\Services;

use App\Interfaces\SignatoryRepositoryInterface;
use App\Models\Signatory;
use App\Repositories\LogRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class SignatoryService
{
    public function __construct(
        protected SignatoryRepositoryInterface $repository,
        protected LogRepository $logRepository
    ) {}

    public function getAll(array $filters): LengthAwarePaginator
    {
        return $this->repository->getAll($filters);
    }

    public function getByDocumentAndType(string $document, string $signatoryType, array $filters): Collection
    {
        $signatories = $this->repository->getByDocumentAndType($document, $signatoryType, $filters);

        foreach ($signatories as $signatory) {
            $user = \App\Models\User::find($signatory->signatory->user_id);
            $signatory->fullname_designation = "{$user->fullname} ({$signatory->position})";
        }

        return $signatories;
    }

    public function getById(string $id): ?Signatory
    {
        return $this->repository->getById($id);
    }

    public function create(array $data): Signatory
    {
        $data['active'] = filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $details = json_decode($data['details'] ?? '[]');

        $signatory = $this->repository->create([
            'user_id' => $data['user_id'],
            'active' => $data['active'],
        ]);

        foreach ($details as $detail) {
            $this->repository->createDetail([
                'signatory_id' => $signatory->id,
                'document' => $detail->document,
                'signatory_type' => $detail->signatory_type,
                'position' => $detail->position,
            ]);
        }

        $this->logRepository->create([
            'message' => 'Signatory created successfully.',
            'log_id' => $signatory->id,
            'log_module' => 'lib-signatory',
            'data' => $signatory,
        ]);

        return $signatory;
    }

    public function update(string $id, array $data): Signatory
    {
        $data['active'] = filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $details = json_decode($data['details'] ?? '[]');

        $signatory = $this->repository->update($id, [
            'active' => $data['active'],
        ]);

        $this->repository->deleteDetails($signatory->id);

        foreach ($details as $detail) {
            $this->repository->createDetail([
                'signatory_id' => $signatory->id,
                'document' => $detail->document,
                'signatory_type' => $detail->signatory_type,
                'position' => $detail->position,
            ]);
        }

        $this->logRepository->create([
            'message' => 'Signatory updated successfully.',
            'log_id' => $signatory->id,
            'log_module' => 'lib-signatory',
            'data' => $signatory,
        ]);

        return $signatory;
    }

    public function logError(string $message, \Throwable $th, array $data): void
    {
        $this->logRepository->create([
            'message' => $message,
            'details' => $th->getMessage(),
            'log_module' => 'lib-signatory',
            'data' => $data,
        ], isError: true);
    }
}
