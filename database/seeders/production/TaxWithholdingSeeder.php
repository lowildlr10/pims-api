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
                'is_vat' => false,
                'ewt_rate' => 0.0100,
                'ptax_rate' => 0.0300,
                'active' => true,
            ],
            [
                'name' => 'Non-VAT Services',
                'is_vat' => false,
                'ewt_rate' => 0.0200,
                'ptax_rate' => 0.0300,
                'active' => true,
            ],
            [
                'name' => 'VAT Goods',
                'is_vat' => true,
                'ewt_rate' => 0.0100,
                'ptax_rate' => 0.0500,
                'active' => true,
            ],
            [
                'name' => 'VAT Services',
                'is_vat' => true,
                'ewt_rate' => 0.0200,
                'ptax_rate' => 0.0500,
                'active' => true,
            ],
        ];

        foreach ($entries as $entry) {
            TaxWithholding::create($entry);
        }
    }
}
