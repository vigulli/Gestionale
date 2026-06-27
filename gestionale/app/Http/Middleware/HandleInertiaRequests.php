<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $tenant = null;
        if (function_exists('tenancy') && tenancy()->initialized) {
            $t = tenant();
            $tenant = [
                'id'              => $t->id,
                'name'            => $t->name,
                'primary_color'   => $t->primary_color ?? '#6366f1',
                'secondary_color' => $t->secondary_color ?? '#818cf8',
                'logo_url'        => $t->logo_path ? asset('storage/' . $t->logo_path) : null,
                'company_name'    => $t->company_name,
                'iban'            => $t->iban,
                'modules'         => [
                    'repairs'      => (bool) ($t->module_repairs ?? true),
                    'print_orders' => (bool) ($t->module_print_orders ?? false),
                    'pos'          => (bool) ($t->module_pos ?? false),
                ],
            ];
        }

        return [
            ...parent::share($request),
            'ziggy' => fn () => [
                ...(new \Tightenco\Ziggy\Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'auth'       => [
                'user' => $request->user() ? [
                    'id'    => $request->user()->id,
                    'name'  => $request->user()->name,
                    'email' => $request->user()->email,
                ] : null,
            ],
            'tenant'     => $tenant,
            'flash'      => [
                'success' => session('success'),
                'error'   => session('error'),
            ],
            'csrf_token' => csrf_token(),
        ];
    }
}
