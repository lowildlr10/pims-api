<?php

namespace App\Repositories;

use App\Interfaces\CompanyRepositoryInterface;
use App\Models\Company;
use Illuminate\Database\Eloquent\Model;

class CompanyRepository implements CompanyRepositoryInterface
{
    public function __construct(
        protected Company $model
    ) {}

    public function getModel(): string
    {
        return Company::class;
    }

    public function get(): ?Model
    {
        return $this->model->first();
    }

    public function update(array $data): Model
    {
        $company = $this->model->first();

        if (! $company) {
            throw new \RuntimeException('Company data does not exist in the database.');
        }

        $company->update($data);

        return $company->fresh();
    }
}
