<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CompanySeeder::class,
            DepartmentSectionSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            LocationSeeder::class,
            BidsAwardsCommitteeSeeder::class,
            FundingSourceSeeder::class,
            ItemClassificationSeeder::class,
            MfoPapSeeder::class,
            ProcurementModeSeeder::class,
            PaperSizeSeeder::class,
            SignatorySeeder::class,
            SupplierSeeder::class,
            UacsCodeClassificationSeeder::class,
            UacsCodeSeeder::class,
            UnitIssueSeeder::class,
        ]);
    }
}
