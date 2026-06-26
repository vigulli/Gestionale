<template>
  <AppLayout :title="`Fattura ${sale.invoice_number}`">
    <div class="max-w-4xl mx-auto space-y-6">

      <!-- Warning IBAN -->
      <div v-if="!hasIban" class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-sm text-yellow-800 flex items-center gap-2">
        ⚠️ IBAN non configurato — la cedola QR non verrà generata.
        <Link :href="route('settings.index')" class="underline">Configura →</Link>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main card -->
        <div class="lg:col-span-2 card p-5">
          <div class="flex items-start justify-between mb-5">
            <div>
              <h2 class="text-xl font-bold">{{ sale.invoice_number }}</h2>
              <p class="text-sm text-gray-500 mt-1">{{ fmtDt(sale.issued_at) }}</p>
            </div>
            <div class="flex gap-2 flex-wrap justify-end">
              <Badge :color="statusColor(sale.status)">{{ statusLabel(sale.status) }}</Badge>
              <Badge v-if="sale.payment_method" color="blue">{{ sale.payment_method }}</Badge>
            </div>
          </div>

          <!-- Customer -->
          <div v-if="sale.customer" class="bg-gray-50 rounded-lg p-3 mb-4 text-sm">
            <p class="text-xs text-gray-400 mb-1 uppercase tracking-wide">Cliente</p>
            <p class="font-semibold">{{ sale.customer.first_name }} {{ sale.customer.last_name }}</p>
            <p v-if="sale.customer.company" class="text-gray-600">{{ sale.customer.company }}</p>
            <p v-if="sale.customer.address" class="text-gray-600">{{ sale.customer.address }}, {{ sale.customer.zip }} {{ sale.customer.city }}</p>
          </div>

          <!-- Items -->
          <table class="w-full text-sm mb-4">
            <thead class="bg-gray-50 border-b">
              <tr>
                <th class="th">Descrizione</th>
                <th class="th text-center">Qtà</th>
                <th class="th text-right">Prezzo</th>
                <th class="th text-right">IVA</th>
                <th class="th text-right">Totale</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in sale.items" :key="item.id" class="border-b border-gray-50">
                <td class="td">{{ item.description }}</td>
                <td class="td text-center">{{ item.qty }}</td>
                <td class="td text-right">
                  {{ fmt(item.unit_price) }}
                  <span v-if="item.discount_pct > 0" class="text-red-500 text-xs"> (-{{ item.discount_pct }}%)</span>
                </td>
                <td class="td text-right text-gray-500">{{ item.vat_rate }}%</td>
                <td class="td text-right font-medium">{{ fmt(item.line_total) }}</td>
              </tr>
            </tbody>
            <tfoot class="border-t">
              <tr>
                <td colspan="4" class="td text-right text-gray-500">Subtotale</td>
                <td class="td text-right">{{ fmt(sale.subtotal) }}</td>
              </tr>
              <tr>
                <td colspan="4" class="td text-right text-gray-500">IVA</td>
                <td class="td text-right">{{ fmt(sale.vat_amount) }}</td>
              </tr>
              <tr v-if="sale.discount_amount > 0">
                <td colspan="4" class="td text-right text-red-500">Sconto</td>
                <td class="td text-right text-red-500">- {{ fmt(sale.discount_amount) }}</td>
              </tr>
              <tr class="border-t-2">
                <td colspan="4" class="td text-right font-bold text-base">TOTALE</td>
                <td class="td text-right font-bold text-lg text-indigo-700">{{ fmt(sale.total) }}</td>
              </tr>
            </tfoot>
          </table>

          <p v-if="sale.notes" class="text-sm text-gray-500 bg-gray-50 rounded p-3">{{ sale.notes }}</p>
        </div>

        <!-- Sidebar -->
        <div class="space-y-4">
          <!-- PDF actions -->
          <div class="card p-4 space-y-2">
            <h3 class="font-semibold text-gray-700 mb-3">📄 PDF Fattura</h3>
            <p class="text-xs text-gray-500 mb-3">
              {{ hasIban ? 'Include cedola di versamento QR-bill svizzera (pag. 2)' : 'Senza cedola QR — configura IBAN prima.' }}
            </p>
            <a :href="route('invoices.pdf', sale.id)" target="_blank" class="btn-primary w-full text-center block">
              Visualizza PDF
            </a>
            <a :href="route('invoices.pdf.download', sale.id)" class="btn-outline w-full text-center block">
              ⬇️ Scarica PDF
            </a>
          </div>

          <!-- Status update -->
          <div class="card p-4">
            <h3 class="font-semibold text-gray-700 mb-3">Aggiorna stato</h3>
            <form @submit.prevent="updateStatus" class="space-y-3">
              <div>
                <label class="label">Stato</label>
                <select v-model="statusForm.status" class="input">
                  <option value="draft">Bozza</option>
                  <option value="sent">Inviata</option>
                  <option value="paid">Pagata</option>
                  <option value="cancelled">Annullata</option>
                </select>
              </div>
              <div v-if="statusForm.status === 'paid'">
                <label class="label">Metodo pagamento</label>
                <select v-model="statusForm.payment_method" class="input">
                  <option value="">—</option>
                  <option value="cash">Contanti</option>
                  <option value="card">Carta</option>
                  <option value="sumup">SumUp</option>
                  <option value="transfer">Bonifico</option>
                </select>
              </div>
              <button type="submit" class="btn-primary w-full">Salva</button>
            </form>
          </div>

          <!-- Back -->
          <Link :href="route('invoices.index')" class="btn-outline w-full text-center block">
            ← Tutte le fatture
          </Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Badge from '@/Components/Badge.vue'

const props = defineProps({
  sale:       Object,
  hasIban:    Boolean,
  hasQrBill:  Boolean,
})

const statusForm = useForm({
  status:         props.sale.status,
  payment_method: props.sale.payment_method ?? '',
})

function updateStatus() {
  statusForm.patch(route('invoices.status', props.sale.id))
}

const statusMap = {
  draft:     { label: 'Bozza',     color: 'gray' },
  sent:      { label: 'Inviata',   color: 'blue' },
  paid:      { label: 'Pagata',    color: 'green' },
  cancelled: { label: 'Annullata', color: 'red' },
}
const statusLabel = s => statusMap[s]?.label ?? s
const statusColor = s => statusMap[s]?.color ?? 'gray'

const fmt   = v => new Intl.NumberFormat('it-CH', { style: 'currency', currency: 'CHF' }).format(v ?? 0)
const fmtDt = d => d ? new Date(d).toLocaleString('it-CH', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—'
</script>
