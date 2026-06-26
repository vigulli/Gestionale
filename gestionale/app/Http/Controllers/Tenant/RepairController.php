<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Repair;
use App\Models\RepairItem;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RepairController extends Controller
{
    public function index(Request $request)
    {
        $repairs = Repair::with(['customer', 'assignedTo'])
            ->when($request->search, fn($q, $s) =>
                $q->where('ticket_number', 'like', "%$s%")
                  ->orWhereHas('customer', fn($q) =>
                    $q->where('first_name', 'like', "%$s%")
                      ->orWhere('last_name', 'like', "%$s%")
                      ->orWhere('phone', 'like', "%$s%")
                  )
            )
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->priority, fn($q, $p) => $q->where('priority', $p))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Repairs/Index', [
            'repairs'    => $repairs,
            'statuses'   => Repair::statuses(),
            'priorities' => Repair::priorities(),
            'filters'    => $request->only(['search', 'status', 'priority']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Repairs/Create', [
            'customers'  => Customer::orderBy('last_name')->get(['id', 'first_name', 'last_name', 'phone']),
            'services'   => Service::with('category')->where('active', true)->orderBy('sort_order')->get(),
            'technicians'=> User::all(['id', 'name']),
            'statuses'   => Repair::statuses(),
            'priorities' => Repair::priorities(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id'         => 'required|exists:customers,id',
            'device_brand'        => 'nullable|string|max:100',
            'device_model'        => 'nullable|string|max:100',
            'device_serial'       => 'nullable|string|max:100',
            'device_password'     => 'nullable|string|max:100',
            'problem_description' => 'required|string',
            'priority'            => 'required|in:low,normal,high,urgent',
            'assigned_to'         => 'nullable|exists:users,id',
            'estimated_cost'      => 'nullable|numeric|min:0',
            'deadline_at'         => 'nullable|date',
            'items'               => 'array',
            'items.*.description' => 'required|string',
            'items.*.qty'         => 'required|numeric|min:0.01',
            'items.*.unit_price'  => 'required|numeric|min:0',
            'items.*.vat_rate'    => 'required|numeric',
            'items.*.service_id'  => 'nullable|exists:services,id',
        ]);

        $repair = Repair::create($validated);

        foreach ($request->items ?? [] as $item) {
            $repair->items()->create($item);
        }

        return redirect()->route('repairs.show', $repair)
            ->with('success', 'Riparazione #' . $repair->ticket_number . ' creata.');
    }

    public function show(Repair $repair)
    {
        $repair->load(['customer', 'assignedTo', 'items.service', 'items.product', 'statusHistory.user']);

        return Inertia::render('Repairs/Show', [
            'repair'     => $repair,
            'statuses'   => Repair::statuses(),
            'priorities' => Repair::priorities(),
            'services'   => Service::where('active', true)->get(['id', 'name', 'price_default', 'vat_rate']),
        ]);
    }

    public function updateStatus(Request $request, Repair $repair)
    {
        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', array_keys(Repair::statuses())),
            'note'   => 'nullable|string|max:500',
        ]);

        $repair->update(['status' => $validated['status']]);

        if ($validated['note'] ?? null) {
            $repair->statusHistory()->create([
                'status'     => $validated['status'],
                'note'       => $validated['note'],
                'user_id'    => auth()->id(),
                'changed_at' => now(),
            ]);
        }

        return back()->with('success', 'Stato aggiornato.');
    }

    public function edit(Repair $repair)
    {
        $repair->load(['customer', 'items.service']);

        return Inertia::render('Repairs/Edit', [
            'repair'      => $repair,
            'customers'   => Customer::orderBy('last_name')->get(['id', 'first_name', 'last_name', 'phone']),
            'services'    => Service::with('category')->where('active', true)->get(),
            'technicians' => User::all(['id', 'name']),
            'statuses'    => Repair::statuses(),
            'priorities'  => Repair::priorities(),
        ]);
    }

    public function update(Request $request, Repair $repair)
    {
        $validated = $request->validate([
            'device_brand'        => 'nullable|string|max:100',
            'device_model'        => 'nullable|string|max:100',
            'device_serial'       => 'nullable|string|max:100',
            'problem_description' => 'required|string',
            'technician_notes'    => 'nullable|string',
            'priority'            => 'required|in:low,normal,high,urgent',
            'assigned_to'         => 'nullable|exists:users,id',
            'estimated_cost'      => 'nullable|numeric|min:0',
            'final_cost'          => 'nullable|numeric|min:0',
            'deadline_at'         => 'nullable|date',
            'items'               => 'array',
        ]);

        $repair->update($validated);

        if ($request->has('items')) {
            $repair->items()->delete();
            foreach ($request->items as $item) {
                $repair->items()->create($item);
            }
        }

        return redirect()->route('repairs.show', $repair)->with('success', 'Riparazione aggiornata.');
    }

    public function destroy(Repair $repair)
    {
        $repair->delete();
        return redirect()->route('repairs.index')->with('success', 'Riparazione eliminata.');
    }

    // Pagina pubblica tracking (senza auth)
    public function track(string $token)
    {
        $repair = Repair::where('tracking_token', $token)
            ->with(['customer', 'statusHistory'])
            ->firstOrFail();

        return Inertia::render('Public/TrackRepair', [
            'repair'   => [
                'ticket_number'   => $repair->ticket_number,
                'device_brand'    => $repair->device_brand,
                'device_model'    => $repair->device_model,
                'status'          => $repair->status,
                'status_label'    => $repair->status_label,
                'status_color'    => $repair->status_color,
                'received_at'     => $repair->received_at,
                'deadline_at'     => $repair->deadline_at,
                'completed_at'    => $repair->completed_at,
                'statusHistory'   => $repair->statusHistory->map(fn($h) => [
                    'status'     => $h->status,
                    'note'       => $h->note,
                    'changed_at' => $h->changed_at,
                ]),
            ],
        ]);
    }
}
