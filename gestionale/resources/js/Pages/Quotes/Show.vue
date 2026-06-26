<template>
  <AppLayout :title="'Preventivo ' + quote.invoice_number">
    <div class="max-w-4xl space-y-4">

      <!-- Header info -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex justify-between items-start">
        <div>
          <h2 class="text-2xl font-bold text-gray-900 font-mono">{{ quote.invoice_number }}</h2>
          <p class="text-gray-500 mt-1">{{ quote.customer.first_name }} {{ quote.customer.last_name }}</p>
          <p class="text-gray-400 text-sm">{{ quote.customer.email }} · {{ quote.customer.phone }}</p>
        </div>
        <div class="text-right space-y-2">
          <div>
            <span :class="['px-3 py-1 rounded-full text-sm font-medium', statusClass]">
              {{ statusLabel }}
            </span>
          </div>
          <div v-if="quote.quote_status" class="text-sm">
            <span v-if="quote.quote_status === 'accepted'" class="text-green-600 font-semibold">✓ Accettato</span>
            <span v-if="quote.quote_status === 'rejected'" class="text-red-600 font-semibold">✗ Rifiutato</span>
          </div>
          <div v-if="quote.quote_responded_at" class="text-xs text-gray-400">
            {{ formatDateTime(quote.quote_responded_at) }}
          </div>
          <div v-if="quote.quote_rejection_reason" class="text-xs text-gray-500 max-w-xs text-right">
            "{{ quote.quote_rejection_reason }}"
          </div>
        </div>
      </div>

      <!-- Righe -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <table class="w-full text-sm">
          <thead class="text-gray-500 text-xs border-b border-gray-100">
            <tr>
              <th class="pb-2 text-left">Descrizione</th>
              <th class="pb-2 text-right">Qtà</th>
              <th class="pb-2 text-right">Prezzo unit.</th>
              <th class="pb-2 text-right">Sconto</th>
              <th class="pb-2 text-right">IVA</th>
              <th class="pb-2 text-right">Totale</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-for="item in quote.items" :key="item.id">
              <td class="py-2 text-gray-800">{{ item.description }}</td>
              <td class="py-2 text-right text-gray-600">{{ item.qty }}</td>
              <td class="py-2 text-right text-gray-600">CHF {{ Number(item.unit_price).toFixed(2) }}</td>
              <td class="py-2 text-right text-gray-500">{{ item.discount_pct > 0 ? item.discount_pct + '%' : '—' }}</td>
              <td class="py-2 text-right text-gray-500">{{ item.vat_rate }}%</td>
              <td class="py-2 text-right font-medium">CHF {{ Number(item.line_total).toFixed(2) }}</td>
            </tr>
          </tbody>
        </table>

        <div class="mt-4 flex justify-end">
          <div class="w-56 space-y-1 text-sm">
            <div class="flex justify-between text-gray-600">
              <span>Imponibile</span><span>CHF {{ Number(quote.subtotal).toFixed(2) }}</span>
            </div>
            <div class="flex justify-between text-gray-600">
              <span>IVA</span><span>CHF {{ Number(quote.vat_amount).toFixed(2) }}</span>
            </div>
            <div class="flex justify-between font-bold text-gray-900 text-base border-t pt-2 mt-1">
              <span>Totale</span><span>CHF {{ Number(quote.total).toFixed(2) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Note -->
      <div v-if="quote.notes" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-sm font-medium text-gray-700 mb-2">Note</h3>
        <p class="text-sm text-gray-600 whitespace-pre-line">{{ quote.notes }}</p>
      </div>

      <!-- Invio -->
      <div v-if="!quote.quote_status" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Invia al cliente</h3>

        <!-- Link condivisione -->
        <div class="flex items-center gap-2 bg-gray-50 rounded-lg px-3 py-2 mb-4">
          <span class="text-xs text-gray-500 font-mono flex-1 truncate">{{ quote.quote_url }}</span>
          <button @click="copyLink" class="text-xs text-indigo-600 hover:underline flex-shrink-0">
            {{ copied ? '✓ Copiato!' : 'Copia link' }}
          </button>
        </div>

        <form @submit.prevent="sendQuote" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Invia tramite</label>
            <div class="flex flex-wrap gap-3">
              <label v-for="ch in channelOptions" :key="ch.value"
                class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" :value="ch.value" v-model="sendForm.channels"
                  :disabled="ch.requires && !ch.available"
                  class="rounded text-indigo-600" />
                <span class="text-sm" :class="ch.requires && !ch.available ? 'text-gray-400' : 'text-gray-700'">
                  {{ ch.label }}
                  <span v-if="ch.requires && !ch.available" class="text-xs text-gray-400">(non configurato)</span>
                </span>
              </label>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Messaggio personalizzato <span class="text-gray-400 font-normal">(opzionale — il link viene aggiunto in fondo)</span>
            </label>
            <textarea v-model="sendForm.custom_message" rows="3"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
              placeholder="Lascia vuoto per usare il messaggio predefinito..."></textarea>
          </div>

          <button type="submit" :disabled="sendForm.processing || sendForm.channels.length === 0"
            class="px-5 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 disabled:opacity-50">
            {{ sendForm.processing ? 'Invio...' : 'Invia preventivo' }}
          </button>
        </form>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({ quote: Object })

const statusMap = {
  draft:     ['Bozza',    'bg-gray-100 text-gray-600'],
  sent:      ['Inviato',  'bg-blue-100 text-blue-700'],
  paid:      ['Accettato/Pagato', 'bg-green-100 text-green-700'],
  cancelled: ['Annullato','bg-red-100 text-red-700'],
}
const statusLabel = computed(() => statusMap[props.quote.status]?.[0] ?? props.quote.status)
const statusClass = computed(() => statusMap[props.quote.status]?.[1] ?? 'bg-gray-100 text-gray-600')

const channelOptions = [
  { value: 'email',     label: '📧 Email',     available: !!props.quote.customer?.email },
  { value: 'sms',       label: '📱 SMS',       available: !!props.quote.customer?.phone },
  { value: 'whatsapp',  label: '💬 WhatsApp',  available: !!props.quote.customer?.phone },
]

const sendForm = useForm({ channels: [], custom_message: '' })
const copied = ref(false)

function copyLink() {
  navigator.clipboard.writeText(props.quote.quote_url)
  copied.value = true
  setTimeout(() => { copied.value = false }, 2000)
}

function sendQuote() {
  sendForm.post(route('quotes.send', props.quote.id))
}

function formatDateTime(d) {
  return d ? new Date(d).toLocaleString('it-CH') : ''
}
</script>
