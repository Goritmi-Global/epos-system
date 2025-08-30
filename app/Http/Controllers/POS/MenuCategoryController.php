<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
class MenuCategoryController extends Controller
{
    public function index()
    {
        // return Inertia page with categories
        return Inertia::render('Backend/Menu/Index');
    }
}
