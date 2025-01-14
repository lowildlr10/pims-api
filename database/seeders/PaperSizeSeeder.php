<?php

namespace Database\Seeders;

use App\Models\PaperSize;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaperSizeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PaperSize::create([
            'paper_type' => 'Letter',
            'unit' => 'in',
            'width' => '8.5',
            'height' => '11'
        ]);
    }
}
