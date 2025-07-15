<?php

namespace Database\Seeders;

use App\Models\ItemClassification;
use Illuminate\Database\Seeder;

class ItemClassificationSeeder extends Seeder
{
    private $classifications = [
        'IT Equipment',
        'Office Supplies',
        'Furniture',
        'Maintenance',
        'Personal Protective Equipment',
        'Consumables',
        'Software',
        'Tools and Equipment',
        'Electrical Equipment',
        'Vehicles',
        'Raw Materials',
        'Spare Parts',
        'Security Equipment',
        'Medical Supplies',
        'Cleaning Supplies',
        'Packaging Materials',
        'Books and Publications',
        'Promotional Items',
        'Food and Beverages',
        'Health and Safety Supplies',
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
