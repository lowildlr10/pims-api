<?php

namespace App\Helpers;

use Carbon\Carbon;

class StatusTimestampsHelper {
    public static function generate(string $status, string $current): string
    {
        $statusTimestamps = json_decode($current, true);
        $statusTimestamps[$status] = Carbon::now();
        return json_encode($statusTimestamps);
    }
}
