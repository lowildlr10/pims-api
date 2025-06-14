<?php

namespace App\Repositories;

use App\Enums\InspectionAcceptanceReportStatus;
use App\Helpers\FileHelper;
use App\Interfaces\InspectionAcceptanceReportInterface;
use App\Interfaces\SupplyInterface;
use App\Models\Company;
use App\Models\InspectionAcceptanceReport;
use App\Models\InspectionAcceptanceReportItem;
use App\Models\Location;
use App\Models\Log;
use App\Models\PurchaseRequestItem;
use App\Models\Supply;
use Exception;
use Illuminate\Support\Collection;

class SupplyRepository implements SupplyInterface
{
    public function storeUpdate(array $data, ?Supply $supply = NULL): Supply
    {
        if (!empty($supply)) {
            $supply->update($data);
        } else {
            $supply = supply::create($data);
        }

        return $supply;
    }
}
