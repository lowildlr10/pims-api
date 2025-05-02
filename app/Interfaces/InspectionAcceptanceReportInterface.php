<?php

namespace App\Interfaces;

use App\Models\InspectionAcceptanceReport;

interface InspectionAcceptanceReportInterface
{
    public function storeUpdate(array $data, ?InspectionAcceptanceReport $inspectionAcceptanceReport);
    public function print(array $pageConfig, string $prId);
}
