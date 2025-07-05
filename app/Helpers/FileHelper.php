<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

class FileHelper {
    public static function getPublicPath(string $path, string $disk = 'public'): string | NULL
    {
        $appUrl = env('APP_URL') ?? 'http://localhost';

        if (!empty($path)) {
            $publicPath = Storage::disk($disk)->path($path);
            return $publicPath;
        }

        return null;
    }
}
