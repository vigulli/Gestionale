<template>
  <div>
    <!-- Campo di input che cattura il lettore USB quando focalizzato -->
    <div
      v-if="mode === 'usb' || mode === 'both'"
      class="relative"
    >
      <input
        ref="inputRef"
        v-model="manualInput"
        type="text"
        :placeholder="placeholder ?? 'Scansiona o digita codice...'"
        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
        @keydown.enter.prevent="submitManual"
        @focus="scanner.attachUsbListener($event.target)"
        @blur="scanner.detachUsbListener($event.target)"
      />
      <span class="absolute left-3 top-2.5 text-gray-400">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
        </svg>
      </span>
    </div>

    <!-- Camera scanner -->
    <div v-if="mode === 'camera' || mode === 'both'" class="mt-2">
      <button
        type="button"
        @click="toggleCamera"
        class="flex items-center gap-2 text-sm text-indigo-600 hover:text-indigo-800"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        {{ scanner.cameraActive.value ? 'Chiudi camera' : 'Usa camera' }}
      </button>

      <div v-if="scanner.cameraActive.value" class="mt-2 rounded-lg overflow-hidden border border-gray-200">
        <video ref="videoRef" class="w-full max-w-sm" autoplay muted playsinline />
      </div>
      <p v-if="scanner.cameraError.value" class="text-xs text-red-500 mt-1">
        {{ scanner.cameraError.value }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, nextTick } from 'vue'
import { useBarcodeScanner } from '@/Composables/useBarcodeScanner'

const props = defineProps({
  mode: { type: String, default: 'usb' }, // usb | camera | both
  placeholder: String,
  autoFocus: { type: Boolean, default: false },
})

const emit = defineEmits(['scan'])

const inputRef = ref(null)
const videoRef = ref(null)
const manualInput = ref('')

const scanner = useBarcodeScanner({
  onScan(code, source) {
    manualInput.value = ''
    emit('scan', code, source)
  },
})

function submitManual() {
  const code = manualInput.value.trim()
  if (code.length >= 2) {
    emit('scan', code, 'manual')
    manualInput.value = ''
  }
}

async function toggleCamera() {
  if (scanner.cameraActive.value) {
    scanner.stopCamera()
  } else {
    await nextTick()
    scanner.startCamera(videoRef.value)
  }
}

if (props.autoFocus) {
  watch(inputRef, (el) => { if (el) el.focus() }, { immediate: true })
}
</script>
