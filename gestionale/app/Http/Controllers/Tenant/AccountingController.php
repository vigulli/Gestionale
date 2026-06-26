<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\CashMovement;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AccountingController extends Controller
{
    // ─── Dashboard ───────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $year  = $request->integer('year', now()->year);
        $month = $request->integer('month', 0); // 0 = tutti

        $stats = $this->buildStats($year, $month);

        return Inertia::render('Accounting/Index', [
            'stats'      => $stats,
            'year'       => $year,
            'month'      => $month,
            'categories' => ExpenseCategory::orderBy('sort_order')->get(),
        ]);
    }

    private function buildStats(int $year, int $month): array
    {
        $expenseQ = Expense::whereYear('expense_date', $year);
        $incomeQ  = Sale::where('type', 'sale')->where('status', 'paid')->whereYear('issued_at', $year);
        $cashInQ  = CashMovement::where('type', 'in')->whereYear('movement_date', $year);
        $cashOutQ = CashMovement::where('type', 'out')->whereYear('movement_date', $year);

        if ($month > 0) {
            $expenseQ->whereMonth('expense_date', $month);
            $incomeQ->whereMonth('issued_at', $month);
            $cashInQ->whereMonth('movement_date', $month);
            $cashOutQ->whereMonth('movement_date', $month);
        }

        $totalIncome   = $incomeQ->sum('total') + $cashInQ->sum('amount');
        $totalExpenses = $expenseQ->sum('amount') + $cashOutQ->sum('amount');
        $margin        = $totalIncome - $totalExpenses;

        // Monthly breakdown for chart
        $monthly = [];
        for ($m = 1; $m <= 12; $m++) {
            $inc = Sale::where('type', 'sale')->where('status', 'paid')
                ->whereYear('issued_at', $year)->whereMonth('issued_at', $m)->sum('total')
                + CashMovement::where('type', 'in')
                ->whereYear('movement_date', $year)->whereMonth('movement_date', $m)->sum('amount');

            $exp = Expense::whereYear('expense_date', $year)->whereMonth('expense_date', $m)->sum('amount')
                + CashMovement::where('type', 'out')
                ->whereYear('movement_date', $year)->whereMonth('movement_date', $m)->sum('amount');

            $monthly[] = ['month' => $m, 'income' => (float) $inc, 'expenses' => (float) $exp, 'margin' => (float) ($inc - $exp)];
        }

        // Expenses by category
        $byCategory = Expense::whereYear('expense_date', $year)
            ->when($month > 0, fn($q) => $q->whereMonth('expense_date', $month))
            ->select('expense_category_id', DB::raw('SUM(amount) as total'))
            ->groupBy('expense_category_id')
            ->with('category')
            ->get()
            ->map(fn($r) => [
                'name'  => $r->category?->name ?? 'Senza categoria',
                'color' => $r->category?->color ?? '#94a3b8',
                'total' => (float) $r->total,
            ]);

        return compact('totalIncome', 'totalExpenses', 'margin', 'monthly', 'byCategory');
    }

    // ─── Expenses ────────────────────────────────────────────────────────────

    public function expenses(Request $request)
    {
        $expenses = Expense::with('category')
            ->when($request->search, fn($q) => $q->where('description', 'like', "%{$request->search}%")
                ->orWhere('supplier', 'like', "%{$request->search}%"))
            ->when($request->category, fn($q) => $q->where('expense_category_id', $request->category))
            ->when($request->year,  fn($q) => $q->whereYear('expense_date', $request->year))
            ->when($request->month && $request->month > 0, fn($q) => $q->whereMonth('expense_date', $request->month))
            ->orderByDesc('expense_date')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Accounting/Expenses', [
            'expenses'   => $expenses,
            'categories' => ExpenseCategory::orderBy('sort_order')->get(),
            'filters'    => $request->only(['search', 'category', 'year', 'month']),
        ]);
    }

    public function storeExpense(Request $request)
    {
        $data = $request->validate([
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'description'         => 'required|string|max:255',
            'supplier'            => 'nullable|string|max:255',
            'amount'              => 'required|numeric|min:0',
            'vat_amount'          => 'nullable|numeric|min:0',
            'vat_rate'            => 'nullable|numeric|min:0|max:100',
            'payment_method'      => 'required|in:cash,card,transfer,check',
            'reference'           => 'nullable|string|max:100',
            'is_recurring'        => 'boolean',
            'recurring_period'    => 'nullable|in:monthly,quarterly,yearly',
            'notes'               => 'nullable|string',
            'expense_date'        => 'required|date',
            'receipt'             => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        if ($request->hasFile('receipt')) {
            $data['receipt_path'] = $request->file('receipt')->store('receipts', 'public');
        }

        $data['user_id'] = auth()->id();
        Expense::create($data);

        return back()->with('success', 'Spesa registrata.');
    }

    public function updateExpense(Request $request, Expense $expense)
    {
        $data = $request->validate([
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'description'         => 'required|string|max:255',
            'supplier'            => 'nullable|string|max:255',
            'amount'              => 'required|numeric|min:0',
            'vat_amount'          => 'nullable|numeric|min:0',
            'vat_rate'            => 'nullable|numeric|min:0|max:100',
            'payment_method'      => 'required|in:cash,card,transfer,check',
            'reference'           => 'nullable|string|max:100',
            'is_recurring'        => 'boolean',
            'recurring_period'    => 'nullable|in:monthly,quarterly,yearly',
            'notes'               => 'nullable|string',
            'expense_date'        => 'required|date',
            'receipt'             => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        if ($request->hasFile('receipt')) {
            if ($expense->receipt_path) Storage::disk('public')->delete($expense->receipt_path);
            $data['receipt_path'] = $request->file('receipt')->store('receipts', 'public');
        }

        $expense->update($data);

        return back()->with('success', 'Spesa aggiornata.');
    }

    public function destroyExpense(Expense $expense)
    {
        $expense->delete();
        return back()->with('success', 'Spesa eliminata.');
    }

    // ─── Purchases ───────────────────────────────────────────────────────────

    public function purchases(Request $request)
    {
        $purchases = Purchase::with('items')
            ->when($request->search, fn($q) => $q->where('supplier_name', 'like', "%{$request->search}%")
                ->orWhere('purchase_number', 'like', "%{$request->search}%"))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderByDesc('ordered_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Accounting/Purchases', [
            'purchases' => $purchases,
            'statuses'  => Purchase::statuses(),
            'filters'   => $request->only(['search', 'status']),
        ]);
    }

    public function storePurchase(Request $request)
    {
        $data = $request->validate([
            'supplier_name'  => 'required|string|max:255',
            'supplier_email' => 'nullable|email|max:255',
            'supplier_phone' => 'nullable|string|max:50',
            'payment_method' => 'nullable|in:cash,card,transfer,check',
            'invoice_ref'    => 'nullable|string|max:100',
            'notes'          => 'nullable|string',
            'ordered_at'     => 'required|date',
            'due_at'         => 'nullable|date',
            'items'          => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.qty'         => 'required|numeric|min:0.01',
            'items.*.unit_price'  => 'required|numeric|min:0',
            'items.*.vat_rate'    => 'nullable|numeric|min:0|max:100',
            'items.*.product_id'  => 'nullable|exists:products,id',
        ]);

        $data['user_id'] = auth()->id();
        $purchase = Purchase::create($data);

        foreach ($data['items'] as $item) {
            $item['line_total'] = $item['qty'] * $item['unit_price'];
            $item['vat_rate']   = $item['vat_rate'] ?? 0;
            $purchase->items()->create($item);
        }

        $purchase->recalculate();

        return redirect()->route('accounting.purchases')->with('success', 'Ordine creato.');
    }

    public function showPurchase(Purchase $purchase)
    {
        return Inertia::render('Accounting/PurchaseShow', [
            'purchase' => $purchase->load('items.product', 'user'),
            'statuses' => Purchase::statuses(),
            'paymentStatuses' => Purchase::paymentStatuses(),
        ]);
    }

    public function updatePurchaseStatus(Request $request, Purchase $purchase)
    {
        $data = $request->validate([
            'status'         => 'sometimes|in:ordered,received,partial,cancelled',
            'payment_status' => 'sometimes|in:unpaid,paid,partial',
            'paid_amount'    => 'sometimes|numeric|min:0',
            'received_at'    => 'sometimes|nullable|date',
        ]);

        $purchase->update($data);

        // Update stock if fully/partially received
        if (in_array($data['status'] ?? '', ['received', 'partial'])) {
            foreach ($purchase->items as $item) {
                if ($item->product_id && $item->received_qty > 0) {
                    $item->product->increment('stock_qty', $item->received_qty);
                }
            }
        }

        return back()->with('success', 'Ordine aggiornato.');
    }

    public function updatePurchaseItem(Request $request, Purchase $purchase, PurchaseItem $item)
    {
        $data = $request->validate([
            'received'     => 'boolean',
            'received_qty' => 'numeric|min:0',
        ]);

        $item->update($data);

        return back()->with('success', 'Articolo aggiornato.');
    }

    // ─── Cash Movements ──────────────────────────────────────────────────────

    public function cashMovements(Request $request)
    {
        $movements = CashMovement::with('category')
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->year,  fn($q) => $q->whereYear('movement_date', $request->year))
            ->when($request->month && $request->month > 0, fn($q) => $q->whereMonth('movement_date', $request->month))
            ->orderByDesc('movement_date')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Accounting/CashMovements', [
            'movements'  => $movements,
            'categories' => ExpenseCategory::orderBy('sort_order')->get(),
            'filters'    => $request->only(['type', 'year', 'month']),
        ]);
    }

    public function storeCashMovement(Request $request)
    {
        $data = $request->validate([
            'type'                => 'required|in:in,out',
            'description'         => 'required|string|max:255',
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'amount'              => 'required|numeric|min:0.01',
            'payment_method'      => 'required|in:cash,card,transfer,check',
            'reference'           => 'nullable|string|max:100',
            'notes'               => 'nullable|string',
            'movement_date'       => 'required|date',
        ]);

        $data['user_id'] = auth()->id();
        CashMovement::create($data);

        return back()->with('success', 'Movimento registrato.');
    }

    public function destroyCashMovement(CashMovement $movement)
    {
        $movement->delete();
        return back()->with('success', 'Movimento eliminato.');
    }

    // ─── Categories ──────────────────────────────────────────────────────────

    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:100',
            'color'      => 'required|string|max:20',
            'type'       => 'required|in:expense,income',
            'sort_order' => 'integer',
        ]);

        ExpenseCategory::create($data);
        return back()->with('success', 'Categoria creata.');
    }

    public function destroyCategory(ExpenseCategory $category)
    {
        $category->delete();
        return back()->with('success', 'Categoria eliminata.');
    }
}
