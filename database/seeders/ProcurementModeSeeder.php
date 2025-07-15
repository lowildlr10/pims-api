<?php

namespace Database\Seeders;

use App\Models\ProcurementMode;
use Illuminate\Database\Seeder;

class ProcurementModeSeeder extends Seeder
{
    private $modes = [
        'Direct Contracting',
        'Emergency Procurement',
        'Limited Source Bidding',
        'Negotiated Procurement',
        'Public Bidding',
        'Re-Order',
        'Shopping',
        'Small Value Procurement',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->modes as $mode) {
            ProcurementMode::create([
                'mode_name' => $mode,
            ]);
        }
    }
}
