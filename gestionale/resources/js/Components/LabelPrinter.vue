<template>
  <div>
    <!-- Preview etichetta -->
    <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
      <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-medium text-gray-700">Anteprima etichetta</h3>
        <div class="flex gap-2">
          <!-- Dimensioni personalizzabili -->
          <div class="flex items-center gap-1 text-xs text-gray-500">
            <input
              v-model.number="labelConfig.width"
              type="number" min="20" max="200"
              class="w-14 border border-gray-300 rounded px-1 py-0.5 text-center text-xs"
            />
            <span>×</span>
            <input
              v-model.number="labelConfig.height"
              type="number" min="20" max="200"
              class="w-14 border border-gray-300 rounded px-1 py-0.5 text-center text-xs"
            />
            <span>mm</span>
          </div>
          <button
            @click="saveConfig"
            class="text-xs text-indigo-600 hover:underline"
          >Salva</button>
        </div>
      </div>

      <!-- Etichetta scalata per anteprima -->
      <div class="flex justify-center">
        <div
          ref="labelRef"
          class="bg-white border border-gray-400 shadow-sm relative overflow-hidden"
          :style="previewStyle"
        >
          <!-- Logo tenant (opzionale) -->
          <img
            v-if="showLogo && tenantLogo"
            :src="tenantLogo"
            class="absolute"
            :style="logoStyle"
          />

          <!-- Contenuto dinamico per tipo -->
          <template v-if="type === 'repair'">
            <div class="absolute" :style="textStyle(0)">
              <div class="font-bold truncate" :style="{ fontSize: scaled(7) + 'px' }">
                {{ data.ticket_number }}
              </div>
              <div class="truncate text-gray-600" :style="{ fontSize: scaled(5.5) + 'px' }">
                {{ data.customer_name }}
              </div>
              <div class="truncate" :style="{ fontSize: scaled(5) + 'px' }">
                {{ data.device_brand }} {{ data.device_model }}
              </div>
              <div class="text-gray-500" :style="{ fontSize: scaled(4.5) + 'px' }">
                {{ data.received_at }}
              </div>
            </div>
          </template>

          <template v-else-if="type === 'product'">
            <div class="absolute" :style="textStyle(0)">
              <div class="font-bold truncate" :style="{ fontSize: scaled(7) + 'px' }">
                {{ data.name }}
              </div>
              <div class="text-gray-500 truncate" :style="{ fontSize: scaled(5) + 'px' }">
                {{ data.sku }}
              </div>
              <div class="font-semibold" :style="{ fontSize: scaled(8) + 'px' }">
                CHF {{ data.sell_price }}
              </div>
            </div>
          </template>

          <template v-else-if="type === 'print_order'">
            <div class="absolute" :style="textStyle(0)">
              <div class="font-bold truncate" :style="{ fontSize: scaled(7) + 'px' }">
                {{ data.order_number }}
              </div>
              <div class="truncate" :style="{ fontSize: scaled(5.5) + 'px' }">
                {{ data.customer_name }}
              </div>
              <div class="text-gray-500 truncate" :style="{ fontSize: scaled(5) + 'px' }">
                {{ data.garment_type }} {{ data.quantity }}pz
              </div>
            </div>
          </template>

          <!-- QR code -->
          <canvas
            v-if="labelConfig.show_qr"
            ref="qrCanvas"
            class="absolute"
            :style="qrStyle"
          />

          <!-- Barcode -->
          <svg
            v-if="labelConfig.show_barcode"
            ref="barcodeEl"
            class="absolute"
            :style="barcodeStyle"
          />
        </div>
      </div>
    </div>

    <!-- Controlli stampa -->
    <div class="mt-3 flex items-center gap-3">
      <label class="flex items-center gap-2 text-sm text-gray-600">
        <input v-model="labelConfig.show_qr" type="checkbox" class="rounded" />
        QR Code
      </label>
      <label class="flex items-center gap-2 text-sm text-gray-600">
        <input v-model="labelConfig.show_barcode" type="checkbox" class="rounded" />
        Barcode
      </label>
      <label class="flex items-center gap-2 text-sm text-gray-600">
        <input v-model="showLogo" type="checkbox" class="rounded" />
        Logo
      </label>

      <div class="flex-1" />

      <label class="text-sm text-gray-600">
        Copie:
        <input
          v-model.number="copies"
          type="number" min="1" max="100"
          class="w-16 ml-1 border border-gray-300 rounded px-2 py-1 text-sm"
        />
      </label>

      <button
        @click="print"
        class="flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700 transition"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
        </svg>
        Stampa
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue'
import { usePage } from '@inertiajs/vue3'
import QRCode from 'qrcode'
import JsBarcode from 'jsbarcode'

const props = defineProps({
  type: { type: String, required: true }, // repair | product | print_order
  data: { type: Object, required: true },
  trackingUrl: String,
})

const page = usePage()
const tenantLogo = computed(() => page.props.tenant?.logo_url)

// Config etichetta (default 38×90mm)
const labelConfig = ref({
  width: 38,
  height: 90,
  show_qr: true,
  show_barcode: false,
})
const showLogo = ref(false)
const copies = ref(1)

// Scala: 1mm = ~3.78px (96dpi), preview ridotto al 60%
const PREVIEW_SCALE = 0.6
const MM_TO_PX = 3.78

function scaled(mm) {
  return mm * MM_TO_PX * PREVIEW_SCALE
}

const previewStyle = computed(() => ({
  width: scaled(labelConfig.value.width) + 'px',
  height: scaled(labelConfig.value.height) + 'px',
}))

// Posizioni elementi
function textStyle(offsetTopMm = 2) {
  const padding = scaled(2)
  return {
    left: padding + 'px',
    top: scaled(offsetTopMm + 2) + 'px',
    right: (labelConfig.value.show_qr ? scaled(labelConfig.value.width * 0.38) : scaled(2)) + 'px',
  }
}

const logoStyle = computed(() => ({
  top: scaled(1) + 'px',
  right: scaled(1) + 'px',
  height: scaled(6) + 'px',
  width: 'auto',
}))

const qrStyle = computed(() => ({
  right: scaled(2) + 'px',
  bottom: scaled(2) + 'px',
  width: scaled(labelConfig.value.width * 0.35) + 'px',
  height: scaled(labelConfig.value.width * 0.35) + 'px',
}))

const barcodeStyle = computed(() => ({
  left: scaled(2) + 'px',
  bottom: scaled(2) + 'px',
  height: scaled(8) + 'px',
}))

// QR e barcode refs
const qrCanvas = ref(null)
const barcodeEl = ref(null)

function qrValue() {
  if (props.trackingUrl) return props.trackingUrl
  if (props.type === 'repair') return props.data.ticket_number ?? ''
  if (props.type === 'product') return props.data.sku ?? props.data.id?.toString() ?? ''
  if (props.type === 'print_order') return props.data.order_number ?? ''
  return ''
}

function barcodeValue() {
  if (props.type === 'product') return props.data.barcode ?? props.data.sku ?? ''
  if (props.type === 'repair') return props.data.ticket_number ?? ''
  return ''
}

async function renderQr() {
  if (!labelConfig.value.show_qr || !qrCanvas.value) return
  const val = qrValue()
  if (!val) return
  try {
    await QRCode.toCanvas(qrCanvas.value, val, {
      width: labelConfig.value.width * MM_TO_PX * PREVIEW_SCALE * 0.35,
      margin: 1,
      color: { dark: '#000', light: '#fff' },
    })
  } catch {}
}

function renderBarcode() {
  if (!labelConfig.value.show_barcode || !barcodeEl.value) return
  const val = barcodeValue()
  if (!val) return
  try {
    JsBarcode(barcodeEl.value, val, {
      format: 'CODE128',
      height: 8 * MM_TO_PX * PREVIEW_SCALE,
      displayValue: false,
      margin: 0,
    })
  } catch {}
}

watch([() => labelConfig.value.show_qr, () => labelConfig.value.show_barcode, () => props.data], async () => {
  await nextTick()
  renderQr()
  renderBarcode()
}, { deep: true, immediate: true })

onMounted(async () => {
  await nextTick()
  renderQr()
  renderBarcode()
})

function saveConfig() {
  // Emette la config per salvarla nel template
  // Il controller la persiste via API
}

const labelRef = ref(null)

function print() {
  const el = labelRef.value
  if (!el) return

  // Genera CSS con dimensioni reali in mm
  const w = labelConfig.value.width
  const h = labelConfig.value.height

  const printWindow = window.open('', '_blank', 'width=400,height=300')
  const html = `<!DOCTYPE html>
<html>
<head>
<style>
  @page { size: ${w}mm ${h}mm; margin: 0; }
  body { margin: 0; padding: 0; }
  .label {
    width: ${w}mm; height: ${h}mm;
    position: relative; overflow: hidden;
    display: flex;
  }
</style>
</head>
<body>
  ${Array(copies.value).fill(`<div class="label">${el.innerHTML}</div>`).join('')}
  <script>window.onload = () => { window.print(); window.close(); }<\/script>
</body>
</html>`

  printWindow.document.write(html)
  printWindow.document.close()
}
</script>
