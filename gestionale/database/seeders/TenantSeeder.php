<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = [
            [
                'id' => 'i-lab',
                'name' => 'i-Lab',
                'primary_color' => '#6366f1',
                'secondary_color' => '#818cf8',
                'invoice_prefix' => 'IL-',
                'country' => 'CH',
                'vat_rate_standard' => 8.1,
                'vat_rate_reduced' => 2.6,
                'module_repairs' => true,
                'module_print_orders' => false,
                'module_pos' => true,
                'module_inventory' => true,
                'module_loyalty' => false,
            ],
            [
                'id' => 'nipotetech',
                'name' => 'NipoteTech',
                'primary_color' => '#10b981',
                'secondary_color' => '#34d399',
                'invoice_prefix' => 'NT-',
                'country' => 'CH',
                'vat_rate_standard' => 8.1,
                'vat_rate_reduced' => 2.6,
                'module_repairs' => true,
                'module_print_orders' => false,
                'module_pos' => true,
                'module_inventory' => true,
                'module_loyalty' => false,
            ],
            [
                'id' => 'dtflab',
                'name' => 'DTF Lab',
                'primary_color' => '#f59e0b',
                'secondary_color' => '#fbbf24',
                'invoice_prefix' => 'DTF-',
                'country' => 'CH',
                'vat_rate_standard' => 8.1,
                'vat_rate_reduced' => 2.6,
                'module_repairs' => true,
                'module_print_orders' => true,
                'module_pos' => true,
                'module_inventory' => true,
                'module_loyalty' => false,
            ],
        ];

        foreach ($tenants as $data) {
            $tenant = Tenant::firstOrCreate(['id' => $data['id']], $data);

            $tenant->domains()->firstOrCreate([
                'domain' => $data['id'] . '.' . config('app.domain', 'gestionale.local'),
            ]);
        }
    }
}
