<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Sale;
use App\Services\InvoicePdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    public function __construct(private InvoicePdfService $pdf) {}

    // ─── List ─────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $sales = Sale::with('customer')
            ->whereIn('type', ['sale', 'credit_note'])
            ->when($request->search, fn($q) => $q->where('invoice_number', 'like', "%{$request->search}%")
                ->orWhereHas('customer', fn($c) => $c->where('first_name', 'like', "%{$request->search}%")
                    ->orWhere('last_name', 'like', "%{$request->search}%")))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->year,   fn($q) => $q->whereYear('issued_at', $request->year))
            ->orderByDesc('issued_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Invoices/Index', [
            'sales'   => $sales,
            'filters' => $request->only(['search', 'status', 'year']),
            'hasIban' => (bool) tenant()->iban,
        ]);
    }

    // ─── Show ─────────────────────────────────────────────────────────────────

    public function show(Sale $sale)
    {
        return Inertia::render('Invoices/Show', [
            'sale'    => $sale->load('items', 'customer'),
            'hasIban' => (bool) tenant()->iban,
            'hasQrBill' => (bool) tenant()->iban,
        ]);
    }

    // ─── Create manual invoice ────────────────────────────────────────────────

    public function create()
    {
        return Inertia::render('Invoices/Create', [
            'customers' => Customer::orderBy('last_name')->get(['id', 'first_name', 'last_name', 'email']),
            'vatRates'  => [8.1, 2.6, 0],
            'nextNumber' => tenant()->invoice_prefix . str_pad(tenant()->invoice_next_number, 6, '0', STR_PAD_LEFT),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id'    => 'nullable|exists:customers,id',
            'type'           => 'required|in:sale,credit_note',
            'status'         => 'required|in:draft,sent,paid,cancelled',
            'payment_method' => 'nullable|in:cash,card,sumup,transfer,mixed',
            'due_at'         => 'nullable|date',
            'notes'          => 'nullable|string',
            'discount_amount' => 'nullable|numeric|min:0',
            'vat_mode'       => 'nullable|in:inclusive,exclusive',
            'items'          => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.qty'         => 'required|numeric|min:0.01',
            'items.*.unit_price'  => 'required|numeric|min:0',
            'items.*.vat_rate'    => 'required|numeric|min:0|max:100',
            'items.*.discount_pct' => 'nullable|numeric|min:0|max:100',
        ]);

        $tenant = tenant();
        $next   = $tenant->invoice_next_number ?? 1;
        $invoiceNumber = ($tenant->invoice_prefix ?? 'INV-') . str_pad($next, 6, '0', STR_PAD_LEFT);
        $tenant->update(['invoice_next_number' => $next + 1]);

        $sale = Sale::create([
            ...$data,
            'invoice_number' => $invoiceNumber,
            'source_type'    => 'manual',
            'source_id'      => 0,
            'issued_at'      => now(),
        ]);

        foreach ($data['items'] as $item) {
            $lineTotal = $item['qty'] * $item['unit_price'] * (1 - ($item['discount_pct'] ?? 0) / 100);
            $sale->items()->create([...$item, 'line_total' => $lineTotal, 'discount_pct' => $item['discount_pct'] ?? 0]);
        }

        $sale->load('items');
        $sale->recalculate();

        return redirect()->route('invoices.show', $sale)->with('success', 'Fattura creata.');
    }

    // ─── Update status ────────────────────────────────────────────────────────

    public function updateStatus(Request $request, Sale $sale)
    {
        $data = $request->validate([
            'status'           => 'required|in:draft,sent,paid,cancelled',
            'payment_method'   => 'nullable|in:cash,card,sumup,transfer,mixed',
            'payment_reference' => 'nullable|string|max:100',
        ]);
        $sale->update($data);
        return back()->with('success', 'Stato aggiornato.');
    }

    // ─── PDF generation ───────────────────────────────────────────────────────

    public function pdf(Sale $sale)
    {
        $pdfContent = $this->pdf->generate($sale);
        $filename   = $sale->invoice_number . '.pdf';

        // Cache on disk
        $path = 'invoices/' . $filename;
        Storage::disk('public')->put($path, $pdfContent);
        $sale->update(['pdf_path' => $path]);

        return response($pdfContent, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }

    public function downloadPdf(Sale $sale)
    {
        $pdfContent = $this->pdf->generate($sale);
        $filename   = $sale->invoice_number . '.pdf';

        return response($pdfContent, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
