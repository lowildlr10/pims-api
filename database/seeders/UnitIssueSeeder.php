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
        'Kilo',
        'Litre',
        'Meter',
        'ml',
        'M2',
        'M3',
        'Pair',
        'Packet',
        'Piece',
        'Pint',
        'Roll',
        'Set',
        'Sheet',
        'Sack',
        'Ton',
        'Yard',
        'lb',
        'Oz',
        'Carton',
        'Crate',
        'Pack',
        'Sq.ft',
        'Sq.in',
        'Box of 12',
        'Box of 24',
        'Dozen Box',
        'Lbs',
        'Unit',
        'gms',
        'Each',
        'Canister',
        'Liter',
        'Kg/Box',
        'g/Box',
        'Bag of 10',
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
