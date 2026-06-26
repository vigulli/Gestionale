<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Service;
use App\Models\Product;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class QuoteController extends Controller
{
    public function __construct(private NotificationService $notify) {}

    public function index(Request $request)
    {
        $quotes = Sale::with('customer')
            ->where('type', 'quote')
            ->when($request->search, fn($q, $s) =>
                $q->where('invoice_number', 'like', "%$s%")
                  ->orWhereHas('customer', fn($q) =>
                    $q->where('first_name', 'like', "%$s%")
                      ->orWhere('last_name', 'like', "%$s%")
                      ->orWhere('phone', 'like', "%$s%")
                  )
            )
            ->when($request->status, fn($q, $s) => $q->where('quote_status', $s))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Quotes/Index', [
            'quotes'  => $quotes,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create(Request $request)
    {
        return Inertia::render('Quotes/Create', [
            'customers' => Customer::orderBy('last_name')->get(['id', 'first_name', 'last_name', 'phone', 'email']),
            'services'  => Service::with('category')->where('active', true)->orderBy('sort_order')->get(),
            'products'  => Product::where('active', true)->get(['id', 'name', 'sell_price', 'vat_rate']),
            'repair_id' => $request->repair_id,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id'          => 'required|exists:customers,id',
            'notes'                => 'nullable|string',
            'due_at'               => 'nullable|date',
            'vat_mode'             => 'in:inclusive,exclusive,margin',
            'items'                => 'required|array|min:1',
            'items.*.description'  => 'required|string',
            'items.*.qty'          => 'required|numeric|min:0.01',
            'items.*.unit_price'   => 'required|numeric|min:0',
            'items.*.discount_pct' => 'nullable|numeric|min:0|max:100',
            'items.*.vat_rate'     => 'required|numeric',
            'items.*.service_id'   => 'nullable|exists:services,id',
            'items.*.product_id'   => 'nullable|exists:products,id',
        ]);

        $prefix = tenant('invoice_prefix') ?? 'Q-';
        $next   = tenant('invoice_next_number') ?? 1;

        $quote = Sale::create([
            ...$validated,
            'type'           => 'quote',
            'status'         => 'draft',
            'invoice_number' => $prefix . 'Q' . str_pad($next, 4, '0', STR_PAD_LEFT),
            'issued_at'      => now(),
            'vat_mode'       => $validated['vat_mode'] ?? 'exclusive',
        ]);

        foreach ($validated['items'] as $item) {
            $lineTotal = $item['qty'] * $item['unit_price'] * (1 - ($item['discount_pct'] ?? 0) / 100);
            $quote->items()->create([...$item, 'line_total' => $lineTotal, 'discount_pct' => $item['discount_pct'] ?? 0]);
        }

        $quote->recalculate();

        return redirect()->route('quotes.show', $quote)
            ->with('success', 'Preventivo ' . $quote->invoice_number . ' creato.');
    }

    public function show(Sale $quote)
    {
        $quote->load(['customer', 'items.service', 'items.product']);

        return Inertia::render('Quotes/Show', [
            'quote' => $quote,
        ]);
    }

    // Invia il preventivo al cliente via canali scelti
    public function send(Request $request, Sale $quote)
    {
        $validated = $request->validate([
            'channels'       => 'required|array|min:1',
            'channels.*'     => 'in:email,sms,whatsapp',
            'custom_message' => 'nullable|string|max:500',
        ]);

        $quote->update(['status' => 'sent']);
        $customer = $quote->customer;
        $url      = $quote->quote_url;

        $defaultMsg = "Salve {$customer->first_name}, il tuo preventivo {$quote->invoice_number} " .
                      "di CHF {$quote->total} è pronto.\n" .
                      "Accetta o rifiuta qui: {$url}";

        $message = $validated['custom_message']
            ? $validated['custom_message'] . "\n" . $url
            : $defaultMsg;

        foreach ($validated['channels'] as $channel) {
            $this->notify->send(
                channel: $channel,
                customer: $customer,
                message: $message,
                notifiable: $quote,
            );
        }

        return back()->with('success', 'Preventivo inviato via ' . implode(', ', $validated['channels']) . '.');
    }

    // ─── Pagina pubblica: cliente risponde ────────────────────────────────────

    public function respond(string $token)
    {
        $quote = Sale::where('quote_token', $token)
            ->where('type', 'quote')
            ->with(['customer', 'items'])
            ->firstOrFail();

        // Preventivo già risposto
        $alreadyResponded = in_array($quote->quote_status, ['accepted', 'rejected']);

        return Inertia::render('Public/QuoteResponse', [
            'quote'            => [
                'invoice_number' => $quote->invoice_number,
                'total'          => $quote->total,
                'subtotal'       => $quote->subtotal,
                'vat_amount'     => $quote->vat_amount,
                'notes'          => $quote->notes,
                'due_at'         => $quote->due_at,
                'issued_at'      => $quote->issued_at,
                'quote_status'   => $quote->quote_status,
                'items'          => $quote->items->map(fn($i) => [
                    'description' => $i->description,
                    'qty'         => $i->qty,
                    'unit_price'  => $i->unit_price,
                    'discount_pct'=> $i->discount_pct,
                    'vat_rate'    => $i->vat_rate,
                    'line_total'  => $i->line_total,
                ]),
                'customer_name'  => $quote->customer->full_name,
            ],
            'already_responded' => $alreadyResponded,
            'token'             => $token,
        ]);
    }

    public function submitResponse(Request $request, string $token)
    {
        $quote = Sale::where('quote_token', $token)
            ->where('type', 'quote')
            ->firstOrFail();

        if (in_array($quote->quote_status, ['accepted', 'rejected'])) {
            return back()->with('error', 'Hai già risposto a questo preventivo.');
        }

        $validated = $request->validate([
            'action'           => 'required|in:accepted,rejected',
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $quote->update([
            'quote_status'          => $validated['action'],
            'quote_responded_at'    => now(),
            'quote_response_ip'     => $request->ip(),
            'quote_rejection_reason'=> $validated['rejection_reason'] ?? null,
            'status'                => $validated['action'] === 'accepted' ? 'paid' : 'cancelled',
        ]);

        return Inertia::render('Public/QuoteConfirm', [
            'action'         => $validated['action'],
            'invoice_number' => $quote->invoice_number,
            'tenant_name'    => tenant('name'),
        ]);
    }
}
