<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        $tenant = tenant();

        return Inertia::render('Settings/Index', [
            'tenant' => [
                'id'                       => $tenant->id,
                'name'                     => $tenant->name,
                'primary_color'            => $tenant->primary_color,
                'secondary_color'          => $tenant->secondary_color,
                'logo_url'                 => $tenant->logo_path ? Storage::disk('public')->url($tenant->logo_path) : null,
                'favicon_url'              => $tenant->favicon_path ? Storage::disk('public')->url($tenant->favicon_path) : null,
                'invoice_background_url'   => $tenant->invoice_background_path ? Storage::disk('public')->url($tenant->invoice_background_path) : null,
                'invoice_layout'           => $tenant->invoice_layout,
                'invoice_prefix'           => $tenant->invoice_prefix,
                'invoice_next_number'      => $tenant->invoice_next_number,
                'company_name'             => $tenant->company_name,
                'address'                  => $tenant->address,
                'city'                     => $tenant->city,
                'zip'                      => $tenant->zip,
                'country'                  => $tenant->country,
                'phone'                    => $tenant->phone,
                'email'                    => $tenant->email,
                'website'                  => $tenant->website,
                'uid_number'               => $tenant->uid_number,
                'iban'                     => $tenant->iban,
                'bank_name'                => $tenant->bank_name,
                'smtp_host'                => $tenant->smtp_host,
                'smtp_port'                => $tenant->smtp_port,
                'smtp_user'                => $tenant->smtp_user,
                'smtp_encryption'          => $tenant->smtp_encryption,
                'smtp_from_name'           => $tenant->smtp_from_name,
                'smtp_from_email'          => $tenant->smtp_from_email,
                'bulkgate_app_id'          => $tenant->bulkgate_app_id,
                'bulkgate_sender_id'       => $tenant->bulkgate_sender_id,
                'bulkgate_whatsapp_enabled'=> $tenant->bulkgate_whatsapp_enabled,
                'sumup_merchant_code'      => $tenant->sumup_merchant_code,
                'module_repairs'           => $tenant->module_repairs,
                'module_print_orders'      => $tenant->module_print_orders,
                'module_pos'               => $tenant->module_pos,
                'module_inventory'         => $tenant->module_inventory,
                'module_loyalty'           => $tenant->module_loyalty,
                'vat_rate_standard'        => $tenant->vat_rate_standard,
                'vat_rate_reduced'         => $tenant->vat_rate_reduced,
            ],
        ]);
    }

    public function updateBranding(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:100',
            'primary_color'    => 'required|string|max:7',
            'secondary_color'  => 'required|string|max:7',
            'logo'             => 'nullable|file|mimes:png,jpg,jpeg,svg,webp|max:2048',
            'favicon'          => 'nullable|file|mimes:png,ico|max:512',
        ]);

        $tenant = tenant();

        if ($request->hasFile('logo')) {
            if ($tenant->logo_path) Storage::disk('public')->delete($tenant->logo_path);
            $validated['logo_path'] = $request->file('logo')->store('tenants/' . $tenant->id, 'public');
        }
        if ($request->hasFile('favicon')) {
            if ($tenant->favicon_path) Storage::disk('public')->delete($tenant->favicon_path);
            $validated['favicon_path'] = $request->file('favicon')->store('tenants/' . $tenant->id, 'public');
        }

        unset($validated['logo'], $validated['favicon']);
        $tenant->update($validated);

        return back()->with('success', 'Branding aggiornato.');
    }

    public function updateBusiness(Request $request)
    {
        $validated = $request->validate([
            'company_name'    => 'nullable|string|max:191',
            'address'         => 'nullable|string|max:191',
            'city'            => 'nullable|string|max:100',
            'zip'             => 'nullable|string|max:20',
            'country'         => 'nullable|string|max:2',
            'phone'           => 'nullable|string|max:30',
            'email'           => 'nullable|email|max:191',
            'website'         => 'nullable|url|max:191',
            'uid_number'      => 'nullable|string|max:30',
            'iban'            => 'nullable|string|max:34',
            'bank_name'       => 'nullable|string|max:191',
            'invoice_prefix'  => 'nullable|string|max:10',
            'invoice_next_number' => 'nullable|integer|min:1',
            'vat_rate_standard'   => 'nullable|numeric|min:0|max:100',
            'vat_rate_reduced'    => 'nullable|numeric|min:0|max:100',
        ]);

        tenant()->update($validated);
        return back()->with('success', 'Dati aziendali aggiornati.');
    }

    public function updateInvoiceLayout(Request $request)
    {
        $validated = $request->validate([
            'invoice_background' => 'nullable|file|mimes:png,jpg,jpeg|max:5120',
            'invoice_layout'     => 'nullable|json',
        ]);

        $tenant = tenant();

        if ($request->hasFile('invoice_background')) {
            if ($tenant->invoice_background_path) Storage::disk('public')->delete($tenant->invoice_background_path);
            $path = $request->file('invoice_background')->store('tenants/' . $tenant->id . '/invoice', 'public');
            $tenant->update(['invoice_background_path' => $path]);
        }

        if ($request->invoice_layout) {
            $tenant->update(['invoice_layout' => json_decode($request->invoice_layout, true)]);
        }

        return back()->with('success', 'Layout fattura aggiornato.');
    }

    public function updateSmtp(Request $request)
    {
        $validated = $request->validate([
            'smtp_host'       => 'nullable|string|max:191',
            'smtp_port'       => 'nullable|integer|min:1|max:65535',
            'smtp_user'       => 'nullable|string|max:191',
            'smtp_password'   => 'nullable|string|max:191',
            'smtp_encryption' => 'nullable|in:tls,ssl,none',
            'smtp_from_name'  => 'nullable|string|max:100',
            'smtp_from_email' => 'nullable|email|max:191',
        ]);

        tenant()->update($validated);
        return back()->with('success', 'Configurazione email aggiornata.');
    }

    public function testSmtp(Request $request)
    {
        $tenant = tenant();

        if (!$tenant->smtp_host || !$tenant->smtp_user) {
            return back()->with('error', 'Configura prima SMTP prima di testarlo.');
        }

        try {
            config([
                'mail.mailers.tenant_smtp' => [
                    'transport'  => 'smtp',
                    'host'       => $tenant->smtp_host,
                    'port'       => $tenant->smtp_port ?? 587,
                    'username'   => $tenant->smtp_user,
                    'password'   => $tenant->smtp_password,
                    'encryption' => $tenant->smtp_encryption,
                ],
            ]);

            \Illuminate\Support\Facades\Mail::mailer('tenant_smtp')
                ->raw('Test email dal gestionale ' . $tenant->name, function ($msg) use ($tenant, $request) {
                    $msg->to($request->user()->email)
                        ->from($tenant->smtp_from_email ?? $tenant->smtp_user, $tenant->smtp_from_name ?? $tenant->name)
                        ->subject('Test SMTP — ' . $tenant->name);
                });

            return back()->with('success', 'Email di test inviata a ' . $request->user()->email);
        } catch (\Throwable $e) {
            return back()->with('error', 'Errore SMTP: ' . $e->getMessage());
        }
    }

    public function updateBulkgate(Request $request)
    {
        $validated = $request->validate([
            'bulkgate_app_id'           => 'nullable|string|max:100',
            'bulkgate_app_token'        => 'nullable|string|max:191',
            'bulkgate_sender_id'        => 'nullable|string|max:20',
            'bulkgate_whatsapp_enabled' => 'boolean',
        ]);

        tenant()->update($validated);
        return back()->with('success', 'Configurazione BulkGate aggiornata.');
    }

    public function testBulkgate(Request $request)
    {
        $tenant = tenant();
        $validated = $request->validate([
            'test_phone'   => 'required|string',
            'test_channel' => 'required|in:sms,whatsapp',
        ]);

        $appId    = $tenant->bulkgate_app_id;
        $appToken = $tenant->bulkgate_app_token;

        if (!$appId || !$appToken) {
            return back()->with('error', 'BulkGate non configurato.');
        }

        try {
            $endpoint = $validated['test_channel'] === 'whatsapp'
                ? 'https://portal.bulkgate.com/api/2.0/simple/whatsapp'
                : 'https://portal.bulkgate.com/api/2.0/simple/transactional';

            $response = \Illuminate\Support\Facades\Http::withBasicAuth($appId, $appToken)
                ->post($endpoint, [
                    'application_id'    => $appId,
                    'application_token' => $appToken,
                    'number'            => $validated['test_phone'],
                    'text'              => 'Test dal gestionale ' . $tenant->name . ' ✓',
                ]);

            if ($response->successful()) {
                return back()->with('success', strtoupper($validated['test_channel']) . ' di test inviato a ' . $validated['test_phone']);
            }
            return back()->with('error', 'BulkGate: ' . ($response->json('error.description') ?? $response->body()));
        } catch (\Throwable $e) {
            return back()->with('error', 'Errore: ' . $e->getMessage());
        }
    }

    public function updateSumup(Request $request)
    {
        $validated = $request->validate([
            'sumup_api_key'       => 'nullable|string|max:191',
            'sumup_merchant_code' => 'nullable|string|max:50',
        ]);

        tenant()->update($validated);
        return back()->with('success', 'Configurazione SumUp aggiornata.');
    }

    public function updateModules(Request $request)
    {
        $validated = $request->validate([
            'module_repairs'      => 'boolean',
            'module_print_orders' => 'boolean',
            'module_pos'          => 'boolean',
            'module_inventory'    => 'boolean',
            'module_loyalty'      => 'boolean',
        ]);

        tenant()->update($validated);
        return back()->with('success', 'Moduli aggiornati.');
    }
}
