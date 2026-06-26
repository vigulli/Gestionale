<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Customers
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('zip')->nullable();
            $table->string('country')->default('CH');
            $table->string('tax_number')->nullable(); // partita IVA / UID svizzero
            $table->string('category')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('loyalty_points', 10, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // Service catalog (predefined services with price tiers)
        Schema::create('service_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color')->default('#6366f1');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_category_id')->nullable()->constrained('service_categories')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type')->default('repair'); // repair | print | sale | subscription
            $table->decimal('price_min', 10, 2)->nullable(); // fascia prezzo min
            $table->decimal('price_max', 10, 2)->nullable(); // fascia prezzo max
            $table->decimal('price_default', 10, 2)->nullable();
            $table->string('vat_rate')->default('7.7'); // IVA svizzera
            $table->boolean('active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Inventory
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable()->unique();
            $table->string('type')->default('part'); // part | product | consumable
            $table->text('description')->nullable();
            $table->decimal('purchase_price', 10, 2)->default(0);
            $table->decimal('sell_price', 10, 2)->default(0);
            $table->string('vat_rate')->default('7.7');
            $table->integer('stock_qty')->default(0);
            $table->integer('stock_alert')->default(5);
            $table->string('supplier')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Repairs
        Schema::create('repairs', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->string('device_brand')->nullable();
            $table->string('device_model')->nullable();
            $table->string('device_serial')->nullable();
            $table->string('device_password')->nullable();
            $table->text('problem_description');
            $table->text('technician_notes')->nullable();
            $table->string('status')->default('received'); // received|diagnosed|in_progress|waiting_parts|ready|delivered|cancelled
            $table->string('priority')->default('normal'); // low|normal|high|urgent
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('estimated_cost', 10, 2)->nullable();
            $table->decimal('final_cost', 10, 2)->nullable();
            $table->string('vat_rate')->default('7.7');
            $table->boolean('customer_notified')->default(false);
            $table->string('tracking_token')->unique()->nullable(); // token pubblico per QR tracking
            $table->timestamp('received_at')->useCurrent();
            $table->timestamp('deadline_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Repair status history (per tracking timeline pubblica)
        Schema::create('repair_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_id')->constrained('repairs')->cascadeOnDelete();
            $table->string('status');
            $table->text('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at')->useCurrent();
            $table->timestamps();
        });

        Schema::create('repair_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_id')->constrained('repairs')->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('description');
            $table->decimal('qty', 10, 2)->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('vat_rate', 5, 2)->default(7.7);
            $table->timestamps();
        });

        // Print Orders (dtflab)
        Schema::create('print_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->string('status')->default('pending'); // pending|confirmed|printing|ready|delivered|cancelled
            $table->string('garment_type')->nullable(); // maglietta, felpa, polo, divisa...
            $table->string('garment_color')->nullable();
            $table->string('garment_size')->nullable();
            $table->integer('quantity')->default(1);
            $table->text('print_description')->nullable();
            $table->string('print_file_path')->nullable(); // uploaded artwork
            $table->string('print_position')->nullable(); // fronte, retro, manica...
            $table->string('print_size_cm')->nullable();
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2)->default(0);
            $table->string('vat_rate')->default('7.7');
            $table->text('notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tracking_token')->unique()->nullable();
            $table->timestamp('deadline_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Sales / POS
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('type')->default('sale'); // sale|quote|credit_note
            $table->string('status')->default('draft'); // draft|sent|paid|cancelled
            $table->string('payment_method')->nullable(); // cash|card|sumup|transfer|mixed
            $table->string('payment_reference')->nullable();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('vat_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->string('vat_mode')->default('inclusive'); // inclusive|exclusive|margin
            $table->text('notes')->nullable();
            $table->string('pdf_path')->nullable();
            $table->boolean('qr_bill_generated')->default(false);
            // Preventivo online: accettazione/rifiuto via link
            $table->string('quote_token')->unique()->nullable();
            $table->string('quote_status')->nullable(); // null|accepted|rejected
            $table->timestamp('quote_responded_at')->nullable();
            $table->string('quote_response_ip')->nullable();
            $table->text('quote_rejection_reason')->nullable();
            $table->morphs('source'); // polymorphic: repair | print_order | manual
            $table->timestamp('issued_at')->useCurrent();
            $table->timestamp('due_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->string('description');
            $table->decimal('qty', 10, 2)->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('discount_pct', 5, 2)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(7.7);
            $table->decimal('line_total', 10, 2);
            $table->timestamps();
        });

        // Label templates (etichette riparazioni e ricambi magazzino)
        Schema::create('label_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // repair | product | print_order
            $table->integer('width_mm')->default(38);
            $table->integer('height_mm')->default(90);
            $table->json('fields'); // campi da stampare con posizioni
            $table->boolean('show_qr')->default(true);
            $table->boolean('show_barcode')->default(false);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // Notifications log
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('channel'); // sms|whatsapp|email
            $table->string('recipient');
            $table->text('message');
            $table->string('status')->default('pending'); // pending|sent|failed
            $table->string('provider')->nullable(); // bulkgate|smtp
            $table->json('provider_response')->nullable();
            $table->morphs('notifiable'); // repair | sale | print_order
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('label_templates');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('print_orders');
        Schema::dropIfExists('repair_items');
        Schema::dropIfExists('repairs');
        Schema::dropIfExists('products');
        Schema::dropIfExists('services');
        Schema::dropIfExists('service_categories');
        Schema::dropIfExists('customers');
    }
};
