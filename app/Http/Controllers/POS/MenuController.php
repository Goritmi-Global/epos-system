<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Services\POS\MenuService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MenuController extends Controller
{
    public function __construct(private MenuService $service) {}

    public function index(Request $request)
    {
        return Inertia::render('Backend/Menu/Index');
        // $items = $this->service->list($request->only('q'));
        // return Inertia::render('Menu/Index', ['items' => $items]);
    }

    public function create() { return Inertia::render('Menu/Form', ['mode'=>'create']); }

    public function store(Request $request)
    {
        $validated = $request->validate(['name'=>'required|max:255','slug'=>'nullable|max:255']);
        $this->service->create($validated);
        return redirect()->route('menu.index')->with('success','Menu created');
    }

    public function edit(Menu $menu) { return Inertia::render('Menu/Form', ['mode'=>'edit','item'=>$menu]); }

    public function update(Request $request, Menu $menu)
    {
        $validated = $request->validate(['name'=>'required|max:255','slug'=>'nullable|max:255']);
        $this->service->update($menu, $validated);
        return redirect()->route('menu.index')->with('success','Menu updated');
    }

    public function destroy(Menu $menu)
    {
        $this->service->delete($menu);
        return back()->with('success','Menu deleted');
    }
}