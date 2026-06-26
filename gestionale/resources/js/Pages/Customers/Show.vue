<template>
  <AppLayout :title="customer.last_name + ' ' + customer.first_name">
    <template #header-actions>
      <Link :href="route('customers.edit', customer.id)"
        class="flex items-center gap-2 border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-50 transition">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
        </svg>
        Modifica
      </Link>
      <Link :href="route('repairs.create', { customer_id: customer.id })"
        class="flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700 transition">
        + Nuova riparazione
      </Link>
    </template>

    <div class="max-w-6xl space-y-4">

      <!-- Anagrafica + stats -->
      <div class="grid grid-cols-3 gap-4">
        <!-- Anagrafica -->
        <div class="col-span-1 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
          <div class="flex items-center gap-4 mb-5">
            <div class="w-14 h-14 rounded-full flex items-center justify-center text-white text-xl font-bold flex-shrink-0"
              :style="{ backgroundColor: avatarColor }">
              {{ (customer.first_name?.[0] ?? '') + (customer.last_name?.[0] ?? '') }}
            </div>
            <div>
              <h2 class="text-xl font-bold text-gray-900">{{ customer.last_name }} {{ customer.first_name }}</h2>
              <span v-if="customer.company" class="text-sm text-gray-500">{{ customer.company }}</span>
            </div>
          </div>

          <dl class="space-y-2 text-sm">
            <div v-if="customer.phone" class="flex items-center gap-2 text-gray-700">
              <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
              </svg>
              <a :href="'tel:' + customer.phone" class="hover:text-indigo-600">{{ customer.phone }}</a>
            </div>
            <div v-if="customer.email" class="flex items-center gap-2 text-gray-700">
              <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
              </svg>
              <a :href="'mailto:' + customer.email" class="hover:text-indigo-600 truncate">{{ customer.email }}</a>
            </div>
            <div v-if="customer.address" class="flex items-start gap-2 text-gray-600">
              <svg class="w-4 h-4 text-gray-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
              </svg>
              <span>{{ customer.address }}<br v-if="customer.city"/>{{ customer.zip }} {{ customer.city }}</span>
            </div>
            <div v-if="customer.tax_number" class="text-gray-500 text-xs pt-1">
              UID/CF: {{ customer.tax_number }}
            </div>
            <div v-if="customer.category" class="pt-2">
              <span class="px-2 py-0.5 rounded-full text-xs bg-indigo-50 text-indigo-700">{{ customer.category }}</span>
            </div>
          </dl>

          <div v-if="customer.notes" class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs text-gray-500 leading-relaxed">{{ customer.notes }}</p>
          </div>
        </div>

        <!-- Stats -->
        <div class="col-span-2 grid grid-cols-3 gap-3 content-start">
          <StatCard
            label="Totale fatturato"
            :value="'CHF ' + Number(stats.total_invoiced ?? 0).toFixed(2)"
            color="indigo"
            icon="currency" />
          <StatCard
            label="Riparazioni"
            :value="stats.repairs_count"
            :sub="stats.repairs_open + ' aperte'"
            color="blue"
            icon="wrench" />
          <StatCard
            label="Fatture emesse"
            :value="stats.invoices_count"
            :sub="stats.invoices_paid + ' pagate'"
            color="green"
            icon="document" />
          <StatCard
            label="Preventivi"
            :value="stats.quotes_count"
            :sub="stats.quotes_accepted + ' accettati'"
            color="yellow"
            icon="clipboard" />
        </div>
      </div>

      <!-- Tabs -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="flex border-b border-gray-100">
          <button v-for="tab in tabs" :key="tab.key"
            @click="activeTab = tab.key"
            :class="['px-5 py-3 text-sm font-medium transition border-b-2 -mb-px',
              activeTab === tab.key
                ? 'border-indigo-600 text-indigo-600'
                : 'border-transparent text-gray-500 hover:text-gray-700']">
            {{ tab.label }}
            <span v-if="tab.count !== undefined"
              class="ml-1.5 text-xs px-1.5 py-0.5 rounded-full"
              :class="activeTab === tab.key ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-500'">
              {{ tab.count }}
            </span>
          </button>
        </div>

        <!-- Tab: Riparazioni -->
        <div v-if="activeTab === 'repairs'" class="divide-y divide-gray-50">
          <div v-for="r in repairs" :key="r.id"
            class="flex items-center gap-4 px-5 py-3 hover:bg-gray-50 cursor-pointer"
            @click="$inertia.visit(route('repairs.show', r.id))">
            <div class="font-mono text-xs text-indigo-600 w-28 flex-shrink-0">{{ r.ticket_number }}</div>
            <div class="flex-1 min-w-0">
              <div class="text-sm font-medium text-gray-800 truncate">{{ r.device || '—' }}</div>
              <div class="text-xs text-gray-400">{{ formatDate(r.received_at) }} · {{ r.assigned_to ?? 'Non assegnato' }}</div>
            </div>
            <StatusBadge :status="r.status" :statuses="repairStatuses" />
            <div class="text-sm font-semibold text-gray-700 w-24 text-right">
              {{ r.final_cost ? 'CHF ' + Number(r.final_cost).toFixed(2) : r.estimated_cost ? '~CHF ' + Number(r.estimated_cost).toFixed(2) : '—' }}
            </div>
          </div>
          <div v-if="!repairs.length" class="px-5 py-10 text-center text-gray-400 text-sm">
            Nessuna riparazione
          </div>
        </div>

        <!-- Tab: Fatture & Preventivi -->
        <div v-if="activeTab === 'invoices'" class="divide-y divide-gray-50">
          <div v-for="inv in invoices" :key="inv.id"
            class="flex items-center gap-4 px-5 py-3 hover:bg-gray-50 cursor-pointer"
            @click="$inertia.visit(inv.type === 'quote' ? route('quotes.show', inv.id) : route('sales.show', inv.id))">
            <div class="font-mono text-xs w-32 flex-shrink-0"
              :class="inv.type === 'quote' ? 'text-yellow-600' : 'text-green-600'">
              {{ inv.invoice_number }}
            </div>
            <div class="flex-1 min-w-0">
              <div class="text-xs text-gray-400">{{ formatDate(inv.issued_at) }} · {{ inv.items_count }} voci</div>
            </div>
            <span class="text-xs px-2 py-0.5 rounded-full"
              :class="inv.type === 'quote' ? 'bg-yellow-50 text-yellow-700' : 'bg-green-50 text-green-700'">
              {{ inv.type === 'quote' ? 'Preventivo' : 'Fattura' }}
            </span>
            <span v-if="inv.quote_status === 'accepted'" class="text-xs text-green-600 font-medium">✓ Accettato</span>
            <span v-else-if="inv.quote_status === 'rejected'" class="text-xs text-red-500">✗ Rifiutato</span>
            <div class="text-sm font-semibold text-gray-700 w-24 text-right">
              CHF {{ Number(inv.total).toFixed(2) }}
            </div>
          </div>
          <div v-if="!invoices.length" class="px-5 py-10 text-center text-gray-400 text-sm">
            Nessuna fattura o preventivo
          </div>
        </div>

        <!-- Tab: Comunicazioni -->
        <div v-if="activeTab === 'communications'" class="divide-y divide-gray-50">
          <div v-for="log in communications" :key="log.id" class="px-5 py-3">
            <div class="flex items-start gap-3">
              <!-- Icona canale -->
              <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-white text-xs mt-0.5"
                :class="channelColor(log.channel)">
                {{ channelIcon(log.channel) }}
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-0.5">
                  <span class="text-xs font-semibold uppercase tracking-wide" :class="channelTextColor(log.channel)">
                    {{ log.channel }}
                  </span>
                  <span class="text-xs text-gray-400">→ {{ log.recipient }}</span>
                  <span :class="['text-xs px-1.5 py-0.5 rounded-full', log.status === 'sent' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-600']">
                    {{ log.status === 'sent' ? 'Inviato' : 'Fallito' }}
                  </span>
                  <span class="ml-auto text-xs text-gray-400">{{ formatDateTime(log.created_at) }}</span>
                </div>
                <p class="text-sm text-gray-600 whitespace-pre-line leading-relaxed">{{ log.message }}</p>
                <!-- Collegamento al lavoro -->
                <div v-if="log.notifiable_type" class="mt-1">
                  <span class="text-xs text-gray-400">
                    Ref: {{ log.notifiable_type.split('\\').pop() }} #{{ log.notifiable_id }}
                  </span>
                </div>
              </div>
            </div>
          </div>
          <div v-if="!communications.length" class="px-5 py-10 text-center text-gray-400 text-sm">
            Nessuna comunicazione registrata
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import StatCard from '@/Components/StatCard.vue'

const props = defineProps({
  customer:       Object,
  stats:          Object,
  repairs:        Array,
  invoices:       Array,
  communications: Array,
})

const activeTab = ref('repairs')

const tabs = computed(() => [
  { key: 'repairs',        label: 'Riparazioni',    count: props.repairs.length },
  { key: 'invoices',       label: 'Fatture',        count: props.invoices.length },
  { key: 'communications', label: 'Comunicazioni',  count: props.communications.length },
])

const repairStatuses = {
  received:      { label: 'Ricevuto',          color: 'blue' },
  diagnosed:     { label: 'Diagnosticato',     color: 'purple' },
  in_progress:   { label: 'In lavorazione',    color: 'yellow' },
  waiting_parts: { label: 'Attesa ricambi',    color: 'orange' },
  ready:         { label: 'Pronto',            color: 'green' },
  delivered:     { label: 'Consegnato',        color: 'gray' },
  cancelled:     { label: 'Annullato',         color: 'red' },
}

const palette = ['#6366f1','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#f97316','#84cc16']
const avatarColor = computed(() => {
  const name = props.customer.first_name + props.customer.last_name
  let hash = 0
  for (const ch of name) hash = ch.charCodeAt(0) + ((hash << 5) - hash)
  return palette[Math.abs(hash) % palette.length]
})

function channelColor(ch) {
  return { sms: 'bg-blue-500', whatsapp: 'bg-green-500', email: 'bg-purple-500' }[ch] ?? 'bg-gray-400'
}
function channelTextColor(ch) {
  return { sms: 'text-blue-600', whatsapp: 'text-green-600', email: 'text-purple-600' }[ch] ?? 'text-gray-500'
}
function channelIcon(ch) {
  return { sms: 'SMS', whatsapp: 'WA', email: '✉' }[ch] ?? ch[0].toUpperCase()
}

function formatDate(d) {
  return d ? new Date(d).toLocaleDateString('it-CH', { day: '2-digit', month: '2-digit', year: 'numeric' }) : ''
}
function formatDateTime(d) {
  return d ? new Date(d).toLocaleString('it-CH', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : ''
}
</script>
