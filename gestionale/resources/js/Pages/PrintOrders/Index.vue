<template>
  <AppLayout title="Ordini Stampa DTF">
    <template #header-actions>
      <Link :href="route('print-orders.create')"
        class="flex items-center gap-2 bg-amber-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-amber-600 transition">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nuovo ordine stampa
      </Link>
    </template>

    <!-- Stats rapide -->
    <div class="grid grid-cols-4 gap-3 mb-4">
      <div v-for="(s, key) in statuses" :key="key"
        class="bg-white rounded-xl border border-gray-100 shadow-sm px-4 py-3">
        <div class="text-xs text-gray-500 uppercase tracking-wide">{{ s.label }}</div>
        <div class="text-2xl font-bold mt-1" :class="badgeText(s.color)">
          {{ orders.data.filter(o => o.status === key).length }}
        </div>
      </div>
    </div>

    <!-- Filtri -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-4 flex flex-wrap gap-3">
      <BarcodeScanner class="w-72" placeholder="Cerca ordine o cliente..."
        @scan="(c) => { filters.search = c; search() }" />
      <input v-model="filters.search" type="text" placeholder="Cerca..."
        class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-48 focus:ring-2 focus:ring-amber-400"
        @input="search" />
      <select v-model="filters.status" @change="search"
        class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
        <option value="">Tutti gli stati</option>
        <option v-for="(s, key) in statuses" :key="key" :value="key">{{ s.label }}</option>
      </select>
      <select v-model="filters.garment_type" @change="search"
        class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
        <option value="">Tutti i capi</option>
        <option v-for="(label, key) in garmentTypes" :key="key" :value="key">{{ label }}</option>
      </select>
      <button v-if="hasFilters" @click="resetFilters" class="text-sm text-gray-500 hover:text-gray-700">Azzera</button>
    </div>

    <!-- Kanban view per stato -->
    <div class="grid grid-cols-3 gap-4 mb-6" v-if="!filters.status">
      <div v-for="col in kanbanCols" :key="col.key"
        class="bg-gray-50 rounded-xl p-3 border border-gray-200">
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-sm font-semibold text-gray-700">{{ col.label }}</h3>
          <span :class="['text-xs px-2 py-0.5 rounded-full font-medium', badgeBg(col.color), badgeText(col.color)]">
            {{ colOrders(col.key).length }}
          </span>
        </div>
        <div class="space-y-2">
          <div v-for="order in colOrders(col.key)" :key="order.id"
            class="bg-white rounded-lg border border-gray-200 p-3 cursor-pointer hover:border-amber-400 hover:shadow-sm transition"
            @click="$inertia.visit(route('print-orders.show', order.id))">
            <div class="flex justify-between items-start mb-1">
              <span class="font-mono text-xs text-amber-600 font-bold">{{ order.order_number }}</span>
              <span v-if="isOverdue(order.deadline_at)" class="text-xs text-red-500 font-medium">⚠ Scaduto</span>
            </div>
            <div class="text-sm font-medium text-gray-800 truncate">
              {{ order.customer.last_name }} {{ order.customer.first_name }}
            </div>
            <div class="text-xs text-gray-500 mt-1">
              {{ garmentTypes[order.garment_type] ?? order.garment_type }} · {{ order.quantity }}pz
            </div>
            <div class="flex justify-between items-center mt-2">
              <span class="text-xs text-gray-400">{{ formatDate(order.deadline_at) }}</span>
              <span class="text-xs font-semibold text-gray-700">CHF {{ Number(order.total_price).toFixed(2) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Lista tabella (quando c'è un filtro attivo) -->
    <div v-if="filters.status || filters.search || filters.garment_type"
      class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
          <tr>
            <th class="px-4 py-3 text-left">Ordine</th>
            <th class="px-4 py-3 text-left">Cliente</th>
            <th class="px-4 py-3 text-left">Capo</th>
            <th class="px-4 py-3 text-center">Qtà</th>
            <th class="px-4 py-3 text-left">Stato</th>
            <th class="px-4 py-3 text-left">Scadenza</th>
            <th class="px-4 py-3 text-right">Totale</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="order in orders.data" :key="order.id"
            class="hover:bg-gray-50 cursor-pointer"
            @click="$inertia.visit(route('print-orders.show', order.id))">
            <td class="px-4 py-3 font-mono font-bold text-amber-600">{{ order.order_number }}</td>
            <td class="px-4 py-3">
              <div class="font-medium">{{ order.customer.last_name }} {{ order.customer.first_name }}</div>
              <div class="text-xs text-gray-400">{{ order.customer.phone }}</div>
            </td>
            <td class="px-4 py-3 text-gray-700">{{ garmentTypes[order.garment_type] ?? order.garment_type }}</td>
            <td class="px-4 py-3 text-center font-semibold">{{ order.quantity }}</td>
            <td class="px-4 py-3">
              <StatusBadge :status="order.status" :statuses="statuses" />
            </td>
            <td class="px-4 py-3">
              <span :class="isOverdue(order.deadline_at) ? 'text-red-600 font-medium' : 'text-gray-500'">
                {{ formatDate(order.deadline_at) || '—' }}
              </span>
            </td>
            <td class="px-4 py-3 text-right font-semibold">CHF {{ Number(order.total_price).toFixed(2) }}</td>
          </tr>
          <tr v-if="!orders.data.length">
            <td colspan="7" class="px-4 py-10 text-center text-gray-400">Nessun ordine trovato</td>
          </tr>
        </tbody>
      </table>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import BarcodeScanner from '@/Components/BarcodeScanner.vue'
import StatusBadge from '@/Components/StatusBadge.vue'

const props = defineProps({
  orders: Object, statuses: Object, garmentTypes: Object, filters: Object,
})

const filters = ref({ ...props.filters })
const hasFilters = computed(() => Object.values(filters.value).some(Boolean))

function search() {
  router.get(route('print-orders.index'), filters.value, { preserveState: true, replace: true })
}
function resetFilters() {
  filters.value = { search: '', status: '', garment_type: '' }
  search()
}

const kanbanCols = [
  { key: 'pending',   label: 'In attesa',      color: 'gray' },
  { key: 'confirmed', label: 'Confermati',      color: 'blue' },
  { key: 'printing',  label: 'In stampa',       color: 'yellow' },
  { key: 'ready',     label: 'Pronti',          color: 'green' },
  { key: 'delivered', label: 'Consegnati',      color: 'purple' },
  { key: 'cancelled', label: 'Annullati',       color: 'red' },
].filter(c => ['pending','printing','ready'].includes(c.key)) // 3 colonne principali

function colOrders(status) {
  return props.orders.data.filter(o => o.status === status)
}

const colorMap = {
  gray:   ['bg-gray-100',   'text-gray-700'],
  blue:   ['bg-blue-100',   'text-blue-700'],
  yellow: ['bg-yellow-100', 'text-yellow-700'],
  green:  ['bg-green-100',  'text-green-700'],
  purple: ['bg-purple-100', 'text-purple-700'],
  red:    ['bg-red-100',    'text-red-700'],
  orange: ['bg-orange-100', 'text-orange-700'],
}
function badgeBg(c)   { return colorMap[c]?.[0] ?? 'bg-gray-100' }
function badgeText(c) { return colorMap[c]?.[1] ?? 'text-gray-700' }

function formatDate(d) {
  return d ? new Date(d).toLocaleDateString('it-CH', { day: '2-digit', month: '2-digit', year: 'numeric' }) : ''
}
function isOverdue(d) {
  return d && new Date(d) < new Date()
}
</script>
