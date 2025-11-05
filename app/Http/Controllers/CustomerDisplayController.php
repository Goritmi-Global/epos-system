<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerDisplayController extends Controller
{
    public function index($terminal = 'default')
    {
        // Generate terminal ID if not provided
        if (!$terminal) {
            $terminal = 'terminal-' . time();
        }
        return Inertia::render('Backend/CustomerDisplay/Index', [
            'terminalId' => $terminal,
        ]);
    }
}
