<?php

namespace Database\Seeders\Production;

use App\Models\TaxWithholding;
use Illuminate\Database\Seeder;

class TaxWithholdingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Tax rates per Accounting Department - LGU Atok computation reference:
     *
     * Non-VAT Goods   : 1% EWT + 3% P/Tax
     * Non-VAT Services: 2% EWT + 3% P/Tax
     * VAT Goods       : 1% W/Tax + 5% P/Tax on VAT base (OBR ÷ 1.12)
     * VAT Services    : 2% W/Tax + 5% P/Tax on VAT base (OBR ÷ 1.12)
     */
    public function run(): void
    {
        $entries = [
            [
                'name' => 'Non-VAT Goods',
                'type' => 'non_vat_goods',
                'is_vat' => false,
                'ewt_rate' => 0.0100,
                'ptax_rate' => 0.0300,
                'active' => true,
            ],
            [
                'name' => 'Non-VAT Services',
                'type' => 'non_vat_services',
                'is_vat' => false,
                'ewt_rate' => 0.0200,
                'ptax_rate' => 0.0300,
                'active' => true,
            ],
            [
                'name' => 'VAT Goods',
                'type' => 'vat_goods',
                'is_vat' => true,
                'ewt_rate' => 0.0100,
                'ptax_rate' => 0.0500,
                'active' => true,
            ],
            [
                'name' => 'VAT Services',
                'type' => 'vat_services',
                'is_vat' => true,
                'ewt_rate' => 0.0200,
                'ptax_rate' => 0.0500,
                'active' => true,
            ],
        ];

        foreach ($entries as $entry) {
            TaxWithholding::firstOrCreate(['type' => $entry['type']], $entry);
        }
    }
}
