<?php

namespace Database\Seeders;

use App\Models\AccountClassification;
use Illuminate\Database\Seeder;

class AccountClassificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AccountClassification::create([
            'classification_name' => 'Awards/Rewards, Prizes and Indemnities',
        ]);
    }
}
