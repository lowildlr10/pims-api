<?php

namespace App\Helpers;

use Carbon\Carbon;

class StatusTimestampsHelper
{
    public static function generate(string $status, mixed $current): array
    {
        $current = match (true) {
            is_string($current) => json_decode($current, true),
            is_object($current) => (array) $current,
            !is_array($current) || is_null($current) => [],
            default => [],
        };

        $current[$status] = Carbon::now()->toDateTimeString();

        return $current;
    }

    public static function clear(): string
    {
        return json_encode(new \stdClass);
    }
}
