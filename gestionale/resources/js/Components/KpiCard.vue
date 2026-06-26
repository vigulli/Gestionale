<template>
  <div class="card p-5 flex items-center gap-4">
    <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0"
         :class="bgClass">
      <span class="text-xl">{{ iconEmoji }}</span>
    </div>
    <div class="min-w-0">
      <p class="text-sm text-gray-500 truncate">{{ label }}</p>
      <p class="text-2xl font-bold truncate" :class="textClass">{{ value }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  label: String,
  value: String,
  color: { type: String, default: 'indigo' },
  icon:  { type: String, default: 'chart-bar' },
})

const colorMap = {
  green:  { bg: 'bg-green-50',  text: 'text-green-600' },
  red:    { bg: 'bg-red-50',    text: 'text-red-600' },
  indigo: { bg: 'bg-indigo-50', text: 'text-indigo-700' },
  orange: { bg: 'bg-orange-50', text: 'text-orange-600' },
  blue:   { bg: 'bg-blue-50',   text: 'text-blue-700' },
  yellow: { bg: 'bg-yellow-50', text: 'text-yellow-700' },
}

const bgClass   = computed(() => colorMap[props.color]?.bg   ?? 'bg-gray-50')
const textClass = computed(() => colorMap[props.color]?.text ?? 'text-gray-800')

const iconMap = {
  'arrow-trending-up':   '📈',
  'arrow-trending-down': '📉',
  'scale':               '⚖️',
  'chart-bar':           '📊',
  'banknotes':           '💵',
  'shopping-cart':       '🛒',
}
const iconEmoji = computed(() => iconMap[props.icon] ?? '📊')
</script>
