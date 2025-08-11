<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DateTime;
use DateTimeZone;

class GeoController extends Controller
{
    public function info(Request $request)
    {
        // Force UK for now
        $countryName = 'United Kingdom';
        $countryCode = 'GB';
        $timezoneName = 'Europe/London';

        // Convert to "GMT (UTC±HH:MM)"
        $dateTime = new DateTime('now', new DateTimeZone($timezoneName));
        $offsetInHours = $dateTime->format('P'); // e.g. "+00:00"
        $formattedTimezone = "GMT (UTC{$offsetInHours})";

        return response()->json([
            'ip'              => 'STATIC-UK',
            'country_name'    => $countryName,
            'country_code'    => $countryCode,
            'timezone'        => $formattedTimezone,
            'timezone_name'   => $timezoneName,
        ]);
    }
}
