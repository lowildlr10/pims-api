<?php

namespace Database\Seeders\Production;

use App\Models\ProcurementMode;
use Illuminate\Database\Seeder;

class ProcurementModeSeeder extends Seeder
{
    private $modes = [
        'Electronic Procurement (e-Procurement)',
        'Framework Agreement',
        'Community Participation',
        'Agency-to-Agency Procurement',
        'Negotiated Procurement (Expanded)',
        'Competitive Dialogue',
        'Two-Stage Bidding',
        'Most Economically Advantageous Responsive Bid (MEARB)',
        'Public-Private Partnership (PPP) Procurement',
        'Procurement through Leasing',
        'Procurement by Consignment'
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
