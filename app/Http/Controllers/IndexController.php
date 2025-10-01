<?php
// app/Http/Controllers/IndexController.php
namespace App\Http\Controllers;

use App\Models\Country;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Stevebauman\Location\Facades\Location;
use DateTime;
use DateTimeZone;

class IndexController extends Controller
{
    public function countries()
    {
        return Country::orderBy('name')
            ->get(['iso2 as code', 'name']);
    }

    public function countries_pluck()
    {
        return Country::orderBy('name')
            ->get(['name', 'iso2'])
            ->map(fn($c) => [
                'label' => $c->name,
                'value' => strtoupper($c->iso2)
            ])
            ->values();
    }

    // public function countryDetails(Request $request, $country_code)
    // {
    //     // Force UK regardless of the passed $country_code
    //     $countryName = 'United Kingdom';
    //     $countryCode = 'GB';
    //     $nationalLanguage = 'English';
    //     $timezoneName = 'Europe/London';

    //     // Convert "Europe/London" into "GMT (UTC±HH:MM)"
    //     $dateTime = new DateTime('now', new DateTimeZone($timezoneName));
    //     $offsetInHours = $dateTime->format('P'); // e.g. "+00:00"
    //     $formattedTimezone = "GMT (UTC{$offsetInHours})";

    //     return response()->json([
    //         'country_name'       => $countryName,
    //         'country_code'       => $countryCode,
    //         'languages'          => [
    //             ['label' => $nationalLanguage, 'value' => $nationalLanguage]
    //         ],
    //         'timezone'           => $formattedTimezone,
    //         'timezone_name'      => $timezoneName
    //     ]);
    // }


    public function countryDetails(Request $request, $country_code)
    {
        $country = Country::where('iso2', strtoupper($country_code))
            ->with('defaultTimezone')
            ->first();

        if (!$country) {
            return response()->json(['message' => 'Country not found'], 404);
        }

        $tz = $country->defaultTimezone;
        $dt = new \DateTime('now', new \DateTimeZone($tz->name));
        $formattedTimezone = "GMT (UTC{$dt->format('P')})";

        return response()->json([
            'country_name'  => $country->name,
            'country_code'  => $country->iso2,
            'timezone'      => $formattedTimezone,
            'timezone_name' => $tz->name,
        ]);
    }
}
