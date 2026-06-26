<template>
  <div class="flex items-start gap-2 bg-gray-50 rounded-xl p-3">
    <div class="flex-1 min-w-0">
      <p class="text-sm font-medium text-gray-800 leading-tight">{{ item.description }}</p>
      <div class="flex items-center gap-2 mt-1.5">
        <!-- Qty -->
        <div class="flex items-center gap-1">
          <button @click="dec" class="w-6 h-6 rounded-full bg-gray-200 hover:bg-gray-300 text-xs font-bold flex items-center justify-center">−</button>
          <input :value="item.qty" @change="emit('update', { qty: +$event.target.value })"
                 type="number" min="0.01" step="0.01"
                 class="w-12 text-center text-sm border rounded px-1 py-0.5" />
          <button @click="inc" class="w-6 h-6 rounded-full bg-gray-200 hover:bg-gray-300 text-xs font-bold flex items-center justify-center">+</button>
        </div>
        <!-- Price -->
        <span class="text-xs text-gray-400">×</span>
        <input :value="item.unit_price" @change="emit('update', { unit_price: +$event.target.value })"
               type="number" min="0" step="0.01"
               class="w-16 text-sm border rounded px-1 py-0.5 text-right" />
        <!-- Discount -->
        <input :value="item.discount_pct" @change="emit('update', { discount_pct: +$event.target.value })"
               type="number" min="0" max="100" step="1"
               class="w-10 text-sm border rounded px-1 py-0.5 text-right" placeholder="%" title="Sconto %" />
        <span class="text-xs text-gray-400">%</span>
      </div>
    </div>
    <div class="text-right flex-shrink-0">
      <p class="font-bold text-sm">{{ fmt(lineTotal) }}</p>
      <p class="text-xs text-gray-400">IVA {{ item.vat_rate }}%</p>
      <button @click="emit('remove')" class="text-red-400 hover:text-red-600 text-xs mt-1">✕ togli</button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({ item: Object })
const emit  = defineEmits(['update', 'remove'])

const lineTotal = computed(() => props.item.qty * props.item.unit_price * (1 - props.item.discount_pct / 100))

function inc() { emit('update', { qty: Math.round((props.item.qty + 1) * 100) / 100 }) }
function dec() { if (props.item.qty > 1) emit('update', { qty: Math.round((props.item.qty - 1) * 100) / 100 }) }

const fmt = v => new Intl.NumberFormat('it-CH', { style: 'currency', currency: 'CHF' }).format(v ?? 0)
</script>
