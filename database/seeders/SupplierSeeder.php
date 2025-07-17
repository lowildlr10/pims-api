<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    private $suppliers = [
        [
            'supplier_name' => 'Supplier 1',
            'address' => '1 Atok, Benguet',
            'tin_no' => '111-111-111',
            'phone' => '09123456789',
            'contact_person' => 'Test Name',
        ],
        [
            'supplier_name' => 'Supplier 2',
            'address' => '2 Atok, Benguet',
            'tin_no' => '111-111-112',
            'phone' => '09123456789',
            'contact_person' => 'Test Name',
        ],
        [
            'supplier_name' => 'Supplier 3',
            'address' => '3 Atok, Benguet',
            'tin_no' => '111-111-113',
            'phone' => '09123456789',
            'contact_person' => 'Test Name',
        ],
        [
            'supplier_name' => 'Supplier 4',
            'address' => '4 Atok, Benguet',
            'tin_no' => '111-111-114',
            'phone' => '09123456789',
            'contact_person' => 'Test Name',
        ],
        [
            'supplier_name' => 'Supplier 5',
            'address' => '5 Atok, Benguet',
            'tin_no' => '111-111-115',
            'phone' => '09123456789',
            'contact_person' => 'Test Name',
        ],
        [
            'supplier_name' => 'Supplier 6',
            'address' => '6 Atok, Benguet',
            'tin_no' => '111-111-116',
            'phone' => '09123456789',
            'contact_person' => 'Test Name',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->suppliers as $supplier) {
            Supplier::create([
                'supplier_name' => $supplier['supplier_name'],
                'address' => $supplier['address'],
                'tin_no' => $supplier['tin_no'],
                'phone' => $supplier['phone'],
                'contact_person' => $supplier['contact_person'],
            ]);
        }
    }
}
