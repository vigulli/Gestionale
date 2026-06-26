<template>
  <div class="relative" ref="root">
    <div @click="open = !open"
         class="flex items-center gap-2 cursor-pointer text-sm border rounded-lg px-3 py-2 bg-white hover:border-indigo-400 transition-colors">
      <span class="text-gray-400">👤</span>
      <span :class="modelValue ? 'text-gray-800' : 'text-gray-400'">
        {{ selectedLabel }}
      </span>
      <span v-if="modelValue" @click.stop="clear" class="ml-auto text-gray-400 hover:text-red-500">✕</span>
    </div>

    <div v-if="open" class="absolute z-30 left-0 right-0 mt-1 bg-white border rounded-xl shadow-xl">
      <input v-model="search" ref="searchEl" type="text" placeholder="Cerca cliente…"
             class="w-full px-3 py-2 text-sm border-b outline-none rounded-t-xl" />
      <div class="max-h-48 overflow-y-auto">
        <button v-for="c in filtered" :key="c.id"
                @click="select(c)"
                class="w-full text-left px-3 py-2 text-sm hover:bg-indigo-50 transition-colors">
          <span class="font-medium">{{ c.first_name }} {{ c.last_name }}</span>
          <span class="text-gray-400 ml-2 text-xs">{{ c.phone }}</span>
        </button>
        <p v-if="!filtered.length" class="text-center text-gray-400 text-sm py-4">Nessun cliente trovato</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'

const props = defineProps({
  modelValue: { type: [Number, null], default: null },
  customers:  { type: Array, default: () => [] },
})
const emit = defineEmits(['update:modelValue'])

const open     = ref(false)
const search   = ref('')
const searchEl = ref(null)
const root     = ref(null)

const selectedLabel = computed(() => {
  if (!props.modelValue) return 'Cliente (opzionale)'
  const c = props.customers.find(c => c.id === props.modelValue)
  return c ? `${c.first_name} ${c.last_name}` : '—'
})

const filtered = computed(() => {
  const q = search.value.toLowerCase()
  return props.customers.filter(c =>
    `${c.first_name} ${c.last_name} ${c.phone ?? ''}`.toLowerCase().includes(q)
  ).slice(0, 20)
})

watch(open, v => { if (v) nextTick(() => searchEl.value?.focus()) })

function select(c) { emit('update:modelValue', c.id); open.value = false; search.value = '' }
function clear()   { emit('update:modelValue', null) }

function onClickOutside(e) { if (root.value && !root.value.contains(e.target)) open.value = false }
onMounted(()  => document.addEventListener('mousedown', onClickOutside))
onUnmounted(() => document.removeEventListener('mousedown', onClickOutside))
</script>
