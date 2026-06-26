<template>
  <AppLayout title="Contabilità">
    <!-- Filtri periodo -->
    <div class="flex flex-wrap gap-3 mb-6">
      <select v-model="filters.year" @change="load" class="input-sm">
        <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
      </select>
      <select v-model="filters.month" @change="load" class="input-sm">
        <option :value="0">Tutto l'anno</option>
        <option v-for="(m, i) in months" :key="i" :value="i+1">{{ m }}</option>
      </select>
    </div>

    <!-- KPI cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
      <KpiCard label="Entrate totali" :value="fmt(stats.totalIncome)" color="green" icon="arrow-trending-up" />
      <KpiCard label="Uscite totali"  :value="fmt(stats.totalExpenses)" color="red"   icon="arrow-trending-down" />
      <KpiCard label="Margine netto"  :value="fmt(stats.margin)"
               :color="stats.margin >= 0 ? 'indigo' : 'orange'" icon="scale" />
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
      <!-- Grafico mensile -->
      <div class="xl:col-span-2 card p-4">
        <h3 class="font-semibold text-gray-700 mb-4">Andamento mensile {{ filters.year }}</h3>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-gray-500 border-b">
                <th class="text-left pb-2">Mese</th>
                <th class="text-right pb-2 text-green-600">Entrate</th>
                <th class="text-right pb-2 text-red-500">Uscite</th>
                <th class="text-right pb-2 text-indigo-600">Margine</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in stats.monthly" :key="row.month"
                  class="border-b border-gray-50 hover:bg-gray-50">
                <td class="py-2">{{ months[row.month - 1] }}</td>
                <td class="text-right text-green-600">{{ fmt(row.income) }}</td>
                <td class="text-right text-red-500">{{ fmt(row.expenses) }}</td>
                <td class="text-right font-medium"
                    :class="row.margin >= 0 ? 'text-indigo-600' : 'text-orange-500'">
                  {{ fmt(row.margin) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Spese per categoria -->
      <div class="card p-4">
        <h3 class="font-semibold text-gray-700 mb-4">Spese per categoria</h3>
        <div v-if="stats.byCategory.length" class="space-y-3">
          <div v-for="cat in stats.byCategory" :key="cat.name" class="flex items-center gap-2">
            <span class="w-3 h-3 rounded-full flex-shrink-0" :style="{ background: cat.color }"></span>
            <span class="flex-1 text-sm text-gray-700 truncate">{{ cat.name }}</span>
            <span class="text-sm font-medium">{{ fmt(cat.total) }}</span>
          </div>
        </div>
        <p v-else class="text-sm text-gray-400 italic">Nessuna spesa nel periodo</p>

        <!-- Quick links -->
        <div class="mt-6 space-y-2">
          <Link :href="route('accounting.expenses')" class="btn-outline w-full text-center block">
            Gestisci spese
          </Link>
          <Link :href="route('accounting.purchases')" class="btn-outline w-full text-center block">
            Ordini fornitore
          </Link>
          <Link :href="route('accounting.cash')" class="btn-outline w-full text-center block">
            Movimenti cassa
          </Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import KpiCard from '@/Components/KpiCard.vue'

const props = defineProps({
  stats: Object,
  year: Number,
  month: Number,
  categories: Array,
})

const filters = ref({ year: props.year, month: props.month })

const years = computed(() => {
  const y = new Date().getFullYear()
  return [y + 1, y, y - 1, y - 2]
})

const months = ['Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic']

function load() {
  router.get(route('accounting.index'), filters.value, { preserveState: true, replace: true })
}

function fmt(val) {
  return new Intl.NumberFormat('it-CH', { style: 'currency', currency: 'CHF' }).format(val ?? 0)
}
</script>
