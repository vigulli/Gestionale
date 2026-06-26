<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\NotificationLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::withCount(['repairs', 'sales'])
            ->withSum(['sales as total_invoiced' => fn($q) => $q->where('type', 'sale')->where('status', 'paid')], 'total')
            ->when($request->search, fn($q, $s) =>
                $q->where('first_name', 'like', "%$s%")
                  ->orWhere('last_name', 'like', "%$s%")
                  ->orWhere('phone', 'like', "%$s%")
                  ->orWhere('email', 'like', "%$s%")
                  ->orWhere('company', 'like', "%$s%")
            )
            ->when($request->category, fn($q, $c) => $q->where('category', $c))
            ->orderBy('last_name')
            ->paginate(25)
            ->withQueryString();

        $categories = Customer::whereNotNull('category')->distinct()->pluck('category');

        return Inertia::render('Customers/Index', [
            'customers'  => $customers,
            'categories' => $categories,
            'filters'    => $request->only(['search', 'category']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Customers/Form', [
            'customer'   => null,
            'categories' => Customer::whereNotNull('category')->distinct()->pluck('category'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name'  => 'required|string|max:100',
            'last_name'   => 'required|string|max:100',
            'email'       => 'nullable|email|max:191',
            'phone'       => 'nullable|string|max:30',
            'company'     => 'nullable|string|max:191',
            'address'     => 'nullable|string|max:191',
            'city'        => 'nullable|string|max:100',
            'zip'         => 'nullable|string|max:20',
            'country'     => 'nullable|string|max:2',
            'tax_number'  => 'nullable|string|max:50',
            'category'    => 'nullable|string|max:100',
            'notes'       => 'nullable|string',
        ]);

        $customer = Customer::create($validated);

        return redirect()->route('customers.show', $customer)
            ->with('success', 'Cliente creato.');
    }

    public function show(Customer $customer)
    {
        $customer->load([
            'repairs' => fn($q) => $q->latest()->limit(50),
            'repairs.assignedTo',
            'sales'   => fn($q) => $q->latest()->limit(50),
            'sales.items',
        ]);

        // Storico comunicazioni per questo cliente
        $communications = NotificationLog::where('customer_id', $customer->id)
            ->orWhere(function ($q) use ($customer) {
                // Include anche i log legati a riparazioni/vendite di questo cliente
                $repairIds = $customer->repairs->pluck('id');
                $saleIds   = $customer->sales->pluck('id');
                $q->where(function ($q) use ($repairIds) {
                    $q->where('notifiable_type', 'App\\Models\\Repair')
                      ->whereIn('notifiable_id', $repairIds);
                })->orWhere(function ($q) use ($saleIds) {
                    $q->where('notifiable_type', 'App\\Models\\Sale')
                      ->whereIn('notifiable_id', $saleIds);
                });
            })
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $repairs = $customer->repairs->map(fn($r) => [
            'id'             => $r->id,
            'ticket_number'  => $r->ticket_number,
            'device'         => trim($r->device_brand . ' ' . $r->device_model),
            'status'         => $r->status,
            'status_label'   => $r->status_label,
            'status_color'   => $r->status_color,
            'final_cost'     => $r->final_cost,
            'estimated_cost' => $r->estimated_cost,
            'received_at'    => $r->received_at,
            'completed_at'   => $r->completed_at,
            'assigned_to'    => $r->assignedTo?->name,
        ]);

        $invoices = $customer->sales->map(fn($s) => [
            'id'             => $s->id,
            'invoice_number' => $s->invoice_number,
            'type'           => $s->type,
            'status'         => $s->status,
            'quote_status'   => $s->quote_status,
            'total'          => $s->total,
            'issued_at'      => $s->issued_at,
            'items_count'    => $s->items->count(),
        ]);

        return Inertia::render('Customers/Show', [
            'customer'       => $customer,
            'stats'          => $customer->stats,
            'repairs'        => $repairs,
            'invoices'       => $invoices,
            'communications' => $communications,
        ]);
    }

    public function edit(Customer $customer)
    {
        return Inertia::render('Customers/Form', [
            'customer'   => $customer,
            'categories' => Customer::whereNotNull('category')->distinct()->pluck('category'),
        ]);
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'first_name'  => 'required|string|max:100',
            'last_name'   => 'required|string|max:100',
            'email'       => 'nullable|email|max:191',
            'phone'       => 'nullable|string|max:30',
            'company'     => 'nullable|string|max:191',
            'address'     => 'nullable|string|max:191',
            'city'        => 'nullable|string|max:100',
            'zip'         => 'nullable|string|max:20',
            'country'     => 'nullable|string|max:2',
            'tax_number'  => 'nullable|string|max:50',
            'category'    => 'nullable|string|max:100',
            'notes'       => 'nullable|string',
        ]);

        $customer->update($validated);

        return redirect()->route('customers.show', $customer)
            ->with('success', 'Cliente aggiornato.');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'Cliente eliminato.');
    }
}
