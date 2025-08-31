<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StockLogController extends Controller
{
    public function index()
    {
        return Inertia::render('Backend/Inventory/StockMomentLogs/Index');
    }
}
