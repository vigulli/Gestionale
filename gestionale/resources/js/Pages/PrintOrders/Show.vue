<template>
  <AppLayout :title="'Ordine ' + order.order_number">
    <template #header-actions>
      <Link :href="route('print-orders.edit', order.id)"
        class="flex items-center gap-2 border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-50">
        Modifica
      </Link>
    </template>

    <div class="max-w-4xl space-y-4">

      <!-- Header -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex justify-between items-start">
          <div>
            <div class="flex items-center gap-3 mb-1">
              <h2 class="text-2xl font-bold text-amber-600 font-mono">{{ order.order_number }}</h2>
              <StatusBadge :status="order.status" :statuses="statuses" />
            </div>
            <p class="text-gray-700 font-medium">{{ order.customer.last_name }} {{ order.customer.first_name }}</p>
            <p class="text-gray-400 text-sm">{{ order.customer.phone }} · {{ order.customer.email }}</p>
          </div>
          <div class="text-right">
            <div class="text-3xl font-bold text-gray-900">CHF {{ Number(order.total_price).toFixed(2) }}</div>
            <div class="text-sm text-gray-500">{{ order.quantity }} pz × CHF {{ Number(order.unit_price).toFixed(2) }}</div>
            <div v-if="order.deadline_at" class="mt-1">
              <span :class="['text-xs font-medium', isOverdue ? 'text-red-600' : 'text-gray-500']">
                {{ isOverdue ? '⚠ Scaduto' : 'Scadenza' }}: {{ formatDate(order.deadline_at) }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <!-- Dettagli capo -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
          <h3 class="font-semibold text-gray-800 mb-4">Dettagli capo</h3>
          <dl class="space-y-2 text-sm">
            <div class="flex justify-between">
              <dt class="text-gray-500">Tipo</dt>
              <dd class="font-medium text-gray-800">{{ garmentTypes[order.garment_type] ?? order.garment_type }}</dd>
            </div>
            <div v-if="order.garment_color" class="flex justify-between">
              <dt class="text-gray-500">Colore</dt>
              <dd class="font-medium text-gray-800">{{ order.garment_color }}</dd>
            </div>
            <div v-if="order.garment_size" class="flex justify-between">
              <dt class="text-gray-500">Taglia</dt>
              <dd class="font-medium text-gray-800">{{ order.garment_size }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-gray-500">Quantità</dt>
              <dd class="font-bold text-gray-900">{{ order.quantity }} pz</dd>
            </div>
            <div v-if="order.print_position" class="flex justify-between">
              <dt class="text-gray-500">Posizione</dt>
              <dd class="font-medium text-gray-800">{{ order.print_position }}</dd>
            </div>
            <div v-if="order.print_size_cm" class="flex justify-between">
              <dt class="text-gray-500">Dim. stampa</dt>
              <dd class="font-medium text-gray-800">{{ order.print_size_cm }} cm</dd>
            </div>
            <div v-if="order.assigned_to" class="flex justify-between">
              <dt class="text-gray-500">Stampatore</dt>
              <dd class="font-medium text-gray-800">{{ order.assigned_to.name }}</dd>
            </div>
          </dl>

          <div v-if="order.print_description" class="mt-4 p-3 bg-gray-50 rounded-lg">
            <p class="text-xs text-gray-500 mb-1 font-medium">Descrizione grafica</p>
            <p class="text-sm text-gray-700 whitespace-pre-line">{{ order.print_description }}</p>
          </div>

          <div v-if="order.notes" class="mt-3 p-3 bg-amber-50 rounded-lg border border-amber-100">
            <p class="text-xs text-amber-700 mb-1 font-medium">Note interne</p>
            <p class="text-sm text-amber-800 whitespace-pre-line">{{ order.notes }}</p>
          </div>
        </div>

        <!-- File grafica + Azioni -->
        <div class="space-y-4">
          <!-- File artwork -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-800 mb-3">File grafica</h3>
            <div v-if="order.print_file_path">
              <img v-if="isImage(order.print_file_path)"
                :src="'/storage/' + order.print_file_path"
                class="w-full rounded-lg border border-gray-200 object-contain max-h-48" />
              <a v-else :href="'/storage/' + order.print_file_path" target="_blank"
                class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg border border-gray-200 hover:bg-gray-100">
                <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <div>
                  <p class="text-sm font-medium text-gray-700">Apri file grafica</p>
                  <p class="text-xs text-gray-400">{{ order.print_file_path.split('/').pop() }}</p>
                </div>
              </a>
            </div>
            <div v-else class="text-sm text-gray-400 text-center py-4">Nessun file caricato</div>
          </div>

          <!-- Aggiorna stato -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-800 mb-3">Aggiorna stato</h3>
            <div class="space-y-2">
              <button v-for="(s, key) in statuses" :key="key"
                @click="updateStatus(key)"
                :disabled="order.status === key"
                :class="['w-full text-left px-3 py-2 rounded-lg text-sm transition flex items-center gap-2',
                  order.status === key
                    ? 'bg-indigo-600 text-white font-semibold cursor-default'
                    : 'hover:bg-gray-50 border border-gray-200 text-gray-700']">
                <span class="w-2 h-2 rounded-full flex-shrink-0"
                  :class="order.status === key ? 'bg-white' : dotColor(s.color)" />
                {{ s.label }}
                <span v-if="order.status === key" class="ml-auto text-xs opacity-75">attuale</span>
              </button>
            </div>
          </div>

          <!-- Notifica cliente -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-800 mb-3">Notifica cliente</h3>
            <form @submit.prevent="sendNotification" class="space-y-3">
              <div class="flex gap-3">
                <label v-for="ch in ['sms','whatsapp','email']" :key="ch"
                  class="flex items-center gap-1.5 text-sm cursor-pointer">
                  <input type="checkbox" :value="ch" v-model="notifForm.channels" class="rounded text-amber-500" />
                  {{ ch.toUpperCase() }}
                </label>
              </div>
              <textarea v-model="notifForm.message" rows="3" required
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400"
                :placeholder="defaultMessage" />
              <button type="submit" :disabled="notifForm.processing || !notifForm.channels.length"
                class="w-full py-2 bg-amber-500 text-white rounded-lg text-sm font-semibold hover:bg-amber-600 disabled:opacity-50">
                {{ notifForm.processing ? 'Invio...' : 'Invia notifica' }}
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- QR e link tracking -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-800 mb-3">Tracking ordine</h3>
        <div class="flex items-center gap-3 bg-gray-50 rounded-lg px-4 py-3">
          <span class="text-xs font-mono text-gray-500 flex-1 truncate">{{ order.tracking_url }}</span>
          <button @click="copyTrackingLink" class="text-xs text-amber-600 hover:underline flex-shrink-0">
            {{ trackingCopied ? '✓ Copiato!' : 'Copia link' }}
          </button>
        </div>
        <p class="text-xs text-gray-400 mt-2">
          Condividi questo link col cliente per permettergli di seguire lo stato dell'ordine.
        </p>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatusBadge from '@/Components/StatusBadge.vue'

const props = defineProps({
  order: Object,
  statuses: Object,
  garmentTypes: Object,
})

const notifForm = useForm({
  channels: [],
  message:  '',
})

const trackingCopied = ref(false)

const isOverdue = computed(() => props.order.deadline_at && new Date(props.order.deadline_at) < new Date())

const defaultMessage = computed(() =>
  `Ciao ${props.order.customer?.first_name}, il tuo ordine ${props.order.order_number} è ${props.order.status_label?.toLowerCase()}.\nTraccia l'ordine: ${props.order.tracking_url}`
)

function updateStatus(status) {
  router.patch(route('print-orders.status', props.order.id), { status })
}

function sendNotification() {
  notifForm.post(route('print-orders.notify', props.order.id))
}

function copyTrackingLink() {
  navigator.clipboard.writeText(props.order.tracking_url)
  trackingCopied.value = true
  setTimeout(() => { trackingCopied.value = false }, 2000)
}

function isImage(path) {
  return /\.(jpg|jpeg|png|gif|webp)$/i.test(path)
}

function formatDate(d) {
  return d ? new Date(d).toLocaleDateString('it-CH') : ''
}

const dotColors = {
  gray: 'bg-gray-400', blue: 'bg-blue-500', yellow: 'bg-yellow-500',
  green: 'bg-green-500', purple: 'bg-purple-500', red: 'bg-red-500',
}
function dotColor(c) { return dotColors[c] ?? 'bg-gray-400' }
</script>
