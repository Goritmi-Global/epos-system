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
        $countries = Country::orderBy('name')
            ->pluck('name', 'id')
            ->map(fn ($name, $id) => ['label' => $name, 'value' => (string)$id])
            ->values();

        return response()->json($countries);
    }
 
 
public function countryDetails(Request $request, $country_code)
{
    // Force UK regardless of the passed $country_code
    $countryName = 'United Kingdom';
    $countryCode = 'GB';
    $nationalLanguage = 'English';
    $timezoneName = 'Europe/London';

    // Convert "Europe/London" into "GMT (UTC±HH:MM)"
    $dateTime = new DateTime('now', new DateTimeZone($timezoneName));
    $offsetInHours = $dateTime->format('P'); // e.g. "+00:00"
    $formattedTimezone = "GMT (UTC{$offsetInHours})";

    return response()->json([
        'country_name'       => $countryName,
        'country_code'       => $countryCode,
        'languages'          => [
            ['label' => $nationalLanguage, 'value' => $nationalLanguage]
        ],
        'timezone'           => $formattedTimezone,
        'timezone_name'      => $timezoneName
    ]);
}



}

