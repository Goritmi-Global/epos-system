<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class CustomerDisplayController extends Controller
{
    public function index($terminal = 'default')
    {
        if (! $terminal) {
            $terminal = 'terminal-'.time();
        }

        return Inertia::render('Backend/CustomerDisplay/Index', [
            'terminalId' => $terminal,
        ]);
    }
}
