<template>
  <div class="min-h-screen flex" :style="cssVars">
    <!-- Sidebar -->
    <aside class="w-64 bg-sidebar text-white flex flex-col shadow-xl">
      <!-- Logo tenant -->
      <div class="flex items-center gap-3 px-6 py-5 border-b border-white/10">
        <img v-if="tenant.logo_url" :src="tenant.logo_url" class="h-8 w-auto object-contain" :alt="tenant.name" />
        <span v-else class="text-lg font-bold tracking-tight">{{ tenant.name }}</span>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        <NavItem v-if="tenant.modules.repairs" :href="route('repairs.index')" icon="wrench">
          Riparazioni
        </NavItem>
        <NavItem v-if="tenant.modules.print_orders" :href="route('print-orders.index')" icon="print">
          Ordini Stampa
        </NavItem>
        <NavItem v-if="tenant.modules.pos" :href="route('sales.index')" icon="shopping-cart">
          Vendite / POS
        </NavItem>
        <NavItem v-if="tenant.modules.inventory" :href="route('products.index')" icon="cube">
          Inventario
        </NavItem>
        <NavItem :href="route('customers.index')" icon="users">
          Clienti
        </NavItem>
        <NavItem :href="route('invoices.index')" icon="document-text">
          Fatture
        </NavItem>
        <NavItem :href="route('accounting.index')" icon="banknotes">
          Contabilità
        </NavItem>
        <NavItem :href="route('reports.index')" icon="chart-bar">
          Report
        </NavItem>

        <div class="pt-4 border-t border-white/10 mt-4">
          <NavItem :href="route('settings.index')" icon="cog">
            Impostazioni
          </NavItem>
        </div>
      </nav>

      <!-- User info -->
      <div class="px-4 py-3 border-t border-white/10 text-sm text-white/70">
        {{ $page.props.auth.user?.name }}
      </div>
    </aside>

    <!-- Main content -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Top bar -->
      <header class="h-14 bg-white border-b border-gray-200 flex items-center px-6 gap-4 shadow-sm">
        <h1 class="text-gray-800 font-semibold text-base flex-1">{{ title }}</h1>
        <slot name="header-actions" />
      </header>

      <!-- Page -->
      <main class="flex-1 p-6 bg-gray-50 overflow-auto">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import NavItem from '@/Components/NavItem.vue'

defineProps({
  title: String,
})

const page = usePage()
const tenant = computed(() => page.props.tenant)

const cssVars = computed(() => ({
  '--color-primary': tenant.value?.primary_color ?? '#6366f1',
  '--color-secondary': tenant.value?.secondary_color ?? '#818cf8',
}))
</script>

<style scoped>
.bg-sidebar {
  background-color: var(--color-primary);
}
</style>
