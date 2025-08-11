<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stevebauman\Location\Facades\Location;

class GeoController extends Controller
{
    public function info(Request $request)
    {
        // Useful in dev behind proxies
        // $ip = $request->query('ip', $request->ip());
         $ip = '8.8.8.8'; 
        $pos = Location::get($ip); // countryName, countryCode, timezone (if provider returns it)

        // dd($pos);
        return response()->json([
            'ip'            => $ip,
            'country_name'  => $pos?->countryName,
            'country_code'  => $pos?->countryCode, // e.g. GB, PK
            'timezone'      => $pos?->timezone,    // may be null from some providers
        ]);
    }
}
