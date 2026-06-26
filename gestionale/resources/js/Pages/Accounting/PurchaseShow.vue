<template>
  <AppLayout :title="`Ordine ${purchase.purchase_number}`">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Dettagli -->
      <div class="lg:col-span-2 space-y-6">
        <div class="card p-5">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h2 class="text-lg font-semibold">{{ purchase.supplier_name }}</h2>
              <p class="text-sm text-gray-500">{{ purchase.purchase_number }}</p>
            </div>
            <div class="flex gap-2">
              <Badge :color="statuses[purchase.status]?.color">{{ statuses[purchase.status]?.label }}</Badge>
              <Badge :color="paymentStatuses[purchase.payment_status]?.color">{{ paymentStatuses[purchase.payment_status]?.label }}</Badge>
            </div>
          </div>

          <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm mb-4">
            <div>
              <p class="label">Ordinato il</p>
              <p>{{ fmtDate(purchase.ordered_at) }}</p>
            </div>
            <div v-if="purchase.received_at">
              <p class="label">Ricevuto il</p>
              <p>{{ fmtDate(purchase.received_at) }}</p>
            </div>
            <div v-if="purchase.due_at">
              <p class="label">Scadenza</p>
              <p :class="isPastDue ? 'text-red-600 font-semibold' : ''">{{ fmtDate(purchase.due_at) }}</p>
            </div>
            <div v-if="purchase.invoice_ref">
              <p class="label">Rif. fattura</p>
              <p>{{ purchase.invoice_ref }}</p>
            </div>
          </div>

          <!-- Articoli -->
          <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
              <tr>
                <th class="th">Descrizione</th>
                <th class="th text-right">Qtà</th>
                <th class="th text-right">Prezzo</th>
                <th class="th text-right">Totale</th>
                <th class="th text-center">Ricevuto</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in purchase.items" :key="item.id" class="border-b border-gray-50">
                <td class="td">
                  {{ item.description }}
                  <span v-if="item.product" class="text-xs text-gray-400 ml-1">({{ item.product.sku ?? item.product.name }})</span>
                </td>
                <td class="td text-right">{{ item.qty }}</td>
                <td class="td text-right">{{ fmt(item.unit_price) }}</td>
                <td class="td text-right font-medium">{{ fmt(item.line_total) }}</td>
                <td class="td text-center">
                  <input type="checkbox" :checked="item.received"
                         @change="toggleReceived(item)"
                         class="rounded text-green-500" />
                </td>
              </tr>
            </tbody>
            <tfoot class="border-t font-medium">
              <tr>
                <td colspan="3" class="td text-right text-gray-500">Subtotale</td>
                <td class="td text-right">{{ fmt(purchase.subtotal) }}</td>
                <td></td>
              </tr>
              <tr v-if="purchase.vat_amount > 0">
                <td colspan="3" class="td text-right text-gray-500">IVA</td>
                <td class="td text-right">{{ fmt(purchase.vat_amount) }}</td>
                <td></td>
              </tr>
              <tr>
                <td colspan="3" class="td text-right">Totale</td>
                <td class="td text-right text-indigo-700 font-bold">{{ fmt(purchase.total) }}</td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- Azioni laterali -->
      <div class="space-y-4">
        <!-- Aggiorna stato -->
        <div class="card p-4">
          <h3 class="font-semibold text-gray-700 mb-3">Aggiorna stato</h3>
          <form @submit.prevent="updateStatus" class="space-y-3">
            <div>
              <label class="label">Stato ordine</label>
              <select v-model="statusForm.status" class="input">
                <option v-for="(s, k) in statuses" :key="k" :value="k">{{ s.label }}</option>
              </select>
            </div>
            <div>
              <label class="label">Stato pagamento</label>
              <select v-model="statusForm.payment_status" class="input">
                <option v-for="(s, k) in paymentStatuses" :key="k" :value="k">{{ s.label }}</option>
              </select>
            </div>
            <div v-if="statusForm.payment_status !== 'unpaid'">
              <label class="label">Importo pagato (CHF)</label>
              <input v-model="statusForm.paid_amount" type="number" step="0.01" min="0" class="input" />
            </div>
            <div v-if="['received','partial'].includes(statusForm.status)">
              <label class="label">Data ricezione</label>
              <input v-model="statusForm.received_at" type="date" class="input" />
            </div>
            <button type="submit" class="btn-primary w-full">Aggiorna</button>
          </form>
        </div>

        <!-- Info pagamento -->
        <div class="card p-4 text-sm space-y-1">
          <p class="label">Pagato finora</p>
          <p class="text-xl font-bold text-green-600">{{ fmt(purchase.paid_amount) }}</p>
          <p class="text-gray-500">su {{ fmt(purchase.total) }}</p>
          <div v-if="remaining > 0" class="mt-2 text-red-600 font-medium">Residuo: {{ fmt(remaining) }}</div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Badge from '@/Components/Badge.vue'

const props = defineProps({
  purchase: Object,
  statuses: Object,
  paymentStatuses: Object,
})

const statusForm = useForm({
  status:         props.purchase.status,
  payment_status: props.purchase.payment_status,
  paid_amount:    props.purchase.paid_amount,
  received_at:    props.purchase.received_at?.slice(0, 10) ?? '',
})

const remaining  = computed(() => Math.max(0, props.purchase.total - props.purchase.paid_amount))
const isPastDue  = computed(() => props.purchase.due_at && new Date(props.purchase.due_at) < new Date() && props.purchase.payment_status !== 'paid')

function updateStatus() {
  statusForm.patch(route('accounting.purchases.status', props.purchase.id))
}

function toggleReceived(item) {
  router.patch(route('accounting.purchases.item', [props.purchase.id, item.id]), {
    received: !item.received,
    received_qty: item.received ? 0 : item.qty,
  }, { preserveScroll: true })
}

const fmt     = v => new Intl.NumberFormat('it-CH', { style: 'currency', currency: 'CHF' }).format(v ?? 0)
const fmtDate = d => d ? new Date(d).toLocaleDateString('it-CH') : '—'
</script>
