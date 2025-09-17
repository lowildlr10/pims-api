<?php

namespace Database\Seeders\Production;

use App\Models\PaperSize;
use Illuminate\Database\Seeder;

class PaperSizeSeeder extends Seeder
{
    private $papers = [
        [
            'paper_type' => 'A4',
            'unit' => 'mm',
            'width' => 210,
            'height' => 297,
        ],
        [
            'paper_type' => 'Letter',
            'unit' => 'mm',
            'width' => 216,
            'height' => 279,
        ],
        [
            'paper_type' => 'Long',
            'unit' => 'mm',
            'width' => 216,
            'height' => 330,
        ],
        [
            'paper_type' => 'Legal',
            'unit' => 'mm',
            'width' => 216,
            'height' => 356,
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->papers as $paper) {
            PaperSize::create([
                'paper_type' => $paper['paper_type'],
                'unit' => $paper['unit'],
                'width' => $paper['width'],
                'height' => $paper['height'],
            ]);
        }
    }
}
