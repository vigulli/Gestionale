<template>
  <AppLayout title="Fatture">
    <!-- Warning IBAN -->
    <div v-if="!hasIban" class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-sm text-yellow-800 flex items-center gap-2">
      ⚠️ IBAN non configurato — le fatture non avranno la cedola QR svizzera.
      <Link :href="route('settings.index')" class="underline ml-1">Configura nelle Impostazioni →</Link>
    </div>

    <div class="flex flex-wrap gap-3 mb-6 items-center">
      <input v-model="search" @keyup.enter="load" type="text" placeholder="Cerca per numero o cliente…" class="input-sm flex-1 min-w-48" />
      <select v-model="filterStatus" @change="load" class="input-sm">
        <option value="">Tutti gli stati</option>
        <option value="draft">Bozza</option>
        <option value="sent">Inviata</option>
        <option value="paid">Pagata</option>
        <option value="cancelled">Annullata</option>
      </select>
      <select v-model="filterYear" @change="load" class="input-sm">
        <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
      </select>
      <Link :href="route('invoices.create')" class="btn-primary ml-auto">+ Nuova fattura</Link>
    </div>

    <div class="card overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="th">N° Fattura</th>
            <th class="th">Data</th>
            <th class="th">Cliente</th>
            <th class="th">Stato</th>
            <th class="th">Pagamento</th>
            <th class="th text-right">Totale</th>
            <th class="th"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="s in sales.data" :key="s.id" class="tr-hover">
            <td class="td font-mono text-xs font-semibold">{{ s.invoice_number }}</td>
            <td class="td text-gray-500">{{ fmtDate(s.issued_at) }}</td>
            <td class="td">{{ s.customer ? `${s.customer.first_name} ${s.customer.last_name}` : '—' }}</td>
            <td class="td">
              <Badge :color="statusColor(s.status)">{{ statusLabel(s.status) }}</Badge>
            </td>
            <td class="td capitalize text-gray-500 text-xs">{{ s.payment_method ?? '—' }}</td>
            <td class="td text-right font-medium">{{ fmt(s.total) }}</td>
            <td class="td">
              <div class="flex items-center gap-1">
                <Link :href="route('invoices.show', s.id)" class="icon-btn text-indigo-500 hover:text-indigo-700 text-xs">Apri</Link>
                <a :href="route('invoices.pdf', s.id)" target="_blank" class="icon-btn text-gray-400 hover:text-red-500" title="Visualizza PDF">📄</a>
                <a :href="route('invoices.pdf.download', s.id)" class="icon-btn text-gray-400 hover:text-gray-700" title="Scarica PDF">⬇️</a>
              </div>
            </td>
          </tr>
          <tr v-if="!sales.data.length">
            <td colspan="7" class="td text-center text-gray-400 py-8">Nessuna fattura trovata</td>
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
import { ref, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Badge from '@/Components/Badge.vue'

const props = defineProps({
  sales:   Object,
  filters: Object,
  hasIban: Boolean,
})

const search       = ref(props.filters.search ?? '')
const filterStatus = ref(props.filters.status ?? '')
const filterYear   = ref(props.filters.year ?? new Date().getFullYear())
const years        = computed(() => { const y = new Date().getFullYear(); return [y+1, y, y-1, y-2] })

function load() {
  router.get(route('invoices.index'), { search: search.value, status: filterStatus.value, year: filterYear.value }, { preserveState: true, replace: true })
}

const statusMap = {
  draft:     { label: 'Bozza',     color: 'gray' },
  sent:      { label: 'Inviata',   color: 'blue' },
  paid:      { label: 'Pagata',    color: 'green' },
  cancelled: { label: 'Annullata', color: 'red' },
}
const statusLabel = s => statusMap[s]?.label ?? s
const statusColor = s => statusMap[s]?.color ?? 'gray'

const fmt     = v => new Intl.NumberFormat('it-CH', { style: 'currency', currency: 'CHF' }).format(v ?? 0)
const fmtDate = d => d ? new Date(d).toLocaleDateString('it-CH') : '—'
</script>
