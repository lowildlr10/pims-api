<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\Section;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DivisionSectionSeeder extends Seeder
{
    private $divisions = [
        'Office of the Municipal Mayor' => [
            'Municipal Mayor',
            'Executive Assistant',
            'Mayor’s Staff',
            'Legal Affairs',
        ],
        'Office of the Municipal Vice Mayor' => [
            'Vice Mayor’s Staff',
            'Legislative Affairs',
        ],
        'Municipal Planning and Development Office' => [
            'Planning Section',
            'Research and Statistics Section',
            'Development Communication Section',
        ],
        'Municipal Budget Office' => [
            'Budget Preparation Section',
            'Budget Execution Section',
        ],
        'Municipal Treasurer\'s Office' => [
            'Tax Section',
            'Revenue Collection Section',
            'Cashiering Section',
            'Real Property Tax Section',
            'Treasury Operations Section',
            'Accounting Section',
        ],

        'Municipal Social Welfare and Development Office' => [
            'Social Services Section',
            'Community Development Section',
        ],
        'Municipal Agriculture Office' => [
            'Agricultural Planning Section',
            'Farm Services Section',
            'Agri-business Section',
        ],
        'Municipal Health Office' => [
            'Health Services Section',
            'Nutrition and Sanitation Section',
        ],
        'Municipal Engineering Office' => [
            'Construction and Maintenance Section',
            'Engineering Design Section',
        ],
        'Municipal Environment and Natural Resources Office' => [
            'Environmental Protection Section',
            'Waste Management Section',
        ],
        'Municipal Civil Registrar\'s Office' => [
            'Vital Records Section',
            'Registration Section',
        ],
        'Municipal Human Resource Management Office' => [
            'Employee Services Section',
            'Personnel Records Section',
        ],
        'Local Economic Development and Investment Promotions Office' => [
            'Investment Promotion Section',
            'Business Assistance Section',
        ],
        'Municipal Information and Communications Technology Office' => [
            'IT Support Section',
            'Web Development Section',
            'Systems and Network Administration Section',
            'Data Management Section',
            'Digital Governance Section'
        ],

        'Municipal General Services Office' => [
            'Procurement Section',
            'Supply and Inventory Section',
            'Logistics and Distribution Section',
            'Designated Supply and Procurement Officer (DPSO)',
        ],

    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->divisions as $division => $sections) {
            $divData = Division::create(
                ['division_name' => $division]
            );

            foreach ($sections as $key => $sectionName) {
                Section::create([
                    'division_id' => $divData->id,
                    'section_name' => $sectionName,
                ]);
            }
        }
    }
}
