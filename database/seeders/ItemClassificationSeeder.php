<?php

namespace Database\Seeders;

use App\Models\ItemClassification;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ItemClassificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ItemClassification::create([
            'classification_name' => 'IT Equipment'
        ]);
    }
}
