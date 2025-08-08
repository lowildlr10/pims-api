<?php

namespace App\Helpers;

use Carbon\Carbon;

class StatusTimestampsHelper
{
    public static function generate(string $status, mixed $current): array
    {
        switch ($current) {
            case is_string($current):
                $current = json_decode($current, true) ?? [];
                break;

            case is_object($current):
                $current = (array) $current;
                break;

            case ! is_array($current):
            case is_null($current):
                $current = [];
                break;

            default:
                $current = [];
                break;
        }

        $current[$status] = Carbon::now()->toDateTimeString();

        return $current;
    }

    public static function clear(): string
    {
        return json_encode(new \stdClass);
    }
}
