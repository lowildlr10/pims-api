<?php

namespace Database\Seeders\Production;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Location::create([
            'location_name' => 'Atok, Benguet',
        ]);
    }
}
