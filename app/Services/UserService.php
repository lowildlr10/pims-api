<?php

namespace App\Services;

use App\Interfaces\UserRepositoryInterface;
use App\Models\Designation;
use App\Models\Position;
use App\Models\Section;
use App\Models\User;
use App\Repositories\LogRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class UserService
{
    public function __construct(
        protected UserRepositoryInterface $repository,
        protected LogRepository $logRepository
    ) {}

    public function getAll(array $filters): LengthAwarePaginator
    {
        $filters['show_inactive'] = filter_var($filters['show_inactive'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return $this->repository->getAll($filters);
    }

    public function getById(string $id): ?User
    {
        return $this->repository->getById($id);
    }

    public function create(array $data): User
    {
        $position = Position::updateOrCreate([
            'position_name' => $data['position'],
        ], [
            'position_name' => $data['position'],
        ]);

        $designation = Designation::updateOrCreate([
            'designation_name' => $data['designation'],
        ], [
            'designation_name' => $data['designation'],
        ]);

        $section = Section::find($data['section_id']);

        $data['position_id'] = $position->id;
        $data['designation_id'] = $designation->id;
        $data['department_id'] = $section?->department_id ?? $data['department_id'];
        $data['avatar'] = null;
        $data['signature'] = null;
        $data['password'] = bcrypt($data['password']);
        $data['restricted'] = filter_var($data['restricted'], FILTER_VALIDATE_BOOLEAN);

        $user = $this->repository->create($data);

        $roles = $data['roles'] ?? [];
        $user->roles()->sync($roles);

        $this->logRepository->create([
            'message' => 'User registered successfully.',
            'log_id' => $user->id,
            'log_module' => 'account-user',
            'data' => $user,
        ]);

        return $user;
    }

    public function update(string $id, array $data): User
    {
        $user = $this->repository->getById($id);
        $updateType = $data['update_type'] ?? 'account-management';

        if ($updateType === 'account-management' || $updateType === 'profile') {
            $position = Position::updateOrCreate([
                'position_name' => $data['position'],
            ], [
                'position_name' => $data['position'],
            ]);

            $designation = Designation::updateOrCreate([
                'designation_name' => $data['designation'],
            ], [
                'designation_name' => $data['designation'],
            ]);

            $data['position_id'] = $position->id;
            $data['designation_id'] = $designation->id;
        }

        if ($updateType === 'profile') {
            $password = $data['password'] ?? '';
            unset($data['password']);

            $updateData = array_merge($data, [
                'position_id' => $data['position_id'],
                'designation_id' => $data['designation_id'],
            ]);

            if (! empty(trim($password))) {
                $updateData['password'] = bcrypt($password);
            }
        } elseif ($updateType === 'allow_signature') {
            $updateData = [
                'allow_signature' => filter_var($data['allow_signature'], FILTER_VALIDATE_BOOLEAN),
            ];
        } else {
            $password = $data['password'] ?? '';
            unset($data['password']);

            $data['restricted'] = filter_var($data['restricted'], FILTER_VALIDATE_BOOLEAN);

            $updateData = array_merge($data, [
                'position_id' => $data['position_id'],
                'designation_id' => $data['designation_id'],
                'restricted' => $data['restricted'],
            ]);

            if (! empty(trim($password))) {
                $updateData['password'] = bcrypt($password);
            }

            $user->tokens()->delete();
        }

        if ($updateType === 'account-management') {
            $roles = $data['roles'] ?? [];
            $user->roles()->sync($roles);
        }

        $user = $this->repository->update($id, $updateData);

        $successMessage = match ($updateType) {
            'allow_signature' => 'Signature allowed successfully.',
            default => 'User updated successfully.',
        };

        $this->logRepository->create([
            'message' => $successMessage,
            'log_id' => $user->id,
            'log_module' => 'account-user',
            'data' => $user,
        ]);

        return $user;
    }

    public function checkDocumentAccess(string $document): bool
    {
        $user = Auth::user();

        return match ($document) {
            'pr' => in_array(true, [
                $user->tokenCan('super:*'),
                $user->tokenCan('supply:*'),
            ]),
            default => true,
        };
    }

    public function logError(string $message, \Throwable $th, array $data): void
    {
        $this->logRepository->create([
            'message' => $message,
            'details' => $th->getMessage(),
            'log_module' => 'account-user',
            'data' => $data,
        ], isError: true);
    }
}
