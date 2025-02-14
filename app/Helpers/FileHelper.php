<?php

namespace App\Helpers;

class FileHelper {
    public static function getPublicPath(string $path): string | NULL
    {
        $appUrl = env('APP_URL') ?? 'http://localhost';

        if (!empty($path)) {
            $publicPath = str_replace("{$appUrl}/", '', $path);
            return $publicPath;
        }

        return null;
    }
}
