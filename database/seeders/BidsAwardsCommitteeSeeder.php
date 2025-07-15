<?php

namespace Database\Seeders;

use App\Models\BidsAwardsCommittee;
use Illuminate\Database\Seeder;

class BidsAwardsCommitteeSeeder extends Seeder
{
    private $committees = [
        'Goods & Services',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->committees as $committee) {
            BidsAwardsCommittee::create([
                'committee_name' => $committee,
            ]);
        }
    }
}
