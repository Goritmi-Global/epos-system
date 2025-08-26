<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // fetch KPIs via services if needed
        return Inertia::render('Backend/Dashboard/Index');
    }
}
