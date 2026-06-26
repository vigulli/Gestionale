<template>
  <AppLayout title="Clienti">
    <template #header-actions>
      <Link :href="route('customers.create')"
        class="flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700 transition">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nuovo cliente
      </Link>
    </template>

    <!-- Filtri -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-4 flex flex-wrap gap-3 items-center">
      <BarcodeScanner
        class="w-72"
        placeholder="Cerca nome, telefono, email..."
        @scan="(code) => { filters.search = code; search() }"
      />
      <input v-model="filters.search" type="text" placeholder="Cerca..."
        class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-56 focus:ring-2 focus:ring-indigo-500"
        @input="search" />
      <select v-model="filters.category" @change="search"
        class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
        <option value="">Tutte le categorie</option>
        <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
      </select>
      <button v-if="hasFilters" @click="resetFilters" class="text-sm text-gray-500 hover:text-gray-700">Azzera</button>
    </div>

    <!-- Tabella -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
          <tr>
            <th class="px-4 py-3 text-left">Cliente</th>
            <th class="px-4 py-3 text-left">Contatti</th>
            <th class="px-4 py-3 text-left">Categoria</th>
            <th class="px-4 py-3 text-right">Riparazioni</th>
            <th class="px-4 py-3 text-right">Fatturato</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="c in customers.data" :key="c.id"
            class="hover:bg-gray-50 cursor-pointer"
            @click="$inertia.visit(route('customers.show', c.id))">
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0"
                  :style="{ backgroundColor: avatarColor(c.first_name + c.last_name) }">
                  {{ initials(c) }}
                </div>
                <div>
                  <div class="font-medium text-gray-900">{{ c.last_name }} {{ c.first_name }}</div>
                  <div v-if="c.company" class="text-xs text-gray-400">{{ c.company }}</div>
                </div>
              </div>
            </td>
            <td class="px-4 py-3">
              <div class="text-gray-700">{{ c.phone }}</div>
              <div class="text-gray-400 text-xs">{{ c.email }}</div>
            </td>
            <td class="px-4 py-3">
              <span v-if="c.category"
                class="px-2 py-0.5 rounded-full text-xs bg-indigo-50 text-indigo-700">
                {{ c.category }}
              </span>
            </td>
            <td class="px-4 py-3 text-right">
              <span class="font-medium text-gray-800">{{ c.repairs_count }}</span>
            </td>
            <td class="px-4 py-3 text-right">
              <span class="font-semibold text-gray-900">
                {{ c.total_invoiced ? 'CHF ' + Number(c.total_invoiced).toFixed(2) : '—' }}
              </span>
            </td>
            <td class="px-4 py-3 text-right" @click.stop>
              <Link :href="route('customers.edit', c.id)"
                class="text-gray-400 hover:text-indigo-600">
                <svg class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
              </Link>
            </td>
          </tr>
          <tr v-if="customers.data.length === 0">
            <td colspan="6" class="px-4 py-12 text-center text-gray-400">Nessun cliente trovato</td>
          </tr>
        </tbody>
      </table>

      <!-- Paginazione -->
      <div v-if="customers.last_page > 1" class="px-4 py-3 border-t border-gray-100 flex justify-between items-center text-sm text-gray-500">
        <span>{{ customers.from }}–{{ customers.to }} di {{ customers.total }}</span>
        <div class="flex gap-1">
          <Link v-for="link in customers.links" :key="link.label"
            :href="link.url ?? '#'"
            :class="['px-3 py-1 rounded', link.active ? 'bg-indigo-600 text-white' : 'hover:bg-gray-100', !link.url ? 'opacity-40 pointer-events-none' : '']"
            v-html="link.label" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import BarcodeScanner from '@/Components/BarcodeScanner.vue'

const props = defineProps({ customers: Object, categories: Array, filters: Object })
const filters   = ref({ ...props.filters })
const hasFilters = computed(() => Object.values(filters.value).some(Boolean))

function search() {
  router.get(route('customers.index'), filters.value, { preserveState: true, replace: true })
}
function resetFilters() {
  filters.value = { search: '', category: '' }
  search()
}

function initials(c) {
  return ((c.first_name?.[0] ?? '') + (c.last_name?.[0] ?? '')).toUpperCase()
}

const palette = ['#6366f1','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#f97316','#84cc16']
function avatarColor(name) {
  let hash = 0
  for (const ch of name) hash = ch.charCodeAt(0) + ((hash << 5) - hash)
  return palette[Math.abs(hash) % palette.length]
}
</script>
