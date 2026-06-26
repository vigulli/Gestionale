<template>
  <div class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
      <!-- Success header -->
      <div class="bg-green-500 rounded-t-2xl p-6 text-white text-center">
        <div class="text-4xl mb-2">✓</div>
        <p class="text-xl font-bold">Pagamento completato</p>
        <p class="text-green-100 text-sm mt-1">{{ receipt.invoice_number }}</p>
      </div>

      <!-- Receipt preview -->
      <div class="p-4 font-mono text-xs border-b">
        <div class="text-center font-bold text-base mb-1">{{ receipt.tenant_name }}</div>
        <div class="text-center text-gray-500 mb-1">{{ receipt.tenant_address }}</div>
        <div class="text-center text-gray-500 mb-3">{{ receipt.tenant_phone }}</div>
        <div class="flex justify-between mb-1">
          <span>{{ receipt.issued_at }}</span>
          <span>{{ receipt.invoice_number }}</span>
        </div>
        <div v-if="receipt.customer" class="text-gray-600 mb-2">Cliente: {{ receipt.customer }}</div>
        <div class="border-t border-dashed my-2"></div>
        <div v-for="item in receipt.items" :key="item.description" class="mb-1">
          <div>{{ item.description }}</div>
          <div class="flex justify-between text-gray-600">
            <span>{{ item.qty }} × {{ fmtPrice(item.unit_price) }}</span>
            <span>{{ fmtPrice(item.line_total) }}</span>
          </div>
        </div>
        <div class="border-t border-dashed my-2"></div>
        <div class="flex justify-between">
          <span>Subtotale</span>
          <span>{{ fmtPrice(receipt.subtotal) }}</span>
        </div>
        <div class="flex justify-between text-gray-500">
          <span>IVA</span>
          <span>{{ fmtPrice(receipt.vat_amount) }}</span>
        </div>
        <div v-if="receipt.discount_amount > 0" class="flex justify-between text-red-600">
          <span>Sconto</span>
          <span>- {{ fmtPrice(receipt.discount_amount) }}</span>
        </div>
        <div class="flex justify-between font-bold text-base mt-1">
          <span>TOTALE</span>
          <span>{{ fmtPrice(receipt.total) }}</span>
        </div>
        <div class="flex justify-between mt-1 capitalize">
          <span>{{ receipt.payment_method }}</span>
          <span>{{ fmtPrice(receipt.paid_amount) }}</span>
        </div>
        <div v-if="receipt.change > 0" class="flex justify-between text-green-600 font-medium">
          <span>Resto</span>
          <span>{{ fmtPrice(receipt.change) }}</span>
        </div>
        <div v-if="receipt.tenant_uid" class="text-center text-gray-400 mt-3">{{ receipt.tenant_uid }}</div>
        <div class="text-center text-gray-400 mt-1">Grazie per la fiducia!</div>
      </div>

      <!-- Printer IP setting -->
      <div class="px-4 py-2 bg-gray-50 border-b flex items-center gap-2">
        <label class="text-xs text-gray-500 whitespace-nowrap">IP Stampante Star</label>
        <input v-model="localPrinterIp" @change="savePrinterIp"
               type="text" placeholder="es. 192.168.1.100"
               class="input-sm flex-1 font-mono text-xs" />
      </div>

      <!-- Actions -->
      <div class="p-4 flex flex-col gap-2">
        <button @click="printReceipt" :disabled="!localPrinterIp" class="btn-primary w-full" :class="!localPrinterIp && 'opacity-40'">
          🖨 Stampa scontrino
        </button>
        <button @click="printBrowser" class="btn-outline w-full">
          🖨 Stampa browser
        </button>
        <button @click="emit('new-sale')" class="btn-primary w-full bg-green-600 border-green-600">
          + Nuova vendita
        </button>
        <button @click="emit('close')" class="text-sm text-gray-400 hover:text-gray-600 text-center">
          Chiudi
        </button>
      </div>
    </div>
  </div>

  <!-- Print frame (hidden) -->
  <div ref="printArea" class="hidden">
    <div style="font-family:monospace;font-size:12px;width:300px;padding:8px">
      <div style="text-align:center;font-weight:bold;font-size:14px">{{ receipt.tenant_name }}</div>
      <div style="text-align:center;color:#555">{{ receipt.tenant_address }}</div>
      <div style="text-align:center;color:#555;margin-bottom:8px">{{ receipt.tenant_phone }}</div>
      <div style="display:flex;justify-content:space-between">
        <span>{{ receipt.issued_at }}</span><span>{{ receipt.invoice_number }}</span>
      </div>
      <hr style="border-top:1px dashed #ccc;margin:6px 0">
      <div v-for="item in receipt.items" :key="item.description" style="margin-bottom:4px">
        <div>{{ item.description }}</div>
        <div style="display:flex;justify-content:space-between;color:#666">
          <span>{{ item.qty }} × {{ fmtPrice(item.unit_price) }}</span>
          <span>{{ fmtPrice(item.line_total) }}</span>
        </div>
      </div>
      <hr style="border-top:1px dashed #ccc;margin:6px 0">
      <div style="display:flex;justify-content:space-between;font-weight:bold;font-size:14px">
        <span>TOTALE</span><span>{{ fmtPrice(receipt.total) }}</span>
      </div>
      <div style="text-align:center;color:#888;margin-top:12px">Grazie per la fiducia!</div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  receipt:   Object,
  printerIp: String,
})
const emit = defineEmits(['close', 'new-sale'])

const localPrinterIp = ref(props.printerIp || localStorage.getItem('starPrinterIp') || '')

function savePrinterIp() {
  localStorage.setItem('starPrinterIp', localPrinterIp.value)
}

const fmtPrice = v => new Intl.NumberFormat('it-CH', { style: 'currency', currency: 'CHF' }).format(v ?? 0)

// Star WebPRNT / TSP100III over HTTP
async function printReceipt() {
  const ip = localPrinterIp.value.trim()
  if (!ip) return

  // Build StarPRNT XML for 80mm paper
  const lines = [
    `<ESC>@</ESC>`, // init
    `<ESC>a<n>1</n></ESC>`, // center
    `<b>${escXml(props.receipt.tenant_name)}</b>\n`,
    `${escXml(props.receipt.tenant_address)}\n`,
    `${escXml(props.receipt.tenant_phone)}\n\n`,
    `<ESC>a<n>0</n></ESC>`, // left
    `${props.receipt.issued_at}  ${props.receipt.invoice_number}\n`,
    props.receipt.customer ? `Cliente: ${escXml(props.receipt.customer)}\n` : '',
    `--------------------------------\n`,
    ...props.receipt.items.map(i =>
      `${escXml(i.description)}\n  ${i.qty} x ${fmtPrice(i.unit_price)}${' '.repeat(Math.max(1, 20 - String(i.qty).length - fmtPrice(i.unit_price).length))}${fmtPrice(i.line_total)}\n`
    ),
    `--------------------------------\n`,
    `Subtotale${' '.repeat(23 - fmtPrice(props.receipt.subtotal).length)}${fmtPrice(props.receipt.subtotal)}\n`,
    `IVA${' '.repeat(29 - fmtPrice(props.receipt.vat_amount).length)}${fmtPrice(props.receipt.vat_amount)}\n`,
    props.receipt.discount_amount > 0 ? `Sconto${' '.repeat(26 - fmtPrice(props.receipt.discount_amount).length)}-${fmtPrice(props.receipt.discount_amount)}\n` : '',
    `<b>TOTALE${' '.repeat(26 - fmtPrice(props.receipt.total).length)}${fmtPrice(props.receipt.total)}</b>\n`,
    `${props.receipt.payment_method}${' '.repeat(32 - props.receipt.payment_method.length - fmtPrice(props.receipt.paid_amount).length)}${fmtPrice(props.receipt.paid_amount)}\n`,
    props.receipt.change > 0 ? `Resto${' '.repeat(27 - fmtPrice(props.receipt.change).length)}${fmtPrice(props.receipt.change)}\n` : '',
    `\n`,
    `<ESC>a<n>1</n></ESC>`,
    props.receipt.tenant_uid ? `${escXml(props.receipt.tenant_uid)}\n` : '',
    `Grazie per la fiducia!\n`,
    `\n\n\n`,
    `<ESC>m</ESC>`, // cut
  ]

  const xml = `<?xml version="1.0" encoding="utf-8"?><PrintRequestDocument version="1.00"><PrintCommand>${lines.join('')}</PrintCommand></PrintRequestDocument>`

  try {
    await fetch(`http://${ip}/StarWebPRNT/SendMessage`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/xml; charset=utf-8', 'SOAPAction': '""' },
      body: xml,
    })
  } catch (e) {
    alert(`Impossibile raggiungere la stampante su ${ip}. Verifica IP e connessione LAN.\n\n${e.message}`)
  }
}

function printBrowser() {
  const w = window.open('', '_blank', 'width=400,height=600')
  w.document.write(`<html><head><style>
    @page { size: 80mm auto; margin: 5mm; }
    body { font-family: monospace; font-size: 12px; width: 70mm; }
    .center { text-align: center; } .bold { font-weight: bold; } hr { border-top: 1px dashed #ccc; }
    .row { display: flex; justify-content: space-between; }
  </style></head><body onload="window.print();window.close()">
    <div class="center bold" style="font-size:14px">${escHtml(props.receipt.tenant_name)}</div>
    <div class="center">${escHtml(props.receipt.tenant_address)}</div>
    <div class="center" style="margin-bottom:8px">${escHtml(props.receipt.tenant_phone)}</div>
    <div class="row"><span>${props.receipt.issued_at}</span><span>${props.receipt.invoice_number}</span></div>
    <hr>
    ${props.receipt.items.map(i => `<div>${escHtml(i.description)}</div><div class="row" style="color:#666"><span>${i.qty} × ${fmtPrice(i.unit_price)}</span><span>${fmtPrice(i.line_total)}</span></div>`).join('')}
    <hr>
    <div class="row bold" style="font-size:14px"><span>TOTALE</span><span>${fmtPrice(props.receipt.total)}</span></div>
    <div class="row"><span>${escHtml(props.receipt.payment_method)}</span><span>${fmtPrice(props.receipt.paid_amount)}</span></div>
    ${props.receipt.change > 0 ? `<div class="row" style="color:green"><span>Resto</span><span>${fmtPrice(props.receipt.change)}</span></div>` : ''}
    <div class="center" style="margin-top:12px;color:#888">${props.receipt.tenant_uid ?? ''}</div>
    <div class="center" style="color:#888">Grazie per la fiducia!</div>
  </body></html>`)
  w.document.close()
}

function escXml(s) { return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;') }
function escHtml(s) { return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;') }
</script>
