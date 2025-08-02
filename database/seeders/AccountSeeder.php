<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountClassification;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $classification = AccountClassification::first();

        Account::create([
            'classification_id' => $classification->id,
            'account_title' => 'Awards/Rewards Expenses',
            'code' => '5020601000',
        ]);
    }
}
