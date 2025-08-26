<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class PosOrderController extends Controller
{
    public function screen()
    {  
        // dd("tset");
        return Inertia::render('Backend/POS/Index'); 

    }
}