<?php

namespace Database\Seeders;

use App\Models\UnitIssue;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UnitIssueSeeder extends Seeder
{
    private $units = [
        'Bag',
        'Bar',
        'bd/ft',
        'Book',
        'booklet',
        'Bottle',
        'Box',
        'Bundle',
        'Can',
        'Cartoon',
        'cash',
        'color',
        'container',
        'Cu.m.',
        'Cylinder',
        'Day',
        'Dozen',
        'ft',
        'g',
        'gallon',
        'hours',
        'Instance',
        'J.O.',
        'Kg',
        'Kilo'
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->units as $unit) {
            UnitIssue::create([
                'unit_name' => $unit
            ]);
        }
    }
}
