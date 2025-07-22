<?php

namespace Database\Seeders;

use App\Models\PurchaseRequest;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PurchaseRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PurchaseRequest::factory()
            ->count(1000)
            ->create();
    }
}
