<?php

namespace Database\Seeders;

use App\Models\Section;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SectionSeeder extends Seeder
{
    private $divisions = [
        'Office of the Municipal Mayor',
        'Office of the Municipal Vice Mayor',
        'Office of the Sangguniang Bayan',
        'Office of the Sangguniang Bayan Secretary',
        'Office of the Municipal Administrator',
        'Office of the Municipal Treasurer',
        'Office of the Municipal Accountant',
        'Office of the Municipal Budget Officer',
        'Office of the Municipal Planning and Development Coordinator',
        'Office of the Municipal Engineer',
        'Office of the Municipal Assessor',
        'Office of the Municipal Civil Registrar',
        'Office of the Local Disaster Risk Reduction Management Officer',
        'Office of the Municipal Environment and Natural Resources Officer',
        'Office of the Municipal Agriculturist',
        'Office of the Municipal Social Welfare and Development Officer',
        'Office of the Municipal General Services Officer',
        'Office of the Municipal Internal Auditor',
        'Office of the Municipal Health Officer',
        'Office of the Waterworks System'
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->divisions as $key => $division) {
            $divData = DB::table('divisions')
                ->where('division_name', $division)
                ->first();
            Section::create([
                'division_id' => $divData->id,
                'section_name' => "Section Test {$key}"
            ]);
        }
    }
}
