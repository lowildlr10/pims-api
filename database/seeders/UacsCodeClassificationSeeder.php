<?php

namespace Database\Seeders;

use App\Models\UacsCodeClassification;
use Illuminate\Database\Seeder;

class UacsCodeClassificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        UacsCodeClassification::create([
            'classification_name' => 'Awards/Rewards, Prizes and Indemnities',
        ]);
    }
}
