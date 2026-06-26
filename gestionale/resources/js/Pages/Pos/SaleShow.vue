<template>
  <AppLayout :title="`Vendita ${sale.invoice_number}`">
    <div class="max-w-2xl mx-auto space-y-6">
      <div class="card p-5">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h2 class="text-lg font-bold">{{ sale.invoice_number }}</h2>
            <p class="text-sm text-gray-500">{{ fmtDt(sale.issued_at) }}</p>
          </div>
          <div class="flex gap-2">
            <Badge color="green">{{ sale.status }}</Badge>
            <Badge color="blue">{{ sale.payment_method }}</Badge>
          </div>
        </div>

        <div v-if="sale.customer" class="text-sm mb-4 text-gray-600">
          Cliente: <span class="font-medium">{{ sale.customer.first_name }} {{ sale.customer.last_name }}</span>
        </div>

        <table class="w-full text-sm mb-4">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="th">Descrizione</th>
              <th class="th text-right">Qtà</th>
              <th class="th text-right">Prezzo</th>
              <th class="th text-right">Totale</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in sale.items" :key="item.id" class="border-b border-gray-50">
              <td class="td">{{ item.description }}</td>
              <td class="td text-right">{{ item.qty }}</td>
              <td class="td text-right">{{ fmt(item.unit_price) }}</td>
              <td class="td text-right font-medium">{{ fmt(item.line_total) }}</td>
            </tr>
          </tbody>
          <tfoot class="border-t font-medium">
            <tr>
              <td colspan="3" class="td text-right text-gray-500">Subtotale</td>
              <td class="td text-right">{{ fmt(sale.subtotal) }}</td>
            </tr>
            <tr>
              <td colspan="3" class="td text-right text-gray-500">IVA</td>
              <td class="td text-right">{{ fmt(sale.vat_amount) }}</td>
            </tr>
            <tr v-if="sale.discount_amount > 0">
              <td colspan="3" class="td text-right text-red-500">Sconto</td>
              <td class="td text-right text-red-500">- {{ fmt(sale.discount_amount) }}</td>
            </tr>
            <tr>
              <td colspan="3" class="td text-right text-lg font-bold">TOTALE</td>
              <td class="td text-right text-lg font-bold text-green-700">{{ fmt(sale.total) }}</td>
            </tr>
          </tfoot>
        </table>

        <div class="flex gap-3 justify-end flex-wrap">
          <button @click="printBrowser" class="btn-outline">🖨 Stampa</button>
          <button v-if="sale.status !== 'cancelled'" @click="voidSale" class="btn-outline text-red-600 border-red-300 hover:bg-red-50">
            Annulla vendita
          </button>
          <Link :href="route('pos.sales')" class="btn-outline">← Torna alla lista</Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Badge from '@/Components/Badge.vue'

const props = defineProps({
  sale:    Object,
  receipt: Object,
})

function voidSale() {
  if (confirm('Annullare questa vendita? Lo stock verrà ripristinato.')) {
    router.patch(route('pos.sales.void', props.sale.id))
  }
}

function printBrowser() {
  const r = props.receipt
  const fmt = v => new Intl.NumberFormat('it-CH', { style: 'currency', currency: 'CHF' }).format(v ?? 0)
  const w = window.open('', '_blank', 'width=400,height=600')
  w.document.write(`<html><head><style>@page{size:80mm auto;margin:5mm}body{font-family:monospace;font-size:12px;width:70mm}.c{text-align:center}.b{font-weight:bold}.r{display:flex;justify-content:space-between}hr{border-top:1px dashed #ccc}</style></head><body onload="window.print();window.close()">
    <div class="c b" style="font-size:14px">${r.tenant_name}</div>
    <div class="c">${r.tenant_address}</div>
    <div class="c" style="margin-bottom:8px">${r.tenant_phone}</div>
    <div class="r"><span>${r.issued_at}</span><span>${r.invoice_number}</span></div>
    ${r.customer ? `<div>Cliente: ${r.customer}</div>` : ''}
    <hr>
    ${r.items.map(i=>`<div>${i.description}</div><div class="r" style="color:#666"><span>${i.qty} × ${fmt(i.unit_price)}</span><span>${fmt(i.line_total)}</span></div>`).join('')}
    <hr>
    <div class="r b" style="font-size:14px"><span>TOTALE</span><span>${fmt(r.total)}</span></div>
    <div class="r"><span>${r.payment_method}</span><span>${fmt(r.paid_amount)}</span></div>
    ${r.change > 0 ? `<div class="r" style="color:green"><span>Resto</span><span>${fmt(r.change)}</span></div>` : ''}
    <div class="c" style="margin-top:12px;color:#888">${r.tenant_uid ?? ''}</div>
    <div class="c" style="color:#888">Grazie per la fiducia!</div>
  </body></html>`)
  w.document.close()
}

const fmt   = v => new Intl.NumberFormat('it-CH', { style: 'currency', currency: 'CHF' }).format(v ?? 0)
const fmtDt = d => d ? new Date(d).toLocaleString('it-CH', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—'
</script>
