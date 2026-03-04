<?php

namespace App\Services;

use App\Interfaces\CompanyRepositoryInterface;
use App\Models\Company;
use App\Repositories\LogRepository;

class CompanyService
{
    public function __construct(
        protected CompanyRepositoryInterface $repository,
        protected LogRepository $logRepository
    ) {}

    public function get(): ?Company
    {
        return $this->repository->get();
    }

    public function update(array $data): Company
    {
        $themeColors = json_decode($data['theme_colors'], true);
        $data['theme_colors'] = $themeColors;

        $company = $this->repository->update($data);

        $this->logRepository->create([
            'message' => 'Company profile updated successfully.',
            'log_id' => $company->id,
            'log_module' => 'company',
            'data' => $company,
        ]);

        return $company;
    }

    public function logError(string $message, \Throwable $th, array $data): void
    {
        $this->logRepository->create([
            'message' => $message,
            'details' => $th->getMessage(),
            'log_module' => 'company',
            'data' => $data,
        ], isError: true);
    }
}
