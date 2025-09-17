<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\Production as ProductionSeeders;

class ProductionSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ProductionSeeders\RoleSeeder::class,
            ProductionSeeders\UserSeeder::class,
            ProductionSeeders\LocationSeeder::class,
            ProductionSeeders\CompanySeeder::class,
            ProductionSeeders\BidsAwardsCommitteeSeeder::class,
            ProductionSeeders\ItemClassificationSeeder::class,
            ProductionSeeders\PaperSizeSeeder::class,
            ProductionSeeders\ProcurementModeSeeder::class,
            ProductionSeeders\UnitIssueSeeder::class,
        ]);
    }
}
