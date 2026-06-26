<template>
  <AppLayout title="Ordini fornitore">
    <div class="flex flex-wrap gap-3 mb-6 items-center">
      <input v-model="search" @keyup.enter="load" type="text" placeholder="Cerca ordine…" class="input-sm flex-1 min-w-48" />
      <select v-model="filterStatus" @change="load" class="input-sm">
        <option value="">Tutti gli stati</option>
        <option v-for="(s, k) in statuses" :key="k" :value="k">{{ s.label }}</option>
      </select>
      <button @click="showForm = true" class="btn-primary ml-auto">+ Nuovo ordine</button>
    </div>

    <div class="card overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="th">N°</th>
            <th class="th">Fornitore</th>
            <th class="th">Data ordine</th>
            <th class="th">Stato</th>
            <th class="th">Pagamento</th>
            <th class="th text-right">Totale</th>
            <th class="th"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in purchases.data" :key="p.id" class="tr-hover">
            <td class="td font-mono text-xs">{{ p.purchase_number }}</td>
            <td class="td font-medium">{{ p.supplier_name }}</td>
            <td class="td text-gray-500">{{ fmtDate(p.ordered_at) }}</td>
            <td class="td">
              <Badge :color="statuses[p.status]?.color">{{ statuses[p.status]?.label }}</Badge>
            </td>
            <td class="td">
              <Badge :color="payStatuses[p.payment_status]?.color">{{ payStatuses[p.payment_status]?.label }}</Badge>
            </td>
            <td class="td text-right font-medium">{{ fmt(p.total) }}</td>
            <td class="td">
              <Link :href="route('accounting.purchases.show', p.id)" class="icon-btn text-indigo-500">→</Link>
            </td>
          </tr>
          <tr v-if="!purchases.data.length">
            <td colspan="7" class="td text-center text-gray-400 py-8">Nessun ordine trovato</td>
          </tr>
        </tbody>
      </table>
    </div>

    <Modal :open="showForm" @close="showForm = false" title="Nuovo ordine fornitore" size="lg">
      <form @submit.prevent="submit" class="space-y-5">
        <!-- Fornitore -->
        <div class="grid grid-cols-3 gap-4">
          <div class="col-span-3">
            <label class="label">Fornitore *</label>
            <input v-model="form.supplier_name" type="text" class="input" required />
          </div>
          <div>
            <label class="label">Email</label>
            <input v-model="form.supplier_email" type="email" class="input" />
          </div>
          <div>
            <label class="label">Telefono</label>
            <input v-model="form.supplier_phone" type="text" class="input" />
          </div>
          <div>
            <label class="label">Metodo pagamento</label>
            <select v-model="form.payment_method" class="input">
              <option value="">—</option>
              <option value="transfer">Bonifico</option>
              <option value="cash">Contanti</option>
              <option value="card">Carta</option>
              <option value="check">Assegno</option>
            </select>
          </div>
          <div>
            <label class="label">Data ordine *</label>
            <input v-model="form.ordered_at" type="date" class="input" required />
          </div>
          <div>
            <label class="label">Scadenza pagamento</label>
            <input v-model="form.due_at" type="date" class="input" />
          </div>
          <div>
            <label class="label">Rif. fattura fornitore</label>
            <input v-model="form.invoice_ref" type="text" class="input" />
          </div>
          <div class="col-span-3">
            <label class="label">Note</label>
            <textarea v-model="form.notes" class="input" rows="2"></textarea>
          </div>
        </div>

        <!-- Articoli -->
        <div>
          <div class="flex items-center justify-between mb-2">
            <h4 class="font-medium text-gray-700">Articoli</h4>
            <button type="button" @click="addItem" class="text-sm text-indigo-600 hover:underline">+ Aggiungi riga</button>
          </div>
          <div class="space-y-2">
            <div v-for="(item, i) in form.items" :key="i" class="grid grid-cols-12 gap-2 items-center">
              <input v-model="item.description" type="text" placeholder="Descrizione" class="input col-span-4" required />
              <input v-model="item.qty" type="number" step="0.01" min="0.01" placeholder="Qtà" class="input col-span-2" required />
              <input v-model="item.unit_price" type="number" step="0.01" min="0" placeholder="Prezzo" class="input col-span-2" required />
              <input v-model="item.vat_rate" type="number" step="0.1" min="0" placeholder="IVA%" class="input col-span-2" />
              <span class="col-span-1 text-right text-sm font-medium">{{ fmt(item.qty * item.unit_price) }}</span>
              <button type="button" @click="form.items.splice(i, 1)" class="col-span-1 text-red-400 hover:text-red-600 text-lg">×</button>
            </div>
          </div>
          <div class="text-right mt-2 text-sm font-medium text-gray-700">
            Totale: {{ fmt(form.items.reduce((s, i) => s + (i.qty * i.unit_price || 0), 0)) }}
          </div>
        </div>

        <div class="flex justify-end gap-3 pt-2">
          <button type="button" @click="showForm = false" class="btn-outline">Annulla</button>
          <button type="submit" class="btn-primary" :disabled="form.processing">Crea ordine</button>
        </div>
      </form>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Modal from '@/Components/Modal.vue'
import Badge from '@/Components/Badge.vue'

const props = defineProps({
  purchases: Object,
  statuses: Object,
  filters: Object,
})

const payStatuses = {
  unpaid:  { label: 'Non pagato', color: 'red' },
  partial: { label: 'Parz. pagato', color: 'yellow' },
  paid:    { label: 'Pagato', color: 'green' },
}

const search       = ref(props.filters.search ?? '')
const filterStatus = ref(props.filters.status ?? '')
const showForm     = ref(false)

const form = useForm({
  supplier_name:  '',
  supplier_email: '',
  supplier_phone: '',
  payment_method: 'transfer',
  ordered_at: new Date().toISOString().slice(0, 10),
  due_at: '',
  invoice_ref: '',
  notes: '',
  items: [{ description: '', qty: 1, unit_price: 0, vat_rate: 0 }],
})

function load() {
  router.get(route('accounting.purchases'), { search: search.value, status: filterStatus.value }, { preserveState: true, replace: true })
}

function addItem() {
  form.items.push({ description: '', qty: 1, unit_price: 0, vat_rate: 0 })
}

function submit() {
  form.post(route('accounting.purchases.store'), {
    onSuccess: () => { showForm.value = false; form.reset() }
  })
}

const fmt     = v => new Intl.NumberFormat('it-CH', { style: 'currency', currency: 'CHF' }).format(v ?? 0)
const fmtDate = d => d ? new Date(d).toLocaleDateString('it-CH') : '—'
</script>
