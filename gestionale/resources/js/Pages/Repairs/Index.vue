<template>
  <AppLayout title="Riparazioni">
    <template #header-actions>
      <Link :href="route('repairs.create')"
        class="flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700 transition">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nuova riparazione
      </Link>
    </template>

    <!-- Filtri -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-4 flex flex-wrap gap-3 items-center">
      <BarcodeScanner
        class="w-72"
        placeholder="Cerca ticket, cliente, telefono..."
        @scan="(code) => filters.search = code"
      />
      <input
        v-model="filters.search"
        type="text"
        placeholder="Cerca..."
        class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-56 focus:ring-2 focus:ring-indigo-500"
        @input="search"
      />
      <select v-model="filters.status" @change="search"
        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
        <option value="">Tutti gli stati</option>
        <option v-for="(s, key) in statuses" :key="key" :value="key">{{ s.label }}</option>
      </select>
      <select v-model="filters.priority" @change="search"
        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
        <option value="">Tutte le priorità</option>
        <option v-for="(p, key) in priorities" :key="key" :value="key">{{ p.label }}</option>
      </select>
      <button v-if="hasFilters" @click="resetFilters" class="text-sm text-gray-500 hover:text-gray-700">
        Azzera
      </button>
    </div>

    <!-- Tabella -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
          <tr>
            <th class="px-4 py-3 text-left">Ticket</th>
            <th class="px-4 py-3 text-left">Cliente</th>
            <th class="px-4 py-3 text-left">Dispositivo</th>
            <th class="px-4 py-3 text-left">Stato</th>
            <th class="px-4 py-3 text-left">Priorità</th>
            <th class="px-4 py-3 text-left">Tecnico</th>
            <th class="px-4 py-3 text-left">Ricevuto</th>
            <th class="px-4 py-3 text-left">Scadenza</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="repair in repairs.data" :key="repair.id"
            class="hover:bg-gray-50 transition cursor-pointer"
            @click="$inertia.visit(route('repairs.show', repair.id))">
            <td class="px-4 py-3 font-mono font-semibold text-indigo-600">{{ repair.ticket_number }}</td>
            <td class="px-4 py-3">
              <div class="font-medium text-gray-900">{{ repair.customer.first_name }} {{ repair.customer.last_name }}</div>
              <div class="text-gray-500 text-xs">{{ repair.customer.phone }}</div>
            </td>
            <td class="px-4 py-3 text-gray-700">{{ repair.device_brand }} {{ repair.device_model }}</td>
            <td class="px-4 py-3">
              <StatusBadge :status="repair.status" :statuses="statuses" />
            </td>
            <td class="px-4 py-3">
              <PriorityBadge :priority="repair.priority" :priorities="priorities" />
            </td>
            <td class="px-4 py-3 text-gray-600">{{ repair.assigned_to?.name ?? '—' }}</td>
            <td class="px-4 py-3 text-gray-500">{{ formatDate(repair.received_at) }}</td>
            <td class="px-4 py-3">
              <span :class="isOverdue(repair.deadline_at) ? 'text-red-600 font-medium' : 'text-gray-500'">
                {{ repair.deadline_at ? formatDate(repair.deadline_at) : '—' }}
              </span>
            </td>
            <td class="px-4 py-3 text-right" @click.stop>
              <Link :href="route('repairs.edit', repair.id)" class="text-gray-400 hover:text-indigo-600 mr-2">
                <svg class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
              </Link>
            </td>
          </tr>
          <tr v-if="repairs.data.length === 0">
            <td colspan="9" class="px-4 py-10 text-center text-gray-400">Nessuna riparazione trovata</td>
          </tr>
        </tbody>
      </table>

      <!-- Paginazione -->
      <div v-if="repairs.last_page > 1" class="px-4 py-3 border-t border-gray-100 flex justify-between items-center text-sm text-gray-500">
        <span>{{ repairs.from }}–{{ repairs.to }} di {{ repairs.total }}</span>
        <div class="flex gap-1">
          <Link v-for="link in repairs.links" :key="link.label"
            :href="link.url ?? '#'"
            :class="['px-3 py-1 rounded', link.active ? 'bg-indigo-600 text-white' : 'hover:bg-gray-100', !link.url ? 'opacity-40 pointer-events-none' : '']"
            v-html="link.label"
          />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import BarcodeScanner from '@/Components/BarcodeScanner.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import PriorityBadge from '@/Components/PriorityBadge.vue'

const props = defineProps({
  repairs: Object,
  statuses: Object,
  priorities: Object,
  filters: Object,
})

const filters = ref({ ...props.filters })
const hasFilters = computed(() => Object.values(filters.value).some(Boolean))

function search() {
  router.get(route('repairs.index'), filters.value, { preserveState: true, replace: true })
}

function resetFilters() {
  filters.value = { search: '', status: '', priority: '' }
  search()
}

function formatDate(d) {
  if (!d) return ''
  return new Date(d).toLocaleDateString('it-CH', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function isOverdue(d) {
  if (!d) return false
  return new Date(d) < new Date()
}
</script>
