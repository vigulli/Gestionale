<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-2xl mx-auto px-4 py-10">

      <!-- Header tenant -->
      <div class="text-center mb-8">
        <img v-if="$page.props.tenant?.logo_url" :src="$page.props.tenant.logo_url" class="h-12 mx-auto mb-4" />
        <h1 class="text-xl font-bold text-gray-900">{{ $page.props.tenant?.name }}</h1>
      </div>

      <!-- Già risposto -->
      <div v-if="already_responded" class="bg-white rounded-2xl shadow-lg p-8 text-center">
        <div v-if="quote.quote_status === 'accepted'" class="text-green-500 text-5xl mb-4">✓</div>
        <div v-else class="text-red-400 text-5xl mb-4">✗</div>
        <h2 class="text-xl font-bold text-gray-800 mb-2">
          {{ quote.quote_status === 'accepted' ? 'Preventivo accettato' : 'Preventivo rifiutato' }}
        </h2>
        <p class="text-gray-500 text-sm">Hai già risposto a questo preventivo.</p>
      </div>

      <template v-else>
        <!-- Riepilogo preventivo -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-4">
          <div class="flex justify-between items-start mb-4">
            <div>
              <h2 class="text-lg font-bold text-gray-900">Preventivo {{ quote.invoice_number }}</h2>
              <p class="text-gray-500 text-sm">Per {{ quote.customer_name }}</p>
            </div>
            <div class="text-right">
              <div class="text-2xl font-bold text-gray-900">CHF {{ Number(quote.total).toFixed(2) }}</div>
              <div v-if="quote.due_at" class="text-xs text-gray-400 mt-1">
                Valido fino al {{ formatDate(quote.due_at) }}
              </div>
            </div>
          </div>

          <!-- Righe -->
          <table class="w-full text-sm mb-4">
            <thead class="text-gray-500 text-xs border-b border-gray-100">
              <tr>
                <th class="pb-2 text-left font-medium">Descrizione</th>
                <th class="pb-2 text-right font-medium">Qtà</th>
                <th class="pb-2 text-right font-medium">Totale</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr v-for="(item, i) in quote.items" :key="i">
                <td class="py-2 text-gray-700">{{ item.description }}</td>
                <td class="py-2 text-right text-gray-600">{{ item.qty }}</td>
                <td class="py-2 text-right font-medium">CHF {{ Number(item.line_total).toFixed(2) }}</td>
              </tr>
            </tbody>
          </table>

          <div class="border-t border-gray-100 pt-3 space-y-1 text-sm">
            <div class="flex justify-between text-gray-500">
              <span>Imponibile</span><span>CHF {{ Number(quote.subtotal).toFixed(2) }}</span>
            </div>
            <div class="flex justify-between text-gray-500">
              <span>IVA</span><span>CHF {{ Number(quote.vat_amount).toFixed(2) }}</span>
            </div>
            <div class="flex justify-between font-bold text-gray-900 text-base">
              <span>Totale</span><span>CHF {{ Number(quote.total).toFixed(2) }}</span>
            </div>
          </div>

          <div v-if="quote.notes" class="mt-4 p-3 bg-gray-50 rounded-lg text-sm text-gray-600 whitespace-pre-line">
            {{ quote.notes }}
          </div>
        </div>

        <!-- Azioni -->
        <div class="bg-white rounded-2xl shadow-lg p-6">
          <h3 class="font-semibold text-gray-800 mb-4">Cosa vuoi fare?</h3>

          <!-- Accetta -->
          <div v-if="!action" class="flex flex-col sm:flex-row gap-3">
            <button @click="action = 'accepted'"
              class="flex-1 py-3 bg-green-600 text-white rounded-xl font-semibold hover:bg-green-700 transition text-center">
              ✓ Accetto il preventivo
            </button>
            <button @click="action = 'rejected'"
              class="flex-1 py-3 border-2 border-red-200 text-red-600 rounded-xl font-semibold hover:bg-red-50 transition text-center">
              ✗ Rifiuto il preventivo
            </button>
          </div>

          <!-- Conferma accettazione -->
          <div v-if="action === 'accepted'" class="space-y-4">
            <div class="flex items-center gap-3 p-4 bg-green-50 rounded-xl">
              <span class="text-green-600 text-2xl">✓</span>
              <div>
                <div class="font-semibold text-green-800">Stai accettando il preventivo</div>
                <div class="text-sm text-green-700">CHF {{ Number(quote.total).toFixed(2) }}</div>
              </div>
            </div>
            <div class="flex gap-3">
              <button @click="action = null"
                class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50">
                Indietro
              </button>
              <button @click="submit"
                :disabled="submitting"
                class="flex-1 py-2 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 disabled:opacity-50">
                {{ submitting ? 'Conferma...' : 'Conferma accettazione' }}
              </button>
            </div>
          </div>

          <!-- Conferma rifiuto -->
          <div v-if="action === 'rejected'" class="space-y-4">
            <div class="flex items-center gap-3 p-4 bg-red-50 rounded-xl">
              <span class="text-red-500 text-2xl">✗</span>
              <div class="font-semibold text-red-700">Stai rifiutando il preventivo</div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Motivo del rifiuto <span class="text-gray-400 font-normal">(opzionale)</span>
              </label>
              <textarea v-model="rejectionReason" rows="3"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-400"
                placeholder="Troppo costoso, non mi serve più..."></textarea>
            </div>
            <div class="flex gap-3">
              <button @click="action = null"
                class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50">
                Indietro
              </button>
              <button @click="submit"
                :disabled="submitting"
                class="flex-1 py-2 bg-red-600 text-white rounded-lg font-semibold hover:bg-red-700 disabled:opacity-50">
                {{ submitting ? 'Invio...' : 'Conferma rifiuto' }}
              </button>
            </div>
          </div>
        </div>
      </template>

    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  quote:             Object,
  already_responded: Boolean,
  token:             String,
})

const action          = ref(null)
const rejectionReason = ref('')
const submitting      = ref(false)

function submit() {
  submitting.value = true
  router.post(`/quote/${props.token}/respond`, {
    action:           action.value,
    rejection_reason: rejectionReason.value,
  }, {
    onFinish: () => { submitting.value = false },
  })
}

function formatDate(d) {
  return d ? new Date(d).toLocaleDateString('it-CH', { day: '2-digit', month: 'long', year: 'numeric' }) : ''
}
</script>
