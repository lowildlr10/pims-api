<?php

namespace Database\Seeders;

use App\Models\FundingSource;
use App\Models\Location;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FundingSourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $location = Location::where('location_name', 'Atok, Benguet')
            ->first();

        FundingSource::create([
            'title' => 'Project Test 1',
            'location_id' => $location->id,
            'total_cost' => 1000000
        ]);
    }
}
