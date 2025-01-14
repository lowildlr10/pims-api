<?php

namespace Database\Seeders;

use App\Models\Signatory;
use App\Models\SignatoryDetail;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SignatorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();

        $signatory = Signatory::create([
            'user_id' => $user->id
        ]);

        SignatoryDetail::create([
            'signatory_id' => $signatory->id,
            'document' => 'pr',
            'signatory_type' => 'cash_availability',
            'position' => 'Test Position'
        ]);

        SignatoryDetail::create([
            'signatory_id' => $signatory->id,
            'document' => 'pr',
            'signatory_type' => 'approved_by',
            'position' => 'Test Position'
        ]);
    }
}
