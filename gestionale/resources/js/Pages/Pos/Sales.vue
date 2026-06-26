<template>
  <AppLayout title="Storico Vendite">
    <div class="flex flex-wrap gap-3 mb-6 items-center">
      <input v-model="search" @keyup.enter="load" type="text" placeholder="Cerca per fattura o cliente…" class="input-sm flex-1 min-w-48" />
      <select v-model="filterPayment" @change="load" class="input-sm">
        <option value="">Tutti i metodi</option>
        <option value="cash">Contanti</option>
        <option value="card">Carta</option>
        <option value="sumup">SumUp</option>
        <option value="transfer">Bonifico</option>
      </select>
      <Link :href="route('pos.index')" class="btn-primary ml-auto">🛒 Apri POS</Link>
    </div>

    <!-- Today totale -->
    <div class="card p-4 mb-4 flex items-center justify-between">
      <span class="text-sm text-gray-500">Totale vendite oggi</span>
      <span class="text-2xl font-bold text-green-600">{{ fmt(todayTotal) }}</span>
    </div>

    <div class="card overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="th">N° Fattura</th>
            <th class="th">Data/Ora</th>
            <th class="th">Cliente</th>
            <th class="th">Metodo</th>
            <th class="th text-right">Totale</th>
            <th class="th text-right">Articoli</th>
            <th class="th"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="s in sales.data" :key="s.id" class="tr-hover">
            <td class="td font-mono text-xs">{{ s.invoice_number }}</td>
            <td class="td text-gray-500">{{ fmtDt(s.issued_at) }}</td>
            <td class="td">{{ s.customer ? `${s.customer.first_name} ${s.customer.last_name}` : '—' }}</td>
            <td class="td capitalize">{{ s.payment_method }}</td>
            <td class="td text-right font-medium text-green-700">{{ fmt(s.total) }}</td>
            <td class="td text-right text-gray-400">{{ s.items.length }}</td>
            <td class="td">
              <Link :href="route('pos.sales.show', s.id)" class="text-indigo-500 hover:underline text-xs">Dettaglio →</Link>
            </td>
          </tr>
          <tr v-if="!sales.data.length">
            <td colspan="7" class="td text-center text-gray-400 py-8">Nessuna vendita trovata</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="sales.last_page > 1" class="flex justify-center gap-2 mt-4">
      <Link v-for="link in sales.links" :key="link.label"
            :href="link.url ?? '#'"
            :class="['btn-outline btn-sm', link.active && 'btn-active', !link.url && 'opacity-40 cursor-default']"
            v-html="link.label" />
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  sales:      Object,
  todayTotal: Number,
  filters:    Object,
})

const search        = ref(props.filters.search ?? '')
const filterPayment = ref(props.filters.payment ?? '')

function load() {
  router.get(route('pos.sales'), { search: search.value, payment: filterPayment.value }, { preserveState: true, replace: true })
}

const fmt   = v => new Intl.NumberFormat('it-CH', { style: 'currency', currency: 'CHF' }).format(v ?? 0)
const fmtDt = d => d ? new Date(d).toLocaleString('it-CH', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—'
</script>
