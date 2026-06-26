<template>
  <div class="h-screen flex flex-col" :style="cssVars">
    <!-- Top bar -->
    <header class="h-14 bg-white border-b flex items-center px-4 gap-4 shadow-sm flex-shrink-0">
      <Link :href="route('repairs.index')" class="text-gray-400 hover:text-gray-600">
        ← Gestionale
      </Link>
      <span class="font-bold text-lg text-primary">POS</span>
      <span class="text-gray-400 text-sm">{{ now }}</span>
      <div class="ml-auto flex items-center gap-3">
        <Link :href="route('pos.sales')" class="btn-outline btn-sm">Storico vendite</Link>
        <button @click="showWooSync = true" class="btn-outline btn-sm" title="Sync WooCommerce">🔄 WooCommerce</button>
      </div>
    </header>

    <div class="flex-1 flex min-h-0">
      <!-- LEFT: Product grid -->
      <div class="flex-1 flex flex-col min-w-0 p-4 gap-3">
        <!-- Search / barcode -->
        <div class="flex gap-2">
          <input ref="searchInput" v-model="productSearch"
                 @keydown="onSearchKey"
                 type="text" placeholder="Cerca prodotto o scansiona barcode…"
                 class="input flex-1" />
          <button @click="addManualItem" class="btn-outline">+ Manuale</button>
        </div>

        <!-- Category tabs -->
        <div class="flex gap-2 flex-wrap">
          <button v-for="tab in tabs" :key="tab.value"
                  @click="activeTab = tab.value"
                  :class="['px-3 py-1 rounded-full text-sm font-medium transition-colors',
                           activeTab === tab.value ? 'bg-primary text-white' : 'bg-white text-gray-600 border']">
            {{ tab.label }}
          </button>
        </div>

        <!-- Product cards -->
        <div class="flex-1 overflow-y-auto">
          <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
            <button v-for="item in filteredItems" :key="item._key"
                    @click="addToCart(item)"
                    class="bg-white border border-gray-200 rounded-xl p-3 text-left hover:border-primary hover:shadow-md transition-all group">
              <div class="text-sm font-medium text-gray-800 leading-tight mb-1 line-clamp-2 group-hover:text-primary">
                {{ item.name }}
              </div>
              <div class="text-xs text-gray-400 mb-2">{{ item.sku ?? (item.category?.name ?? '') }}</div>
              <div class="font-bold text-primary">{{ fmt(item.sell_price ?? item.price_default ?? 0) }}</div>
              <div v-if="item.stock_qty !== undefined" class="text-xs mt-1"
                   :class="item.stock_qty <= 5 ? 'text-orange-500' : 'text-gray-400'">
                Stock: {{ item.stock_qty }}
              </div>
            </button>
          </div>
          <p v-if="!filteredItems.length" class="text-center text-gray-400 py-12 text-sm">
            Nessun prodotto trovato
          </p>
        </div>
      </div>

      <!-- RIGHT: Cart -->
      <div class="w-96 flex flex-col bg-white border-l shadow-xl flex-shrink-0">
        <!-- Customer -->
        <div class="p-3 border-b">
          <CustomerSelect v-model="cart.customer_id" :customers="customers" />
        </div>

        <!-- Cart items -->
        <div class="flex-1 overflow-y-auto p-3 space-y-2">
          <div v-if="!cart.items.length" class="text-center text-gray-400 py-8 text-sm">
            Aggiungi prodotti dal catalogo
          </div>
          <CartItem v-for="(item, i) in cart.items" :key="i"
                    :item="item"
                    @update="updateItem(i, $event)"
                    @remove="removeItem(i)" />
        </div>

        <!-- Totals -->
        <div class="border-t p-4 space-y-2">
          <div class="flex justify-between text-sm text-gray-500">
            <span>Subtotale</span>
            <span>{{ fmt(subtotal) }}</span>
          </div>
          <div v-if="cart.discount_amount > 0" class="flex justify-between text-sm text-red-500">
            <span>Sconto</span>
            <span>- {{ fmt(cart.discount_amount) }}</span>
          </div>
          <div class="flex justify-between text-sm text-gray-500">
            <span>IVA</span>
            <span>{{ fmt(vatAmount) }}</span>
          </div>
          <div class="flex justify-between text-xl font-bold border-t pt-2">
            <span>TOTALE</span>
            <span class="text-primary">{{ fmt(total) }}</span>
          </div>

          <!-- Discount -->
          <div class="flex items-center gap-2 pt-1">
            <label class="text-xs text-gray-500 whitespace-nowrap">Sconto (CHF)</label>
            <input v-model.number="cart.discount_amount" type="number" min="0" step="0.01"
                   class="input-sm flex-1" />
          </div>

          <!-- Payment method -->
          <div class="grid grid-cols-4 gap-1 pt-1">
            <button v-for="pm in paymentMethods" :key="pm.value"
                    @click="cart.payment_method = pm.value"
                    :class="['rounded-lg py-2 text-xs font-medium border transition-colors',
                             cart.payment_method === pm.value
                               ? 'bg-primary text-white border-primary'
                               : 'bg-white text-gray-600 border-gray-200 hover:border-primary']">
              {{ pm.label }}
            </button>
          </div>

          <!-- Cash given -->
          <div v-if="cart.payment_method === 'cash'" class="flex items-center gap-2">
            <label class="text-xs text-gray-500 whitespace-nowrap">Contanti dati</label>
            <input v-model.number="cart.paid_amount" type="number" min="0" step="0.05"
                   class="input-sm flex-1" />
            <span class="text-xs font-medium text-green-600 whitespace-nowrap">
              Resto: {{ fmt(Math.max(0, (cart.paid_amount || total) - total)) }}
            </span>
          </div>

          <!-- SumUp status -->
          <div v-if="sumupStatus" class="text-xs text-center p-2 rounded"
               :class="sumupStatus === 'PAID' ? 'bg-green-50 text-green-700' : 'bg-yellow-50 text-yellow-700'">
            SumUp: {{ sumupStatus }}
          </div>

          <!-- Checkout button -->
          <button @click="checkout" :disabled="!cart.items.length || processing"
                  class="w-full py-4 rounded-xl text-white font-bold text-lg bg-primary hover:opacity-90 disabled:opacity-40 transition">
            {{ processing ? 'Elaborazione…' : `Paga ${fmt(total)}` }}
          </button>
        </div>
      </div>
    </div>

    <!-- Receipt modal -->
    <ReceiptModal v-if="lastReceipt" :receipt="lastReceipt"
                  :printer-ip="printerIp"
                  @close="lastReceipt = null"
                  @new-sale="resetCart" />

    <!-- WooCommerce sync modal -->
    <Modal :open="showWooSync" @close="showWooSync = false" title="Sincronizza WooCommerce">
      <form @submit.prevent="syncWoo" class="space-y-4">
        <div>
          <label class="label">URL WooCommerce (es. https://i-lab.ch)</label>
          <input v-model="woo.url" type="url" class="input" required placeholder="https://your-store.com" />
        </div>
        <div>
          <label class="label">Consumer Key</label>
          <input v-model="woo.key" type="text" class="input" required placeholder="ck_…" />
        </div>
        <div>
          <label class="label">Consumer Secret</label>
          <input v-model="woo.secret" type="password" class="input" required placeholder="cs_…" />
        </div>
        <div class="flex justify-end gap-3">
          <button type="button" @click="showWooSync = false" class="btn-outline">Annulla</button>
          <button type="submit" class="btn-primary" :disabled="wooSyncing">
            {{ wooSyncing ? 'Sincronizzazione…' : 'Sincronizza prodotti' }}
          </button>
        </div>
        <p v-if="wooError" class="text-sm text-red-600">{{ wooError }}</p>
      </form>
    </Modal>

    <!-- Manual item modal -->
    <Modal :open="showManualForm" @close="showManualForm = false" title="Articolo manuale">
      <form @submit.prevent="confirmManualItem" class="space-y-4">
        <div>
          <label class="label">Descrizione *</label>
          <input v-model="manualItem.description" type="text" class="input" required />
        </div>
        <div class="grid grid-cols-3 gap-3">
          <div>
            <label class="label">Prezzo (CHF) *</label>
            <input v-model.number="manualItem.unit_price" type="number" step="0.01" min="0" class="input" required />
          </div>
          <div>
            <label class="label">Qtà</label>
            <input v-model.number="manualItem.qty" type="number" step="0.01" min="0.01" class="input" />
          </div>
          <div>
            <label class="label">IVA %</label>
            <select v-model.number="manualItem.vat_rate" class="input">
              <option :value="8.1">8.1%</option>
              <option :value="2.6">2.6%</option>
              <option :value="0">0%</option>
            </select>
          </div>
        </div>
        <div class="flex justify-end gap-3">
          <button type="button" @click="showManualForm = false" class="btn-outline">Annulla</button>
          <button type="submit" class="btn-primary">Aggiungi</button>
        </div>
      </form>
    </Modal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import Modal from '@/Components/Modal.vue'
import CustomerSelect from '@/Components/CustomerSelect.vue'
import CartItem from '@/Components/Pos/CartItem.vue'
import ReceiptModal from '@/Components/Pos/ReceiptModal.vue'

const props = defineProps({
  products:  Array,
  services:  Array,
  customers: Array,
  vatRates:  Array,
})

const page     = usePage()
const cssVars  = computed(() => ({
  '--color-primary': page.props.tenant?.primary_color ?? '#6366f1',
}))

// ─── Clock ───────────────────────────────────────────────────────────────────
const now = ref('')
let clockTimer = null
function tick() { now.value = new Date().toLocaleString('it-CH', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit', year: 'numeric' }) }
onMounted(() => { tick(); clockTimer = setInterval(tick, 60000) })
onUnmounted(() => clearInterval(clockTimer))

// ─── Tabs / filter ───────────────────────────────────────────────────────────
const activeTab    = ref('all')
const productSearch = ref('')
const searchInput  = ref(null)

const tabs = computed(() => {
  const cats = [...new Set(props.services.map(s => s.category?.name).filter(Boolean))]
  return [
    { label: 'Tutti', value: 'all' },
    { label: 'Prodotti', value: 'products' },
    { label: 'Servizi', value: 'services' },
    ...cats.map(c => ({ label: c, value: 'cat:' + c })),
  ]
})

const allItems = computed(() => [
  ...props.products.map(p => ({ ...p, _key: 'p' + p.id, _type: 'product' })),
  ...props.services.map(s => ({ ...s, _key: 's' + s.id, _type: 'service', sell_price: s.price_default })),
])

const filteredItems = computed(() => {
  let items = allItems.value
  if (activeTab.value === 'products') items = items.filter(i => i._type === 'product')
  else if (activeTab.value === 'services') items = items.filter(i => i._type === 'service')
  else if (activeTab.value.startsWith('cat:')) {
    const cat = activeTab.value.slice(4)
    items = items.filter(i => i.category?.name === cat)
  }
  if (productSearch.value.trim()) {
    const q = productSearch.value.toLowerCase()
    items = items.filter(i =>
      i.name.toLowerCase().includes(q) ||
      (i.sku ?? '').toLowerCase().includes(q) ||
      (i.barcode ?? '').toLowerCase().includes(q)
    )
  }
  return items
})

// ─── Barcode scanner (USB) ───────────────────────────────────────────────────
let barcodeBuffer = ''
let barcodeTimer  = null

function onSearchKey(e) {
  if (e.key === 'Enter' && productSearch.value.trim()) {
    // Try barcode lookup
    lookupBarcode(productSearch.value.trim())
  }
}

async function lookupBarcode(code) {
  try {
    const res = await fetch(route('pos.barcode', { code }))
    if (res.ok) {
      const data = await res.json()
      if (data.found) {
        addToCart({ ...data.product, _type: 'product', sell_price: data.product.sell_price })
        productSearch.value = ''
        return
      }
    }
  } catch {}
  // keep search text as-is for manual filter
}

// ─── Cart ────────────────────────────────────────────────────────────────────
const cart = ref({
  customer_id:     null,
  items:           [],
  payment_method:  'cash',
  paid_amount:     null,
  discount_amount: 0,
  notes:           '',
})

function addToCart(item) {
  const existing = cart.value.items.find(i => i._key === item._key)
  if (existing) {
    existing.qty++
  } else {
    cart.value.items.push({
      _key:        item._key,
      type:        item._type,
      id:          item.id,
      description: item.name,
      qty:         1,
      unit_price:  parseFloat(item.sell_price ?? item.price_default ?? 0),
      vat_rate:    parseFloat(item.vat_rate ?? 8.1),
      discount_pct: 0,
    })
  }
}

function updateItem(i, patch) {
  Object.assign(cart.value.items[i], patch)
}

function removeItem(i) {
  cart.value.items.splice(i, 1)
}

const subtotal  = computed(() => cart.value.items.reduce((s, i) => s + i.qty * i.unit_price * (1 - i.discount_pct / 100), 0))
const vatAmount = computed(() => cart.value.items.reduce((s, i) => s + i.qty * i.unit_price * (1 - i.discount_pct / 100) * (i.vat_rate / 100), 0))
const total     = computed(() => Math.max(0, subtotal.value + vatAmount.value - (cart.value.discount_amount || 0)))

// ─── Manual item ─────────────────────────────────────────────────────────────
const showManualForm = ref(false)
const manualItem = ref({ description: '', unit_price: 0, qty: 1, vat_rate: 8.1 })

function addManualItem() { showManualForm.value = true }
function confirmManualItem() {
  cart.value.items.push({ _key: 'manual' + Date.now(), type: 'manual', id: null, ...manualItem.value, discount_pct: 0 })
  showManualForm.value = false
  manualItem.value = { description: '', unit_price: 0, qty: 1, vat_rate: 8.1 }
}

// ─── Payment methods ─────────────────────────────────────────────────────────
const paymentMethods = [
  { label: '💵 Contanti', value: 'cash' },
  { label: '💳 Carta',    value: 'card' },
  { label: '📱 SumUp',    value: 'sumup' },
  { label: '🏦 Bonifico', value: 'transfer' },
]

// ─── SumUp ───────────────────────────────────────────────────────────────────
const sumupStatus   = ref(null)
const sumupCheckoutId = ref(null)
let sumupPollTimer  = null

async function initiateSumup() {
  const res = await fetch(route('pos.sumup.checkout'), {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': page.props.csrf_token ?? '' },
    body: JSON.stringify({ amount: total.value, description: 'POS vendita' }),
  })
  const data = await res.json()
  if (data.checkout_id) {
    sumupCheckoutId.value = data.checkout_id
    sumupStatus.value = 'IN ATTESA…'
    pollSumup()
  }
}

function pollSumup() {
  sumupPollTimer = setInterval(async () => {
    const res = await fetch(route('pos.sumup.status', sumupCheckoutId.value))
    const data = await res.json()
    sumupStatus.value = data.status
    if (data.status === 'PAID') {
      clearInterval(sumupPollTimer)
      finalizeCheckout()
    } else if (['FAILED', 'EXPIRED'].includes(data.status)) {
      clearInterval(sumupPollTimer)
    }
  }, 2000)
}

// ─── Checkout ────────────────────────────────────────────────────────────────
const processing  = ref(false)
const lastReceipt = ref(null)
const printerIp   = ref(localStorage.getItem('starPrinterIp') ?? '')

async function checkout() {
  if (!cart.value.items.length) return

  if (cart.value.payment_method === 'sumup' && !sumupCheckoutId.value) {
    await initiateSumup()
    return
  }

  await finalizeCheckout()
}

async function finalizeCheckout() {
  processing.value = true
  try {
    const res = await fetch(route('pos.checkout'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        ...cart.value,
        items: cart.value.items.map(({ _key, type, id, description, qty, unit_price, vat_rate, discount_pct }) =>
          ({ type, id, description, qty, unit_price, vat_rate, discount_pct })),
        paid_amount: cart.value.payment_method === 'cash' ? (cart.value.paid_amount || total.value) : total.value,
      }),
    })
    const data = await res.json()
    if (data.success) {
      lastReceipt.value = data.receipt
    }
  } finally {
    processing.value = false
  }
}

function resetCart() {
  cart.value = { customer_id: null, items: [], payment_method: 'cash', paid_amount: null, discount_amount: 0, notes: '' }
  sumupStatus.value = null
  sumupCheckoutId.value = null
  lastReceipt.value = null
  productSearch.value = ''
  searchInput.value?.focus()
}

// ─── WooCommerce sync ─────────────────────────────────────────────────────────
const showWooSync = ref(false)
const wooSyncing  = ref(false)
const wooError    = ref('')
const woo = ref({ url: '', key: '', secret: '' })

function syncWoo() {
  wooSyncing.value = true
  wooError.value = ''
  router.post(route('pos.woo-sync'), woo.value, {
    onSuccess: () => { showWooSync.value = false; wooSyncing.value = false },
    onError: (e) => { wooError.value = e.woo ?? 'Errore'; wooSyncing.value = false },
  })
}

const fmt = v => new Intl.NumberFormat('it-CH', { style: 'currency', currency: 'CHF' }).format(v ?? 0)
</script>

<style scoped>
.text-primary  { color: var(--color-primary); }
.bg-primary    { background-color: var(--color-primary); }
.border-primary { border-color: var(--color-primary); }
.hover\:border-primary:hover { border-color: var(--color-primary); }
.hover\:text-primary:hover { color: var(--color-primary); }
</style>
