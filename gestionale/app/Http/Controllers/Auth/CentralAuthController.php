<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CentralAuthController extends Controller
{
    // ─── Login ────────────────────────────────────────────────────────────────

    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('tenant.select');
        }
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Credenziali non valide.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->route('tenant.select');
    }

    public function logout(Request $request)
    {
        session()->forget('current_tenant_id');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    // ─── Tenant selector ──────────────────────────────────────────────────────

    public function selectTenant()
    {
        $tenants = Tenant::orderBy('name')->get([
            'id', 'name', 'primary_color', 'secondary_color',
            'logo_path', 'company_name', 'module_repairs',
            'module_print_orders', 'module_pos',
        ]);

        $current = session('current_tenant_id');

        return Inertia::render('TenantSelector', [
            'tenants' => $tenants,
            'current' => $current,
        ]);
    }

    public function switchTenant(Request $request)
    {
        $data = $request->validate([
            'tenant_id' => 'required|string|exists:tenants,id',
        ]);

        session(['current_tenant_id' => $data['tenant_id']]);

        return redirect()->route('repairs.index');
    }
}
