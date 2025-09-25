<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\POS\AnalyticsService;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    public function __construct(private AnalyticsService $svc) {}

    public function page(Request $req)
    { 
        return Inertia::render('Backend/Analytics/Index');
    }

    public function index(Request $req)
    {
        $validated = $req->validate([
            'range'     => 'nullable|in:today,last7,last30,thisMonth,all',
            'orderType' => 'nullable|in:All,dine,delivery',
            'payType'   => 'nullable|in:All,cash,card,qr,bank',
        ]);

        $data = $this->svc->fetch([
            'range'     => $validated['range']     ?? 'last30',
            'orderType' => $validated['orderType'] ?? 'All',
            'payType'   => $validated['payType']   ?? 'All',
        ]);

        return response()->json($data);
    }
}
