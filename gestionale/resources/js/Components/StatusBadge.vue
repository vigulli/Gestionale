<template>
  <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', colorClass]">
    {{ statuses[status]?.label ?? status }}
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({ status: String, statuses: Object })

const colorMap = {
  blue:   'bg-blue-100 text-blue-800',
  purple: 'bg-purple-100 text-purple-800',
  yellow: 'bg-yellow-100 text-yellow-800',
  orange: 'bg-orange-100 text-orange-800',
  green:  'bg-green-100 text-green-800',
  gray:   'bg-gray-100 text-gray-700',
  red:    'bg-red-100 text-red-800',
}

const colorClass = computed(() => {
  const color = props.statuses?.[props.status]?.color ?? 'gray'
  return colorMap[color] ?? colorMap.gray
})
</script>
