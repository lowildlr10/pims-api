<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Supplier::create([
            'supplier_name' => 'Test Supplier 1',
            'address' => '123 Atok, Benguet',
            'tin_no' => '123123123',
            'phone' => '+639123456789',
            'contact_person' => 'King Kong'
        ]);
    }
}
