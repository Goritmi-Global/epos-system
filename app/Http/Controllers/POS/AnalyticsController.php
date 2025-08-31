<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Services\POS\AnalyticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    public function __construct(private AnalyticsService $service) {}

    public function index(Request $request)
    {
       return Inertia::render('Backend/Analytics/Index');   
        $range = $request->input('range','7d');
        $widgets = $this->service->dashboard($range); // returns arrays for charts
        return Inertia::render('Analytics/Index', ['range'=>$range, 'widgets'=>$widgets]);
    }
}