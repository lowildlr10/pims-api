<?php

namespace Database\Seeders\Production;

use App\Models\ProcurementMode;
use Illuminate\Database\Seeder;

class ProcurementModeSeeder extends Seeder
{
    private $modes = [
        'Competitive Bidding',
        'Limited Source Bidding',
        'Competitive Dialogue',
        'Unsolicited Offer with Bid Matching',
        'Direct Contracting',
        'Direct Acquisition',
        'Repeat Order',
        'Small Value Procurement',
        'Negotiated Procurement',
        'Direct Sales',
        'Direct Procurement for Science, Technology, and Innovation'
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
