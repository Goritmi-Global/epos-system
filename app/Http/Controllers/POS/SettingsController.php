<?php 

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateSettingRequest;
use App\Services\POS\SettingsService;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function __construct(private SettingsService $service) {}

    public function index()
    {
        return Inertia::render('Settings/Index', [
            'settings' => $this->service->all(),
        ]);
    }

    public function update(UpdateSettingRequest $request)
    {
        $this->service->saveMany($request->validated());
        return back()->with('success','Settings saved');
    }
}
