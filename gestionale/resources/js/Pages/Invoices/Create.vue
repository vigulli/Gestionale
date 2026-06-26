<template>
  <AppLayout title="Nuova Fattura">
    <div class="max-w-3xl mx-auto">
      <form @submit.prevent="submit" class="space-y-6">
        <div class="card p-5 space-y-4">
          <h2 class="font-semibold text-gray-700">Intestazione</h2>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="label">Tipo</label>
              <select v-model="form.type" class="input">
                <option value="sale">Fattura</option>
                <option value="credit_note">Nota di credito</option>
              </select>
            </div>
            <div>
              <label class="label">Stato</label>
              <select v-model="form.status" class="input">
                <option value="draft">Bozza</option>
                <option value="sent">Inviata</option>
                <option value="paid">Pagata</option>
              </select>
            </div>
            <div class="col-span-2">
              <label class="label">Cliente</label>
              <CustomerSelect v-model="form.customer_id" :customers="customers" />
            </div>
            <div>
              <label class="label">Metodo pagamento</label>
              <select v-model="form.payment_method" class="input">
                <option value="">—</option>
                <option value="cash">Contanti</option>
                <option value="card">Carta</option>
                <option value="sumup">SumUp</option>
                <option value="transfer">Bonifico</option>
              </select>
            </div>
            <div>
              <label class="label">Scadenza pagamento</label>
              <input v-model="form.due_at" type="date" class="input" />
            </div>
            <div>
              <label class="label">Sconto (CHF)</label>
              <input v-model.number="form.discount_amount" type="number" min="0" step="0.01" class="input" />
            </div>
            <div class="col-span-2">
              <label class="label">Note</label>
              <textarea v-model="form.notes" class="input" rows="2"></textarea>
            </div>
          </div>

          <div style="font-size:12px;color:#888;">
            Numero fattura: <strong>{{ nextNumber }}</strong>
          </div>
        </div>

        <!-- Line items -->
        <div class="card p-5">
          <div class="flex items-center justify-between mb-3">
            <h2 class="font-semibold text-gray-700">Articoli</h2>
            <button type="button" @click="addRow" class="text-sm text-indigo-600 hover:underline">+ Aggiungi riga</button>
          </div>
          <div class="space-y-2">
            <div v-for="(item, i) in form.items" :key="i" class="grid grid-cols-12 gap-2 items-center">
              <input v-model="item.description" type="text" placeholder="Descrizione" class="input col-span-4" required />
              <input v-model.number="item.qty" type="number" step="0.01" min="0.01" class="input col-span-1" placeholder="Qtà" required />
              <input v-model.number="item.unit_price" type="number" step="0.01" min="0" class="input col-span-2" placeholder="Prezzo" required />
              <select v-model.number="item.vat_rate" class="input col-span-2">
                <option v-for="r in vatRates" :key="r" :value="r">{{ r }}%</option>
              </select>
              <input v-model.number="item.discount_pct" type="number" step="1" min="0" max="100" class="input col-span-1" placeholder="Sc%" />
              <span class="col-span-1 text-right text-sm font-medium">{{ fmt(item.qty * item.unit_price * (1 - item.discount_pct / 100)) }}</span>
              <button type="button" @click="form.items.splice(i, 1)" class="col-span-1 text-red-400 hover:text-red-600 text-lg text-center">×</button>
            </div>
          </div>

          <!-- Total preview -->
          <div class="text-right mt-4 space-y-1 text-sm">
            <div class="text-gray-500">Subtotale: <strong>{{ fmt(subtotal) }}</strong></div>
            <div class="text-gray-500">IVA: <strong>{{ fmt(vat) }}</strong></div>
            <div class="text-lg font-bold">Totale: <span class="text-indigo-700">{{ fmt(total) }}</span></div>
          </div>
        </div>

        <div class="flex justify-end gap-3">
          <Link :href="route('invoices.index')" class="btn-outline">Annulla</Link>
          <button type="submit" class="btn-primary" :disabled="form.processing">Crea fattura</button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import CustomerSelect from '@/Components/CustomerSelect.vue'

const props = defineProps({
  customers:  Array,
  vatRates:   Array,
  nextNumber: String,
})

const form = useForm({
  type:            'sale',
  status:          'draft',
  customer_id:     null,
  payment_method:  '',
  due_at:          '',
  notes:           '',
  discount_amount: 0,
  vat_mode:        'inclusive',
  items: [{ description: '', qty: 1, unit_price: 0, vat_rate: 8.1, discount_pct: 0 }],
})

const subtotal = computed(() => form.items.reduce((s, i) => s + i.qty * i.unit_price * (1 - i.discount_pct / 100), 0))
const vat      = computed(() => form.items.reduce((s, i) => s + i.qty * i.unit_price * (1 - i.discount_pct / 100) * (i.vat_rate / 100), 0))
const total    = computed(() => Math.max(0, subtotal.value + vat.value - (form.discount_amount || 0)))

function addRow() { form.items.push({ description: '', qty: 1, unit_price: 0, vat_rate: 8.1, discount_pct: 0 }) }

function submit() {
  form.post(route('invoices.store'))
}

const fmt = v => new Intl.NumberFormat('it-CH', { style: 'currency', currency: 'CHF' }).format(v ?? 0)
</script>
