<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-lg max-w-sm w-full p-6">
      <div class="text-center mb-6">
        <img v-if="$page.props.tenant?.logo_url" :src="$page.props.tenant.logo_url" class="h-10 mx-auto mb-3" />
        <h1 class="text-xl font-bold text-gray-900">Stato Ordine</h1>
        <p class="text-gray-500 text-sm mt-1 font-mono">{{ order.order_number }}</p>
      </div>

      <div class="bg-gray-50 rounded-xl p-4 mb-5 text-sm space-y-1">
        <div class="font-medium text-gray-700">{{ order.garment_type }} · {{ order.quantity }} pz</div>
        <div v-if="order.deadline_at" class="text-gray-400">Consegna prevista: {{ formatDate(order.deadline_at) }}</div>
      </div>

      <!-- Stato attuale -->
      <div class="text-center mb-6">
        <span :class="['inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold', statusClass]">
          <span class="w-2 h-2 rounded-full bg-current opacity-70" />
          {{ order.status_label }}
        </span>
      </div>

      <!-- Barra progresso visuale -->
      <div class="flex items-center gap-1 mb-4">
        <template v-for="(step, i) in progressSteps" :key="step.key">
          <div class="flex-1 flex flex-col items-center gap-1">
            <div :class="['w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold',
              stepDone(step.key) ? 'bg-amber-500 text-white' : 'bg-gray-200 text-gray-400']">
              {{ stepDone(step.key) ? '✓' : i + 1 }}
            </div>
            <span class="text-xs text-gray-500 text-center leading-tight">{{ step.label }}</span>
          </div>
          <div v-if="i < progressSteps.length - 1"
            :class="['h-0.5 flex-1 mb-4', stepDone(progressSteps[i+1]?.key) ? 'bg-amber-400' : 'bg-gray-200']" />
        </template>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({ order: Object })

const progressSteps = [
  { key: 'pending',   label: 'Ricevuto' },
  { key: 'confirmed', label: 'Confermato' },
  { key: 'printing',  label: 'In stampa' },
  { key: 'ready',     label: 'Pronto' },
  { key: 'delivered', label: 'Consegnato' },
]

const statusOrder = ['pending','confirmed','printing','ready','delivered']

function stepDone(key) {
  return statusOrder.indexOf(props.order.status) >= statusOrder.indexOf(key)
}

const colorMap = {
  gray:   'bg-gray-100 text-gray-700',
  blue:   'bg-blue-100 text-blue-700',
  yellow: 'bg-yellow-100 text-yellow-700',
  green:  'bg-green-100 text-green-700',
  purple: 'bg-purple-100 text-purple-700',
  red:    'bg-red-100 text-red-700',
}

const statusClass = computed(() => {
  const color = props.order.statuses?.[props.order.status]?.color ?? 'gray'
  return colorMap[color] ?? colorMap.gray
})

function formatDate(d) {
  return d ? new Date(d).toLocaleDateString('it-CH') : ''
}
</script>
