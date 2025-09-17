<?php

namespace Database\Seeders\Production;

use App\Models\ItemClassification;
use Illuminate\Database\Seeder;

class ItemClassificationSeeder extends Seeder
{
    private $classifications = [
        'Inventory Held for Consumption',
        'Inventory Held for Distribution',
        'Inventory Held for Sale',
        'Inventory Held for Manufacturing',
        'Semi-Expendable Property'
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->classifications as $classification) {
            ItemClassification::create([
                'classification_name' => $classification,
            ]);
        }
    }
}
