<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\PrintOrder;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PrintOrderController extends Controller
{
    public function __construct(private NotificationService $notify) {}

    public function index(Request $request)
    {
        $orders = PrintOrder::with(['customer', 'assignedTo'])
            ->when($request->search, fn($q, $s) =>
                $q->where('order_number', 'like', "%$s%")
                  ->orWhereHas('customer', fn($q) =>
                    $q->where('first_name', 'like', "%$s%")
                      ->orWhere('last_name', 'like', "%$s%")
                      ->orWhere('phone', 'like', "%$s%")
                  )
            )
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->garment_type, fn($q, $t) => $q->where('garment_type', $t))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('PrintOrders/Index', [
            'orders'       => $orders,
            'statuses'     => PrintOrder::statuses(),
            'garmentTypes' => PrintOrder::garmentTypes(),
            'filters'      => $request->only(['search', 'status', 'garment_type']),
        ]);
    }

    public function create(Request $request)
    {
        return Inertia::render('PrintOrders/Create', [
            'customers'    => Customer::orderBy('last_name')->get(['id', 'first_name', 'last_name', 'phone', 'email']),
            'technicians'  => User::all(['id', 'name']),
            'garmentTypes' => PrintOrder::garmentTypes(),
            'positions'    => PrintOrder::positions(),
            'sizes'        => PrintOrder::sizes(),
            'statuses'     => PrintOrder::statuses(),
            'customer_id'  => $request->customer_id,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id'       => 'required|exists:customers,id',
            'garment_type'      => 'required|string',
            'garment_color'     => 'nullable|string|max:50',
            'garment_size'      => 'nullable|string|max:20',
            'quantity'          => 'required|integer|min:1',
            'print_description' => 'nullable|string',
            'print_position'    => 'nullable|string',
            'print_size_cm'     => 'nullable|string|max:30',
            'unit_price'        => 'required|numeric|min:0',
            'vat_rate'          => 'required|numeric',
            'assigned_to'       => 'nullable|exists:users,id',
            'deadline_at'       => 'nullable|date',
            'notes'             => 'nullable|string',
            'print_file'        => 'nullable|file|mimes:jpg,jpeg,png,pdf,svg,ai,eps|max:20480',
        ]);

        $filePath = null;
        if ($request->hasFile('print_file')) {
            $filePath = $request->file('print_file')->store('print-orders/artwork', 'public');
        }

        $order = PrintOrder::create([...$validated, 'print_file_path' => $filePath, 'status' => 'pending']);

        return redirect()->route('print-orders.show', $order)
            ->with('success', 'Ordine ' . $order->order_number . ' creato.');
    }

    public function show(PrintOrder $printOrder)
    {
        $printOrder->load(['customer', 'assignedTo']);

        return Inertia::render('PrintOrders/Show', [
            'order'    => $printOrder,
            'statuses' => PrintOrder::statuses(),
            'garmentTypes' => PrintOrder::garmentTypes(),
        ]);
    }

    public function edit(PrintOrder $printOrder)
    {
        $printOrder->load(['customer']);

        return Inertia::render('PrintOrders/Create', [
            'order'        => $printOrder,
            'customers'    => Customer::orderBy('last_name')->get(['id', 'first_name', 'last_name', 'phone', 'email']),
            'technicians'  => User::all(['id', 'name']),
            'garmentTypes' => PrintOrder::garmentTypes(),
            'positions'    => PrintOrder::positions(),
            'sizes'        => PrintOrder::sizes(),
            'statuses'     => PrintOrder::statuses(),
        ]);
    }

    public function update(Request $request, PrintOrder $printOrder)
    {
        $validated = $request->validate([
            'garment_type'      => 'required|string',
            'garment_color'     => 'nullable|string|max:50',
            'garment_size'      => 'nullable|string|max:20',
            'quantity'          => 'required|integer|min:1',
            'print_description' => 'nullable|string',
            'print_position'    => 'nullable|string',
            'print_size_cm'     => 'nullable|string|max:30',
            'unit_price'        => 'required|numeric|min:0',
            'vat_rate'          => 'required|numeric',
            'status'            => 'required|in:' . implode(',', array_keys(PrintOrder::statuses())),
            'assigned_to'       => 'nullable|exists:users,id',
            'deadline_at'       => 'nullable|date',
            'notes'             => 'nullable|string',
            'print_file'        => 'nullable|file|mimes:jpg,jpeg,png,pdf,svg,ai,eps|max:20480',
        ]);

        if ($request->hasFile('print_file')) {
            if ($printOrder->print_file_path) {
                Storage::disk('public')->delete($printOrder->print_file_path);
            }
            $validated['print_file_path'] = $request->file('print_file')->store('print-orders/artwork', 'public');
        }

        $printOrder->update($validated);

        return redirect()->route('print-orders.show', $printOrder)
            ->with('success', 'Ordine aggiornato.');
    }

    public function updateStatus(Request $request, PrintOrder $printOrder)
    {
        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', array_keys(PrintOrder::statuses())),
        ]);

        $printOrder->update($validated);

        // Notifica cliente quando pronto o consegnato
        if (in_array($validated['status'], ['ready', 'delivered'])) {
            $customer = $printOrder->customer;
            $msg = $validated['status'] === 'ready'
                ? "Buone notizie! Il tuo ordine {$printOrder->order_number} è pronto per il ritiro.\nTraccia l'ordine: {$printOrder->tracking_url}"
                : "Il tuo ordine {$printOrder->order_number} è stato consegnato. Grazie!";

            if ($customer->phone) {
                $this->notify->send('sms', $customer, $msg, $printOrder);
            }
        }

        return back()->with('success', 'Stato aggiornato.');
    }

    public function notify(Request $request, PrintOrder $printOrder)
    {
        $validated = $request->validate([
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:sms,whatsapp,email',
            'message' => 'required|string',
        ]);

        $customer = $printOrder->customer;
        foreach ($validated['channels'] as $channel) {
            $this->notify->send($channel, $customer, $validated['message'], $printOrder);
        }

        return back()->with('success', 'Notifica inviata.');
    }

    public function destroy(PrintOrder $printOrder)
    {
        if ($printOrder->print_file_path) {
            Storage::disk('public')->delete($printOrder->print_file_path);
        }
        $printOrder->delete();
        return redirect()->route('print-orders.index')->with('success', 'Ordine eliminato.');
    }

    // Pagina pubblica tracking ordine
    public function trackPublic(string $token)
    {
        $order = PrintOrder::where('tracking_token', $token)
            ->with('customer')
            ->firstOrFail();

        return Inertia::render('Public/TrackPrintOrder', [
            'order' => [
                'order_number'  => $order->order_number,
                'garment_type'  => PrintOrder::garmentTypes()[$order->garment_type] ?? $order->garment_type,
                'quantity'      => $order->quantity,
                'status'        => $order->status,
                'status_label'  => $order->status_label,
                'status_color'  => $order->status_color,
                'deadline_at'   => $order->deadline_at,
                'statuses'      => PrintOrder::statuses(),
            ],
        ]);
    }
}
