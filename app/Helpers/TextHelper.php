<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use TCPDF;

class TextHelper
{
    public static function splitTextToLines(TCPDF $pdf, string $text, float $maxWidth): array
    {
        $words = explode(' ', $text);
        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {
            $testLine = $currentLine === '' ? $word : $currentLine . ' ' . $word;
            $testWidth = $pdf->GetStringWidth($testLine);

            if ($testWidth <= $maxWidth) {
                $currentLine = $testLine;
            } else {
                $lines[] = $currentLine;
                $currentLine = $word;
            }
        }

        if ($currentLine !== '') {
            $lines[] = $currentLine;
        }

        return $lines;
    }
}
