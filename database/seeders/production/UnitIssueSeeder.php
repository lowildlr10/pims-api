<?php

namespace Database\Seeders\Production;

use App\Models\UnitIssue;
use Illuminate\Database\Seeder;

class UnitIssueSeeder extends Seeder
{
    private $units = [
        'piece',
        'box',
        'bottle',
        'can',
        'carton',
        'pack',
        'set',
        'lot',
        'unit',
        'ream',
        'tube',
        'roll',
        'jar',
        'bag',
        'bundle',
        'pad',
        'dozen',
        'pair',
        'meter',
        'kilogram',
        'gram',
        'liter',
        'milliliter',
        'gallon',
        'hour',
        'day',
        'month',
        'year',
        'service',
        'project',
        'job',
        'container',
        'cartridge',
        'disk',
        'sheet',
        'block',
        'strip',
        'bar',
        'kit',
        'tray',
        'each'
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->units as $unit) {
            UnitIssue::create([
                'unit_name' => $unit,
            ]);
        }
    }
}
