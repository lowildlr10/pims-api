<?php

namespace App\Interfaces;

use App\Models\InspectionAcceptanceReport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface InspectionAcceptanceReportInterface
{
    public function getAll(array $filters, ?string $userId = null): LengthAwarePaginator;

    public function getById(string $id): ?InspectionAcceptanceReport;

    public function storeUpdate(array $data, ?InspectionAcceptanceReport $inspectionAcceptanceReport = null): InspectionAcceptanceReport;

    public function print(array $pageConfig, string $prId);
}
