<?php

namespace Database\Seeders;

use App\Models\MfoPap;
use Illuminate\Database\Seeder;

class MfoPapSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MfoPap::create([
            'code' => 'a.1.1.1',
            'description' => 'Test MFO/PAP 1',
        ]);
    }
}
