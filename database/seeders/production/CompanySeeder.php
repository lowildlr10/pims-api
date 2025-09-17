<?php

namespace Database\Seeders\Production;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Company::create([
            'company_name' => 'LGU - Atok',
            'municipality' => 'Atok',
            'province' => 'Benguet',
            'region' => 'CAR',
            'company_type' => 'LGU',
            'theme_colors' => [
                'primary' => [
                    '#EDF3FA',
                    '#DBE7F5',
                    '#C9DBF0',
                    '#B8CFEB',
                    '#A6C3E7',
                    '#94B7E2',
                    '#83ABDD',
                    '#719FD8',
                    '#5F93D3',
                    '#4E88CF',
                ],
                'secondary' => [
                    '#EBEDEB',
                    '#D8DCD8',
                    '#C5CAC4',
                    '#B1B9B1',
                    '#9EA89E',
                    '#8B968A',
                    '#778577',
                    '#647363',
                    '#516250',
                    '#3E513D',
                ],
                'tertiary' => [
                    '#F5F7F7',
                    '#ECF0F0',
                    '#E3E8E9',
                    '#DAE1E2',
                    '#D1DADB',
                    '#C7D2D3',
                    '#BECBCC',
                    '#B5C3C5',
                    '#ACBCBE',
                    '#A3B5B7',
                ],
            ],
        ]);
    }
}
