<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Services\POS\PosOrderService;
use Inertia\Inertia;

class PosOrderController extends Controller
{
    public function __construct(private PosOrderService $service) {}
    public function screen()
    {  
        // dd("tset");
        return Inertia::render('Backend/POS/Index'); 

    }

   public function fetchProfileTables()
    {  
        
        $profileTables = $this->service->getProfileTable();
        return $profileTables; 
    }

}