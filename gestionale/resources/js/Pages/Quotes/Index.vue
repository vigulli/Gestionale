<template>
  <AppLayout title="Preventivi">
    <template #header-actions>
      <Link :href="route('quotes.create')"
        class="flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700 transition">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nuovo preventivo
      </Link>
    </template>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-4 flex flex-wrap gap-3">
      <input v-model="filters.search" type="text" placeholder="Cerca..."
        class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-56 focus:ring-2 focus:ring-indigo-500"
        @input="search" />
      <select v-model="filters.status" @change="search"
        class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
        <option value="">Tutti gli stati</option>
        <option value="accepted">Accettati</option>
        <option value="rejected">Rifiutati</option>
        <option value="">In attesa</option>
      </select>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
          <tr>
            <th class="px-4 py-3 text-left">N° Preventivo</th>
            <th class="px-4 py-3 text-left">Cliente</th>
            <th class="px-4 py-3 text-left">Totale</th>
            <th class="px-4 py-3 text-left">Stato</th>
            <th class="px-4 py-3 text-left">Risposta</th>
            <th class="px-4 py-3 text-left">Data</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="q in quotes.data" :key="q.id"
            class="hover:bg-gray-50 cursor-pointer"
            @click="$inertia.visit(route('quotes.show', q.id))">
            <td class="px-4 py-3 font-mono text-indigo-600 font-semibold">{{ q.invoice_number }}</td>
            <td class="px-4 py-3">
              <div class="font-medium">{{ q.customer.first_name }} {{ q.customer.last_name }}</div>
              <div class="text-gray-400 text-xs">{{ q.customer.phone }}</div>
            </td>
            <td class="px-4 py-3 font-semibold">CHF {{ Number(q.total).toFixed(2) }}</td>
            <td class="px-4 py-3">
              <span :class="['px-2 py-0.5 rounded-full text-xs font-medium', statusClass(q.status)]">
                {{ statusLabel(q.status) }}
              </span>
            </td>
            <td class="px-4 py-3">
              <span v-if="q.quote_status === 'accepted'" class="text-green-600 font-medium text-xs">✓ Accettato</span>
              <span v-else-if="q.quote_status === 'rejected'" class="text-red-600 font-medium text-xs">✗ Rifiutato</span>
              <span v-else class="text-gray-400 text-xs">In attesa</span>
            </td>
            <td class="px-4 py-3 text-gray-500">{{ formatDate(q.issued_at) }}</td>
            <td class="px-4 py-3 text-right" @click.stop>
              <Link :href="route('quotes.show', q.id)" class="text-indigo-600 text-xs hover:underline">Apri</Link>
            </td>
          </tr>
          <tr v-if="quotes.data.length === 0">
            <td colspan="7" class="px-4 py-10 text-center text-gray-400">Nessun preventivo</td>
          </tr>
        </tbody>
      </table>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({ quotes: Object, filters: Object })
const filters = ref({ ...props.filters })

const statusMap = {
  draft:     ['Bozza',    'bg-gray-100 text-gray-600'],
  sent:      ['Inviato',  'bg-blue-100 text-blue-700'],
  paid:      ['Pagato',   'bg-green-100 text-green-700'],
  cancelled: ['Annullato','bg-red-100 text-red-700'],
}

function statusLabel(s) { return statusMap[s]?.[0] ?? s }
function statusClass(s) { return statusMap[s]?.[1] ?? 'bg-gray-100 text-gray-600' }
function search() { router.get(route('quotes.index'), filters.value, { preserveState: true, replace: true }) }
function formatDate(d) { return d ? new Date(d).toLocaleDateString('it-CH') : '' }
</script>
