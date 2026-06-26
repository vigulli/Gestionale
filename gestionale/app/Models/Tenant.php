<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    protected $fillable = [
        'id', 'name',
        'logo_path', 'favicon_path', 'primary_color', 'secondary_color',
        'invoice_background_path', 'invoice_layout', 'invoice_prefix', 'invoice_next_number',
        'company_name', 'address', 'city', 'zip', 'country',
        'phone', 'email', 'website', 'uid_number', 'iban', 'bank_name',
        'smtp_host', 'smtp_port', 'smtp_user', 'smtp_password', 'smtp_encryption',
        'smtp_from_name', 'smtp_from_email',
        'bulkgate_app_id', 'bulkgate_app_token', 'bulkgate_sender_id', 'bulkgate_whatsapp_enabled',
        'module_repairs', 'module_print_orders', 'module_pos', 'module_inventory', 'module_loyalty',
        'sumup_api_key', 'sumup_merchant_code',
        'vat_rate_standard', 'vat_rate_reduced',
    ];

    protected $casts = [
        'invoice_layout' => 'array',
        'module_repairs' => 'boolean',
        'module_print_orders' => 'boolean',
        'module_pos' => 'boolean',
        'module_inventory' => 'boolean',
        'module_loyalty' => 'boolean',
        'bulkgate_whatsapp_enabled' => 'boolean',
        'vat_rate_standard' => 'decimal:2',
        'vat_rate_reduced' => 'decimal:2',
    ];

    public static function getCustomColumns(): array
    {
        return [
            'id', 'name',
            'logo_path', 'favicon_path', 'primary_color', 'secondary_color',
            'invoice_background_path', 'invoice_layout', 'invoice_prefix', 'invoice_next_number',
            'company_name', 'address', 'city', 'zip', 'country',
            'phone', 'email', 'website', 'uid_number', 'iban', 'bank_name',
            'smtp_host', 'smtp_port', 'smtp_user', 'smtp_password', 'smtp_encryption',
            'smtp_from_name', 'smtp_from_email',
            'bulkgate_app_id', 'bulkgate_app_token', 'bulkgate_sender_id', 'bulkgate_whatsapp_enabled',
            'module_repairs', 'module_print_orders', 'module_pos', 'module_inventory', 'module_loyalty',
            'sumup_api_key', 'sumup_merchant_code',
            'vat_rate_standard', 'vat_rate_reduced',
        ];
    }
}
