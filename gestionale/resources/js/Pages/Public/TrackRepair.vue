<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-lg max-w-md w-full p-6">
      <!-- Header -->
      <div class="text-center mb-6">
        <img v-if="$page.props.tenant?.logo_url" :src="$page.props.tenant.logo_url" class="h-10 mx-auto mb-3" />
        <h1 class="text-xl font-bold text-gray-900">Stato Riparazione</h1>
        <p class="text-gray-500 text-sm mt-1">{{ repair.ticket_number }}</p>
      </div>

      <!-- Dispositivo -->
      <div class="bg-gray-50 rounded-xl p-4 mb-6 text-sm">
        <div class="font-medium text-gray-700">{{ repair.device_brand }} {{ repair.device_model }}</div>
        <div class="text-gray-500">Ricevuto: {{ formatDate(repair.received_at) }}</div>
        <div v-if="repair.deadline_at" class="text-gray-500">Scadenza: {{ formatDate(repair.deadline_at) }}</div>
      </div>

      <!-- Stato attuale -->
      <div class="text-center mb-6">
        <div :class="['inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold', statusClass]">
          <span class="w-2 h-2 rounded-full bg-current opacity-70" />
          {{ repair.status_label }}
        </div>
      </div>

      <!-- Timeline -->
      <div class="relative">
        <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200" />
        <div class="space-y-4">
          <div v-for="(event, i) in repair.statusHistory" :key="i" class="relative flex gap-4 pl-10">
            <div class="absolute left-2.5 w-3 h-3 rounded-full border-2 border-white ring-2"
              :class="i === repair.statusHistory.length - 1 ? 'bg-indigo-600 ring-indigo-600' : 'bg-gray-300 ring-gray-300'"
            />
            <div>
              <div class="text-sm font-medium text-gray-800">
                {{ statuses[event.status]?.label ?? event.status }}
              </div>
              <div v-if="event.note" class="text-sm text-gray-500">{{ event.note }}</div>
              <div class="text-xs text-gray-400">{{ formatDateTime(event.changed_at) }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  repair: Object,
})

const statuses = {
  received:      { label: 'Ricevuto',           color: 'blue' },
  diagnosed:     { label: 'Diagnosticato',      color: 'purple' },
  in_progress:   { label: 'In lavorazione',     color: 'yellow' },
  waiting_parts: { label: 'In attesa ricambi',  color: 'orange' },
  ready:         { label: 'Pronto',             color: 'green' },
  delivered:     { label: 'Consegnato',         color: 'gray' },
  cancelled:     { label: 'Annullato',          color: 'red' },
}

const statusColorMap = {
  blue:   'bg-blue-100 text-blue-700',
  purple: 'bg-purple-100 text-purple-700',
  yellow: 'bg-yellow-100 text-yellow-700',
  orange: 'bg-orange-100 text-orange-700',
  green:  'bg-green-100 text-green-700',
  gray:   'bg-gray-100 text-gray-600',
  red:    'bg-red-100 text-red-700',
}

const statusClass = computed(() => {
  const color = statuses[props.repair.status]?.color ?? 'gray'
  return statusColorMap[color]
})

function formatDate(d) {
  if (!d) return ''
  return new Date(d).toLocaleDateString('it-CH', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function formatDateTime(d) {
  if (!d) return ''
  return new Date(d).toLocaleString('it-CH', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>
