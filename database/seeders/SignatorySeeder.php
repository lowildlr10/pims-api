<?php

namespace Database\Seeders;

use App\Models\Signatory;
use App\Models\SignatoryDetail;
use App\Models\User;
use Illuminate\Database\Seeder;

class SignatorySeeder extends Seeder
{
    private $signatories = [
        [
            'employee_id' => '1112',
            'details' => [
                [
                    'document' => 'pr',
                    'signatory_type' => 'approved_by',
                    'position' => 'Municipal Mayor',
                ],
                [
                    'document' => 'rfq',
                    'signatory_type' => 'approval',
                    'position' => 'Municipal Mayor',
                ],
            ],
        ],
        [
            'employee_id' => '1114',
            'details' => [
                [
                    'document' => 'pr',
                    'signatory_type' => 'cash_availability',
                    'position' => 'Municipal Treasurer',
                ],
            ],
        ],
        [
            'employee_id' => '1113',
            'details' => [
                [
                    'document' => 'rfq',
                    'signatory_type' => 'approval',
                    'position' => 'BAC Chairman',
                ],
            ],
        ],
        [
            'employee_id' => '1115',
            'details' => [
                [
                    'document' => 'pr',
                    'signatory_type' => 'cash_availability',
                    'position' => 'Municipal Treasurer',
                ],
                [
                    'document' => 'rfq',
                    'signatory_type' => 'approval',
                    'position' => 'BAC Chairman',
                ],
            ],
        ],
        [
            'employee_id' => '1116',
            'details' => [
                [
                    'document' => 'pr',
                    'signatory_type' => 'cash_availability',
                    'position' => 'Municipal Treasurer',
                ],
            ],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->signatories as $signatory) {
            $user = User::where('employee_id', $signatory['employee_id'])
                ->first();

            $sigData = Signatory::create([
                'user_id' => $user->id,
            ]);

            foreach ($signatory['details'] as $detail) {
                SignatoryDetail::create([
                    'signatory_id' => $sigData->id,
                    'document' => $detail['document'],
                    'signatory_type' => $detail['signatory_type'],
                    'position' => $detail['position'],
                ]);
            }
        }
    }
}
