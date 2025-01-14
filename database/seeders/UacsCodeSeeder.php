<?php

namespace Database\Seeders;

use App\Models\UacsCode;
use App\Models\UacsCodeClassification;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UacsCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $classification = UacsCodeClassification::first();

        UacsCode::create([
            'classification_id' => $classification->id,
            'account_title' => 'Awards/Rewards Expenses',
            'code' => '5020601000'
        ]);
    }
}
