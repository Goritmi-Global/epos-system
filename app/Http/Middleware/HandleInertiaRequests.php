<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $stripe_public_key = config('app.stripe_public_key');

        // dd($stripe_public_key);
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                
            ],
            'stripe_public_key' => $stripe_public_key,
             'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
                'print_payload'  => fn () => $request->session()->get('print_payload'),
            ],

        ];
    }
}
