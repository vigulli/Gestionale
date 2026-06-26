<template>
  <AppLayout title="Nuovo Preventivo">
    <form @submit.prevent="submit" class="max-w-4xl space-y-6">

      <!-- Cliente -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="font-semibold text-gray-800 mb-4">Cliente</h2>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Cliente *</label>
            <select v-model="form.customer_id" required
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
              <option value="">Seleziona cliente...</option>
              <option v-for="c in customers" :key="c.id" :value="c.id">
                {{ c.last_name }} {{ c.first_name }} — {{ c.phone }}
              </option>
            </select>
            <p v-if="form.errors.customer_id" class="text-xs text-red-500 mt-1">{{ form.errors.customer_id }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Scadenza preventivo</label>
            <input v-model="form.due_at" type="date"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500" />
          </div>
        </div>
      </div>

      <!-- Righe preventivo -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex justify-between items-center mb-4">
          <h2 class="font-semibold text-gray-800">Voci preventivo</h2>
          <div class="flex gap-2">
            <button type="button" @click="addFromService"
              class="text-xs text-indigo-600 border border-indigo-200 rounded-lg px-3 py-1.5 hover:bg-indigo-50">
              + Da servizio
            </button>
            <button type="button" @click="addLine()"
              class="text-xs text-gray-600 border border-gray-200 rounded-lg px-3 py-1.5 hover:bg-gray-50">
              + Riga manuale
            </button>
          </div>
        </div>

        <!-- Picker servizi -->
        <div v-if="showServicePicker" class="mb-4 p-4 bg-indigo-50 rounded-lg">
          <div class="flex flex-wrap gap-2 mb-2">
            <button v-for="cat in categories" :key="cat.id" type="button"
              @click="filterCat = cat.id"
              :class="['text-xs px-3 py-1 rounded-full border transition',
                filterCat === cat.id ? 'bg-indigo-600 text-white border-indigo-600' : 'border-gray-300 hover:border-indigo-400']">
              {{ cat.name }}
            </button>
            <button type="button" @click="filterCat = null"
              class="text-xs px-3 py-1 rounded-full border border-gray-300 hover:border-indigo-400">
              Tutti
            </button>
          </div>
          <div class="grid grid-cols-2 gap-2 max-h-48 overflow-y-auto">
            <button v-for="s in filteredServices" :key="s.id" type="button"
              @click="addLine(s)"
              class="text-left p-3 bg-white rounded-lg border border-gray-200 hover:border-indigo-400 transition">
              <div class="text-sm font-medium text-gray-800">{{ s.name }}</div>
              <div class="text-xs text-gray-500 mt-0.5">
                <span v-if="s.price_min && s.price_max">CHF {{ s.price_min }}–{{ s.price_max }}</span>
                <span v-else-if="s.price_default">CHF {{ s.price_default }}</span>
              </div>
            </button>
          </div>
          <button type="button" @click="showServicePicker = false"
            class="mt-2 text-xs text-gray-500 hover:text-gray-700">Chiudi</button>
        </div>

        <!-- Tabella righe -->
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="text-gray-500 text-xs border-b border-gray-100">
              <tr>
                <th class="pb-2 text-left font-medium">Descrizione</th>
                <th class="pb-2 text-right font-medium w-16">Qtà</th>
                <th class="pb-2 text-right font-medium w-28">Prezzo unit.</th>
                <th class="pb-2 text-right font-medium w-20">Sconto %</th>
                <th class="pb-2 text-right font-medium w-20">IVA %</th>
                <th class="pb-2 text-right font-medium w-28">Totale</th>
                <th class="pb-2 w-8"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, i) in form.items" :key="i" class="border-b border-gray-50">
                <td class="py-2 pr-2">
                  <input v-model="item.description" placeholder="Descrizione..."
                    class="w-full border border-gray-200 rounded px-2 py-1 text-sm focus:ring-1 focus:ring-indigo-400" />
                </td>
                <td class="py-2 px-1">
                  <input v-model.number="item.qty" type="number" min="0.01" step="0.01"
                    class="w-full border border-gray-200 rounded px-2 py-1 text-sm text-right focus:ring-1 focus:ring-indigo-400" />
                </td>
                <td class="py-2 px-1">
                  <input v-model.number="item.unit_price" type="number" min="0" step="0.01"
                    class="w-full border border-gray-200 rounded px-2 py-1 text-sm text-right focus:ring-1 focus:ring-indigo-400" />
                </td>
                <td class="py-2 px-1">
                  <input v-model.number="item.discount_pct" type="number" min="0" max="100"
                    class="w-full border border-gray-200 rounded px-2 py-1 text-sm text-right focus:ring-1 focus:ring-indigo-400" />
                </td>
                <td class="py-2 px-1">
                  <select v-model.number="item.vat_rate"
                    class="w-full border border-gray-200 rounded px-1 py-1 text-sm focus:ring-1 focus:ring-indigo-400">
                    <option :value="8.1">8.1%</option>
                    <option :value="2.6">2.6%</option>
                    <option :value="0">0%</option>
                  </select>
                </td>
                <td class="py-2 pl-1 text-right font-medium text-gray-800">
                  CHF {{ lineTotal(item).toFixed(2) }}
                </td>
                <td class="py-2 pl-1">
                  <button type="button" @click="form.items.splice(i,1)"
                    class="text-gray-300 hover:text-red-500">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Totali -->
        <div class="mt-4 flex justify-end">
          <div class="w-64 space-y-1 text-sm">
            <div class="flex justify-between text-gray-600">
              <span>Imponibile</span>
              <span>CHF {{ subtotal.toFixed(2) }}</span>
            </div>
            <div class="flex justify-between text-gray-600">
              <span>IVA</span>
              <span>CHF {{ vat.toFixed(2) }}</span>
            </div>
            <div class="flex justify-between font-bold text-gray-900 text-base border-t border-gray-200 pt-2 mt-2">
              <span>Totale</span>
              <span>CHF {{ total.toFixed(2) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Note -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <label class="block text-sm font-medium text-gray-700 mb-1">Note (visibili al cliente)</label>
        <textarea v-model="form.notes" rows="3"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
          placeholder="Condizioni, garanzia, tempi..."></textarea>
      </div>

      <!-- Azioni -->
      <div class="flex gap-3">
        <Link :href="route('quotes.index')"
          class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50">
          Annulla
        </Link>
        <button type="submit" :disabled="form.processing"
          class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 disabled:opacity-50">
          {{ form.processing ? 'Salvataggio...' : 'Crea preventivo' }}
        </button>
      </div>
    </form>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  customers: Array,
  services:  Array,
  products:  Array,
})

const form = useForm({
  customer_id: '',
  due_at: '',
  notes: '',
  vat_mode: 'exclusive',
  items: [],
})

const showServicePicker = ref(false)
const filterCat = ref(null)

const categories = computed(() => {
  const map = {}
  props.services.forEach(s => { if (s.category) map[s.category.id] = s.category })
  return Object.values(map)
})

const filteredServices = computed(() =>
  props.services.filter(s => !filterCat.value || s.service_category_id === filterCat.value)
)

function addLine(service = null) {
  form.items.push({
    service_id:   service?.id ?? null,
    product_id:   null,
    description:  service?.name ?? '',
    qty:          1,
    unit_price:   service?.price_default ?? service?.price_min ?? 0,
    discount_pct: 0,
    vat_rate:     parseFloat(service?.vat_rate ?? 8.1),
  })
  showServicePicker.value = false
}

function addFromService() {
  showServicePicker.value = !showServicePicker.value
}

function lineTotal(item) {
  return item.qty * item.unit_price * (1 - (item.discount_pct ?? 0) / 100)
}

const subtotal = computed(() => form.items.reduce((s, i) => s + lineTotal(i), 0))
const vat      = computed(() => form.items.reduce((s, i) => s + lineTotal(i) * (i.vat_rate / 100), 0))
const total    = computed(() => subtotal.value + vat.value)

function submit() {
  form.post(route('quotes.store'))
}
</script>
