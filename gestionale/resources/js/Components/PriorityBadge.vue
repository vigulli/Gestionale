<template>
  <span :class="['inline-flex items-center gap-1 text-xs font-medium', colorClass]">
    <span class="w-1.5 h-1.5 rounded-full" :class="dotClass" />
    {{ priorities[priority]?.label ?? priority }}
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({ priority: String, priorities: Object })

const colorMap = {
  gray:   ['text-gray-600',  'bg-gray-400'],
  blue:   ['text-blue-700',  'bg-blue-500'],
  orange: ['text-orange-700','bg-orange-500'],
  red:    ['text-red-700',   'bg-red-500'],
}

const colorClass = computed(() => (colorMap[props.priorities?.[props.priority]?.color ?? 'gray'] ?? colorMap.gray)[0])
const dotClass   = computed(() => (colorMap[props.priorities?.[props.priority]?.color ?? 'gray'] ?? colorMap.gray)[1])
</script>
