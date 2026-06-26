<template>
  <AppLayout :title="order ? 'Modifica Ordine' : 'Nuovo Ordine Stampa'">
    <form @submit.prevent="submit" class="max-w-4xl space-y-5" enctype="multipart/form-data">

      <!-- Cliente + Scadenza -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="font-semibold text-gray-800 mb-4">Cliente e consegna</h2>
        <div class="grid grid-cols-3 gap-4">
          <div class="col-span-2">
            <label class="label">Cliente *</label>
            <select v-model="form.customer_id" required class="input">
              <option value="">Seleziona cliente...</option>
              <option v-for="c in customers" :key="c.id" :value="c.id">
                {{ c.last_name }} {{ c.first_name }} — {{ c.phone }}
              </option>
            </select>
            <p v-if="form.errors.customer_id" class="err">{{ form.errors.customer_id }}</p>
          </div>
          <div>
            <label class="label">Scadenza consegna</label>
            <input v-model="form.deadline_at" type="date" class="input" />
          </div>
          <div>
            <label class="label">Tecnico / Stampatore</label>
            <select v-model="form.assigned_to" class="input">
              <option :value="null">Non assegnato</option>
              <option v-for="t in technicians" :key="t.id" :value="t.id">{{ t.name }}</option>
            </select>
          </div>
          <div v-if="order">
            <label class="label">Stato</label>
            <select v-model="form.status" class="input">
              <option v-for="(s, key) in statuses" :key="key" :value="key">{{ s.label }}</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Capo da stampare -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="font-semibold text-gray-800 mb-4">Capo da stampare</h2>
        <div class="grid grid-cols-3 gap-4">
          <div>
            <label class="label">Tipo capo *</label>
            <select v-model="form.garment_type" required class="input">
              <option value="">Seleziona...</option>
              <option v-for="(label, key) in garmentTypes" :key="key" :value="key">{{ label }}</option>
            </select>
          </div>
          <div>
            <label class="label">Colore capo</label>
            <input v-model="form.garment_color" type="text" class="input" placeholder="Bianco, Nero, Navy..." />
          </div>
          <div>
            <label class="label">Taglia</label>
            <select v-model="form.garment_size" class="input">
              <option value="">—</option>
              <option v-for="s in sizes" :key="s" :value="s">{{ s }}</option>
            </select>
          </div>
          <div>
            <label class="label">Quantità *</label>
            <input v-model.number="form.quantity" type="number" min="1" required class="input" />
          </div>
          <div>
            <label class="label">Posizione stampa</label>
            <select v-model="form.print_position" class="input">
              <option value="">Seleziona...</option>
              <option v-for="p in positions" :key="p" :value="p">{{ p }}</option>
            </select>
          </div>
          <div>
            <label class="label">Dimensione stampa (cm)</label>
            <input v-model="form.print_size_cm" type="text" class="input" placeholder="es. 20x15" />
          </div>
          <div class="col-span-3">
            <label class="label">Descrizione stampa / grafica</label>
            <textarea v-model="form.print_description" rows="3" class="input"
              placeholder="Descrizione del logo, testo, colori, numero stampe multipli..."></textarea>
          </div>
        </div>
      </div>

      <!-- File grafica -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="font-semibold text-gray-800 mb-4">File grafica</h2>

        <!-- File esistente -->
        <div v-if="order?.print_file_path && !newFile" class="flex items-center gap-4 mb-4 p-3 bg-amber-50 rounded-lg border border-amber-200">
          <svg class="w-8 h-8 text-amber-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
          </svg>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-700">File grafica caricato</p>
            <p class="text-xs text-gray-500 truncate">{{ order.print_file_path }}</p>
          </div>
          <button type="button" @click="newFile = true" class="text-xs text-amber-600 hover:underline">
            Sostituisci
          </button>
        </div>

        <div v-if="!order?.print_file_path || newFile">
          <label
            class="flex flex-col items-center justify-center border-2 border-dashed border-gray-300 rounded-xl p-8 cursor-pointer hover:border-amber-400 transition"
            @dragover.prevent @drop.prevent="handleDrop">
            <svg class="w-10 h-10 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
            </svg>
            <p class="text-sm text-gray-600 mb-1">
              <span class="text-amber-600 font-medium">Clicca per caricare</span> o trascina qui
            </p>
            <p class="text-xs text-gray-400">PNG, JPG, PDF, SVG, AI, EPS — max 20MB</p>
            <input type="file" class="sr-only" accept=".jpg,.jpeg,.png,.pdf,.svg,.ai,.eps"
              @change="handleFile" />
          </label>
          <div v-if="previewUrl" class="mt-3">
            <img :src="previewUrl" class="max-h-40 rounded-lg border border-gray-200 object-contain" />
          </div>
          <div v-if="selectedFileName" class="mt-2 text-sm text-gray-600">
            📎 {{ selectedFileName }}
          </div>
        </div>
      </div>

      <!-- Prezzo -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="font-semibold text-gray-800 mb-4">Prezzo</h2>
        <div class="grid grid-cols-4 gap-4 items-end">
          <div>
            <label class="label">Prezzo unitario (CHF) *</label>
            <input v-model.number="form.unit_price" type="number" min="0" step="0.01" required class="input" />
          </div>
          <div>
            <label class="label">Quantità</label>
            <input :value="form.quantity" disabled class="input bg-gray-50 text-gray-500" />
          </div>
          <div>
            <label class="label">IVA %</label>
            <select v-model.number="form.vat_rate" class="input">
              <option :value="8.1">8.1% (standard CH)</option>
              <option :value="2.6">2.6% (ridotta CH)</option>
              <option :value="0">0% (esente)</option>
            </select>
          </div>
          <div class="pb-0.5">
            <div class="text-sm text-gray-500">Totale ordine</div>
            <div class="text-2xl font-bold text-gray-900">
              CHF {{ (form.unit_price * form.quantity).toFixed(2) }}
            </div>
            <div class="text-xs text-gray-400">
              + IVA CHF {{ (form.unit_price * form.quantity * form.vat_rate / 100).toFixed(2) }}
            </div>
          </div>
        </div>
      </div>

      <!-- Note -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <label class="label">Note interne</label>
        <textarea v-model="form.notes" rows="2" class="input" placeholder="Note per il team..."></textarea>
      </div>

      <div class="flex gap-3">
        <Link :href="order ? route('print-orders.show', order.id) : route('print-orders.index')"
          class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50">
          Annulla
        </Link>
        <button type="submit" :disabled="form.processing"
          class="px-6 py-2 bg-amber-500 text-white rounded-lg text-sm font-semibold hover:bg-amber-600 disabled:opacity-50">
          {{ form.processing ? 'Salvataggio...' : (order ? 'Salva modifiche' : 'Crea ordine') }}
        </button>
      </div>
    </form>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  order: Object, customers: Array, technicians: Array,
  garmentTypes: Object, positions: Array, sizes: Array, statuses: Object,
  customer_id: [String, Number],
})

const form = useForm({
  customer_id:       props.order?.customer_id ?? props.customer_id ?? '',
  garment_type:      props.order?.garment_type ?? '',
  garment_color:     props.order?.garment_color ?? '',
  garment_size:      props.order?.garment_size ?? '',
  quantity:          props.order?.quantity ?? 1,
  print_description: props.order?.print_description ?? '',
  print_position:    props.order?.print_position ?? '',
  print_size_cm:     props.order?.print_size_cm ?? '',
  unit_price:        props.order?.unit_price ?? 0,
  vat_rate:          props.order?.vat_rate ?? 8.1,
  assigned_to:       props.order?.assigned_to ?? null,
  deadline_at:       props.order?.deadline_at?.slice(0, 10) ?? '',
  notes:             props.order?.notes ?? '',
  status:            props.order?.status ?? 'pending',
  print_file:        null,
})

const previewUrl       = ref(null)
const selectedFileName = ref(null)
const newFile          = ref(false)

function handleFile(e) {
  const file = e.target.files[0]
  if (!file) return
  form.print_file = file
  selectedFileName.value = file.name
  if (file.type.startsWith('image/')) {
    previewUrl.value = URL.createObjectURL(file)
  }
}

function handleDrop(e) {
  const file = e.dataTransfer.files[0]
  if (!file) return
  form.print_file = file
  selectedFileName.value = file.name
  if (file.type.startsWith('image/')) {
    previewUrl.value = URL.createObjectURL(file)
  }
}

function submit() {
  if (props.order) {
    form.post(route('print-orders.update', props.order.id), { method: '_method', value: 'PUT' })
  } else {
    form.post(route('print-orders.store'))
  }
}
</script>

<style scoped>
.label { @apply block text-sm font-medium text-gray-700 mb-1; }
.input  { @apply w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:border-transparent; }
.err    { @apply text-xs text-red-500 mt-1; }
</style>
