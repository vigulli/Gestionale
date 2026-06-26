<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Categorie spese
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color')->default('#6366f1');
            $table->string('type')->default('expense'); // expense | income
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Spese e uscite
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->string('supplier')->nullable();
            $table->decimal('amount', 10, 2);
            $table->decimal('vat_amount', 10, 2)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->string('payment_method')->default('transfer'); // cash|card|transfer|check
            $table->string('reference')->nullable(); // n° fattura fornitore
            $table->string('receipt_path')->nullable(); // file ricevuta/fattura
            $table->boolean('is_recurring')->default(false);
            $table->string('recurring_period')->nullable(); // monthly|quarterly|yearly
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->date('expense_date');
            $table->timestamps();
            $table->softDeletes();
        });

        // Acquisti da fornitori (ricambi, materiali, ecc.)
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_number')->unique();
            $table->string('supplier_name');
            $table->string('supplier_email')->nullable();
            $table->string('supplier_phone')->nullable();
            $table->string('status')->default('ordered'); // ordered|received|partial|cancelled
            $table->string('payment_status')->default('unpaid'); // unpaid|paid|partial
            $table->string('payment_method')->nullable();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('vat_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->string('invoice_ref')->nullable(); // n° fattura fornitore
            $table->string('receipt_path')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->date('ordered_at');
            $table->date('received_at')->nullable();
            $table->date('due_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->decimal('qty', 10, 2)->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->decimal('line_total', 10, 2);
            $table->boolean('received')->default(false);
            $table->decimal('received_qty', 10, 2)->default(0);
            $table->timestamps();
        });

        // Pagamenti extra / entrate manuali
        Schema::create('cash_movements', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // in | out
            $table->string('description');
            $table->foreignId('expense_category_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('payment_method')->default('cash');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->date('movement_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_movements');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
    }
};
