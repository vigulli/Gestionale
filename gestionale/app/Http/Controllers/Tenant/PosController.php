<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PosController extends Controller
{
    // ─── POS Interface ───────────────────────────────────────────────────────

    public function index()
    {
        return Inertia::render('Pos/Index', [
            'products'  => Product::where('stock_qty', '>', 0)->orWhere('type', 'product')
                ->orderBy('name')->get(['id', 'name', 'sku', 'barcode', 'sell_price', 'vat_rate', 'stock_qty', 'type']),
            'services'  => Service::where('active', true)->with('category')
                ->orderBy('sort_order')->get(['id', 'name', 'price_default', 'vat_rate', 'service_category_id']),
            'customers' => Customer::orderBy('last_name')->get(['id', 'first_name', 'last_name', 'phone', 'email']),
            'vatRates'  => [8.1, 2.6, 0],
        ]);
    }

    // ─── Checkout ────────────────────────────────────────────────────────────

    public function checkout(Request $request)
    {
        $data = $request->validate([
            'customer_id'    => 'nullable|exists:customers,id',
            'items'          => 'required|array|min:1',
            'items.*.type'   => 'required|in:product,service,manual',
            'items.*.id'     => 'nullable|integer',
            'items.*.description' => 'required|string',
            'items.*.qty'    => 'required|numeric|min:0.01',
            'items.*.unit_price'  => 'required|numeric|min:0',
            'items.*.vat_rate'    => 'required|numeric|min:0|max:100',
            'items.*.discount_pct' => 'nullable|numeric|min:0|max:100',
            'payment_method' => 'required|in:cash,card,sumup,transfer,mixed',
            'payment_reference' => 'nullable|string|max:100',
            'paid_amount'    => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes'          => 'nullable|string',
            'vat_mode'       => 'nullable|in:inclusive,exclusive',
        ]);

        DB::transaction(function () use ($data, &$sale) {
            $tenant = tenant();
            $prefix = $tenant->invoice_prefix ?? 'INV-';
            $next   = $tenant->invoice_next_number ?? 1;
            $invoiceNumber = $prefix . str_pad($next, 6, '0', STR_PAD_LEFT);
            $tenant->update(['invoice_next_number' => $next + 1]);

            $sale = Sale::create([
                'invoice_number'  => $invoiceNumber,
                'customer_id'     => $data['customer_id'] ?? null,
                'type'            => 'sale',
                'status'          => 'paid',
                'payment_method'  => $data['payment_method'],
                'payment_reference' => $data['payment_reference'] ?? null,
                'paid_amount'     => $data['paid_amount'] ?? 0,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'vat_mode'        => $data['vat_mode'] ?? 'inclusive',
                'notes'           => $data['notes'] ?? null,
                'source_type'     => 'manual',
                'source_id'       => 0,
                'issued_at'       => now(),
            ]);

            foreach ($data['items'] as $item) {
                $lineTotal = $item['qty'] * $item['unit_price'] * (1 - ($item['discount_pct'] ?? 0) / 100);

                $sale->items()->create([
                    'product_id'   => ($item['type'] === 'product' && isset($item['id'])) ? $item['id'] : null,
                    'service_id'   => ($item['type'] === 'service' && isset($item['id'])) ? $item['id'] : null,
                    'description'  => $item['description'],
                    'qty'          => $item['qty'],
                    'unit_price'   => $item['unit_price'],
                    'discount_pct' => $item['discount_pct'] ?? 0,
                    'vat_rate'     => $item['vat_rate'],
                    'line_total'   => $lineTotal,
                ]);

                // Decrement stock
                if ($item['type'] === 'product' && isset($item['id'])) {
                    Product::find($item['id'])?->decrement('stock_qty', $item['qty']);
                }
            }

            $sale->load('items');
            $sale->recalculate();

            if ($data['paid_amount'] === null || $data['paid_amount'] === 0) {
                $sale->update(['paid_amount' => $sale->total]);
            }
        });

        return response()->json([
            'success' => true,
            'sale'    => $sale->load('items', 'customer'),
            'receipt' => $this->buildReceiptData($sale),
        ]);
    }

    // ─── Receipt data for Star printer ───────────────────────────────────────

    private function buildReceiptData(Sale $sale): array
    {
        $tenant = tenant();

        return [
            'tenant_name'    => $tenant->company_name ?? $tenant->name,
            'tenant_address' => trim(($tenant->address ?? '') . ', ' . ($tenant->city ?? '')),
            'tenant_phone'   => $tenant->phone ?? '',
            'tenant_email'   => $tenant->email ?? '',
            'tenant_uid'     => $tenant->uid_number ?? '',
            'invoice_number' => $sale->invoice_number,
            'issued_at'      => $sale->issued_at->format('d.m.Y H:i'),
            'customer'       => $sale->customer?->full_name,
            'items'          => $sale->items->map(fn($i) => [
                'description' => $i->description,
                'qty'         => (float) $i->qty,
                'unit_price'  => (float) $i->unit_price,
                'discount_pct' => (float) $i->discount_pct,
                'vat_rate'    => (float) $i->vat_rate,
                'line_total'  => (float) $i->line_total,
            ])->toArray(),
            'subtotal'       => (float) $sale->subtotal,
            'vat_amount'     => (float) $sale->vat_amount,
            'discount_amount' => (float) $sale->discount_amount,
            'total'          => (float) $sale->total,
            'paid_amount'    => (float) $sale->paid_amount,
            'change'         => max(0, $sale->paid_amount - $sale->total),
            'payment_method' => $sale->payment_method,
        ];
    }

    // ─── Sales history ───────────────────────────────────────────────────────

    public function sales(Request $request)
    {
        $sales = Sale::with('customer', 'items')
            ->where('type', 'sale')
            ->when($request->search, fn($q) => $q->where('invoice_number', 'like', "%{$request->search}%")
                ->orWhereHas('customer', fn($c) => $c->where('first_name', 'like', "%{$request->search}%")
                    ->orWhere('last_name', 'like', "%{$request->search}%")))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->payment, fn($q) => $q->where('payment_method', $request->payment))
            ->orderByDesc('issued_at')
            ->paginate(25)
            ->withQueryString();

        $todayTotal = Sale::where('type', 'sale')->where('status', 'paid')
            ->whereDate('issued_at', today())->sum('total');

        return Inertia::render('Pos/Sales', [
            'sales'      => $sales,
            'todayTotal' => (float) $todayTotal,
            'filters'    => $request->only(['search', 'status', 'payment']),
        ]);
    }

    public function showSale(Sale $sale)
    {
        return Inertia::render('Pos/SaleShow', [
            'sale'    => $sale->load('items', 'customer'),
            'receipt' => $this->buildReceiptData($sale),
        ]);
    }

    // ─── SumUp payment initiation ─────────────────────────────────────────────

    public function sumupCheckout(Request $request)
    {
        $data = $request->validate([
            'amount'      => 'required|numeric|min:0.01',
            'description' => 'required|string|max:100',
        ]);

        $tenant = tenant();
        if (! $tenant->sumup_api_key) {
            return response()->json(['error' => 'SumUp non configurato'], 422);
        }

        try {
            $res = Http::withToken($tenant->sumup_api_key)
                ->post('https://api.sumup.com/v0.1/checkouts', [
                    'checkout_reference' => 'POS-' . uniqid(),
                    'amount'             => round((float) $data['amount'], 2),
                    'currency'           => 'CHF',
                    'merchant_code'      => $tenant->sumup_merchant_code,
                    'description'        => $data['description'],
                    'pay_to_email'       => $tenant->email,
                ]);

            if ($res->successful()) {
                return response()->json(['checkout_id' => $res->json('id'), 'status' => 'pending']);
            }

            return response()->json(['error' => $res->json('message') ?? 'Errore SumUp'], 422);
        } catch (\Throwable $e) {
            Log::error('SumUp checkout error: ' . $e->getMessage());
            return response()->json(['error' => 'Impossibile connettersi a SumUp'], 500);
        }
    }

    public function sumupStatus(Request $request, string $checkoutId)
    {
        $tenant = tenant();
        $res = Http::withToken($tenant->sumup_api_key)
            ->get("https://api.sumup.com/v0.1/checkouts/{$checkoutId}");

        return response()->json($res->json());
    }

    // ─── WooCommerce product sync ─────────────────────────────────────────────

    public function wooSync(Request $request)
    {
        $data = $request->validate([
            'woo_url'    => 'required|url',
            'woo_key'    => 'required|string',
            'woo_secret' => 'required|string',
        ]);

        try {
            $res = Http::withBasicAuth($data['woo_key'], $data['woo_secret'])
                ->get(rtrim($data['woo_url'], '/') . '/wp-json/wc/v3/products', [
                    'per_page' => 100,
                    'status'   => 'publish',
                ]);

            if (! $res->successful()) {
                return back()->withErrors(['woo' => 'Impossibile connettersi a WooCommerce: ' . $res->status()]);
            }

            $synced = 0;
            foreach ($res->json() as $wooProduct) {
                $price    = $wooProduct['price'] ?? $wooProduct['regular_price'] ?? 0;
                $sku      = $wooProduct['sku'] ?? null;
                $stock    = $wooProduct['stock_quantity'] ?? 0;

                $product = Product::updateOrCreate(
                    ['sku' => $sku ?: 'WOO-' . $wooProduct['id']],
                    [
                        'name'       => $wooProduct['name'],
                        'sku'        => $sku ?: 'WOO-' . $wooProduct['id'],
                        'type'       => 'product',
                        'sell_price' => (float) $price,
                        'stock_qty'  => (int) $stock,
                        'supplier'   => 'WooCommerce',
                    ]
                );
                $synced++;
            }

            return back()->with('success', "Sincronizzati {$synced} prodotti da WooCommerce.");
        } catch (\Throwable $e) {
            Log::error('WooCommerce sync error: ' . $e->getMessage());
            return back()->withErrors(['woo' => 'Errore durante la sincronizzazione: ' . $e->getMessage()]);
        }
    }

    // ─── Barcode lookup ──────────────────────────────────────────────────────

    public function barcodeSearch(Request $request)
    {
        $code = $request->string('code')->toString();
        $product = Product::where('barcode', $code)->orWhere('sku', $code)->first();

        if (! $product) {
            return response()->json(['found' => false], 404);
        }

        return response()->json(['found' => true, 'product' => $product]);
    }

    // ─── Void / refund ───────────────────────────────────────────────────────

    public function voidSale(Sale $sale)
    {
        if ($sale->type !== 'sale') {
            return back()->withErrors(['void' => 'Solo le vendite possono essere annullate.']);
        }

        DB::transaction(function () use ($sale) {
            // Restore stock
            foreach ($sale->items as $item) {
                if ($item->product_id) {
                    Product::find($item->product_id)?->increment('stock_qty', $item->qty);
                }
            }
            $sale->update(['status' => 'cancelled']);
        });

        return back()->with('success', 'Vendita annullata e stock ripristinato.');
    }
}
