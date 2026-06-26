<template>
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-start justify-between">
      <div>
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ label }}</p>
        <p class="text-2xl font-bold mt-1" :class="valueColor">{{ value }}</p>
        <p v-if="sub" class="text-xs text-gray-400 mt-0.5">{{ sub }}</p>
      </div>
      <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0" :class="iconBg">
        <!-- currency -->
        <svg v-if="icon === 'currency'" class="w-5 h-5" :class="iconColor" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <!-- wrench -->
        <svg v-else-if="icon === 'wrench'" class="w-5 h-5" :class="iconColor" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l5.653-4.655m5.8-1.938l1.184-1.184a2.25 2.25 0 000-3.182L13.5 4.82a2.25 2.25 0 00-3.182 0l-1.184 1.184"/>
        </svg>
        <!-- document -->
        <svg v-else-if="icon === 'document'" class="w-5 h-5" :class="iconColor" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <!-- clipboard -->
        <svg v-else class="w-5 h-5" :class="iconColor" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  label: String,
  value: [String, Number],
  sub:   String,
  color: { type: String, default: 'indigo' },
  icon:  String,
})

const colorMap = {
  indigo: ['text-indigo-700', 'bg-indigo-50', 'text-indigo-500'],
  blue:   ['text-blue-700',   'bg-blue-50',   'text-blue-500'],
  green:  ['text-green-700',  'bg-green-50',  'text-green-500'],
  yellow: ['text-yellow-700', 'bg-yellow-50', 'text-yellow-500'],
  red:    ['text-red-700',    'bg-red-50',    'text-red-500'],
}

const valueColor = computed(() => colorMap[props.color]?.[0] ?? colorMap.indigo[0])
const iconBg     = computed(() => colorMap[props.color]?.[1] ?? colorMap.indigo[1])
const iconColor  = computed(() => colorMap[props.color]?.[2] ?? colorMap.indigo[2])
</script>
