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
            ->get([
                'iso2 as code',
                'name',
                'phone_code'
            ]);
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

    public function countryDetails(Request $request, $country_code)
    {
        $country = Country::where('iso2', strtoupper($country_code))
            ->with(['timezones' => function ($q) {
                $q->orderByDesc('is_default');
            }])
            ->first();


        if (!$country) {
            return response()->json(['message' => 'Country not found'], 404);
        }

        $timezones = $country->timezones->map(function ($tz) {
            $dt = new \DateTime('now', new \DateTimeZone($tz->name));
            return [
                'id'        => $tz->id,
                'name'      => $tz->name,
                'gmt'       => 'GMT (UTC' . $dt->format('P') . ')',
                'is_default' => $tz->is_default,
            ];
        });

        return response()->json([
            'country_name' => $country->name,
            'country_code' => $country->iso2,
            'timezones'    => $timezones,
        ]);
    }
}
