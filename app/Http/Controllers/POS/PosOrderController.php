<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Services\POS\PosOrderService;
use Inertia\Inertia;

class PosOrderController extends Controller
{
    public function __construct(private PosOrderService $service) {}

    public function index()
    {  
        return Inertia::render('Backend/POS/Index'); 
    }
    public function fetchMenuCategories()
    {  
        $menuCategories = $this->service->getMenuCategories();
        return $menuCategories; 
    }
    public function fetchMenuItems()
    {  
        $menuItems = $this->service->getAllMenus();
        return $menuItems; 
    }
}