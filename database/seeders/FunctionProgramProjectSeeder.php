<?php

namespace Database\Seeders;

use App\Models\FunctionProgramProject;
use Illuminate\Database\Seeder;

class FunctionProgramProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        FunctionProgramProject::create([
            'code' => 'a.1.1.1',
            'description' => 'Test FPP 1',
        ]);
    }
}
