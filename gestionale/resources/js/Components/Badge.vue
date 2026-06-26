<template>
  <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium" :class="cls">
    <slot />
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  color: { type: String, default: 'gray' },
})

const map = {
  green:  'bg-green-100 text-green-800',
  red:    'bg-red-100 text-red-700',
  yellow: 'bg-yellow-100 text-yellow-800',
  blue:   'bg-blue-100 text-blue-700',
  indigo: 'bg-indigo-100 text-indigo-700',
  orange: 'bg-orange-100 text-orange-700',
  gray:   'bg-gray-100 text-gray-600',
  purple: 'bg-purple-100 text-purple-700',
}

const cls = computed(() => map[props.color] ?? map.gray)
</script>
