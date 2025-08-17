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
            DepartmentSeeder::class,
            PositionSeeder::class,
            DesignationSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            SignatorySeeder::class,
            LocationSeeder::class,
            BidsAwardsCommitteeSeeder::class,
            FundingSourceSeeder::class,
            ItemClassificationSeeder::class,
            FunctionProgramProjectSeeder::class,
            ProcurementModeSeeder::class,
            PaperSizeSeeder::class,
            SupplierSeeder::class,
            AccountClassificationSeeder::class,
            AccountSeeder::class,
            UnitIssueSeeder::class,
            PurchaseRequestSeeder::class,
        ]);
    }
}
