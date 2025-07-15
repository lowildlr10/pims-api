<?php

namespace Database\Seeders;

use App\Models\FundingSource;
use App\Models\Location;
use Illuminate\Database\Seeder;

class FundingSourceSeeder extends Seeder
{
    private $projects = [
        'Project Test 1',
        'Project Test 2',
        'Project Test 3',
        'Project Test 4',
        'Project Test 5',
        'Project Test 6',
        'Funding Source 1',
        'Funding Source 2',
        'Funding Source 3',
        'Funding Source 4',
        'Funding Source 5',
        'Funding Source 6',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $location = Location::where('location_name', 'Atok, Benguet')
            ->first();

        foreach ($this->projects as $title) {
            FundingSource::create([
                'title' => $title,
                'location_id' => $location->id,
                'total_cost' => 1000000,
            ]);
        }
    }
}
