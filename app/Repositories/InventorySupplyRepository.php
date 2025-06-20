<?php

namespace App\Repositories;

use App\Enums\InspectionAcceptanceReportStatus;
use App\Helpers\FileHelper;
use App\Interfaces\InspectionAcceptanceReportInterface;
use App\Interfaces\InventorySupplyInterface;
use App\Models\Company;
use App\Models\InspectionAcceptanceReport;
use App\Models\InspectionAcceptanceReportItem;
use App\Models\Location;
use App\Models\Log;
use App\Models\PurchaseRequestItem;
use App\Models\InventorySupply;
use Exception;
use Illuminate\Support\Collection;

class InventorySupplyRepository implements InventorySupplyInterface
{
    public function storeUpdate(array $data, ?InventorySupply $inventorySupply = NULL): InventorySupply
    {
        if (!empty($inventorySupply)) {
            $inventorySupply->update($data);
        } else {
            $inventorySupply = InventorySupply::create($data);
        }

        return $inventorySupply;
    }
}
