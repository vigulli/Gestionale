<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTenantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->string('id')->primary(); // slug: i-lab, nipotetech, dtflab

            // Branding
            $table->string('name');
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->string('primary_color')->default('#6366f1');
            $table->string('secondary_color')->default('#818cf8');

            // Invoice customization
            $table->string('invoice_background_path')->nullable(); // PNG layout sfondo
            $table->json('invoice_layout')->nullable(); // posizioni logo, testo, QR
            $table->string('invoice_prefix')->nullable(); // es. IL-, NT-, DTF-
            $table->integer('invoice_next_number')->default(1);

            // Business info (for invoices)
            $table->string('company_name')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('zip')->nullable();
            $table->string('country')->default('CH');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('uid_number')->nullable(); // UID svizzero (es. CHE-123.456.789)
            $table->string('iban')->nullable(); // per QR-bill svizzero
            $table->string('bank_name')->nullable();

            // SMTP per tenant
            $table->string('smtp_host')->nullable();
            $table->string('smtp_port')->nullable();
            $table->string('smtp_user')->nullable();
            $table->string('smtp_password')->nullable();
            $table->string('smtp_encryption')->default('tls');
            $table->string('smtp_from_name')->nullable();
            $table->string('smtp_from_email')->nullable();

            // BulkGate per tenant
            $table->string('bulkgate_app_id')->nullable();
            $table->string('bulkgate_app_token')->nullable();
            $table->string('bulkgate_sender_id')->nullable();
            $table->boolean('bulkgate_whatsapp_enabled')->default(false);

            // Modules enabled per tenant
            $table->boolean('module_repairs')->default(true);
            $table->boolean('module_print_orders')->default(false);
            $table->boolean('module_pos')->default(true);
            $table->boolean('module_inventory')->default(true);
            $table->boolean('module_loyalty')->default(false);

            // SumUp per tenant
            $table->string('sumup_api_key')->nullable();
            $table->string('sumup_merchant_code')->nullable();

            // VAT settings
            $table->decimal('vat_rate_standard', 5, 2)->default(8.1); // IVA CH 2024
            $table->decimal('vat_rate_reduced', 5, 2)->default(2.6);

            $table->timestamps();
            $table->json('data')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
}
