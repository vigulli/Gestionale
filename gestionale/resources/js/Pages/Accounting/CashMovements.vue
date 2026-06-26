<template>
  <AppLayout title="Movimenti cassa">
    <div class="flex flex-wrap gap-3 mb-6 items-center">
      <select v-model="filterType" @change="load" class="input-sm">
        <option value="">Tutti</option>
        <option value="in">Entrate</option>
        <option value="out">Uscite</option>
      </select>
      <select v-model="filterYear" @change="load" class="input-sm">
        <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
      </select>
      <select v-model="filterMonth" @change="load" class="input-sm">
        <option :value="0">Tutti i mesi</option>
        <option v-for="(m, i) in months" :key="i" :value="i+1">{{ m }}</option>
      </select>

      <!-- Totali -->
      <div class="ml-auto flex gap-4 text-sm">
        <span class="text-green-600 font-medium">+ {{ fmt(totalIn) }}</span>
        <span class="text-red-500 font-medium">- {{ fmt(totalOut) }}</span>
      </div>

      <button @click="showForm = true" class="btn-primary">+ Nuovo movimento</button>
    </div>

    <div class="card overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="th">Data</th>
            <th class="th">Descrizione</th>
            <th class="th">Categoria</th>
            <th class="th">Metodo</th>
            <th class="th text-right">Entrata</th>
            <th class="th text-right">Uscita</th>
            <th class="th"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="m in movements.data" :key="m.id" class="tr-hover">
            <td class="td">{{ fmtDate(m.movement_date) }}</td>
            <td class="td font-medium">{{ m.description }}</td>
            <td class="td">
              <span v-if="m.category" class="badge" :style="{ background: m.category.color + '22', color: m.category.color }">
                {{ m.category.name }}
              </span>
            </td>
            <td class="td capitalize text-gray-500">{{ m.payment_method }}</td>
            <td class="td text-right text-green-600">{{ m.type === 'in' ? fmt(m.amount) : '' }}</td>
            <td class="td text-right text-red-500">{{ m.type === 'out' ? fmt(m.amount) : '' }}</td>
            <td class="td">
              <button @click="deleteMovement(m.id)" class="icon-btn text-gray-400 hover:text-red-500">🗑</button>
            </td>
          </tr>
          <tr v-if="!movements.data.length">
            <td colspan="7" class="td text-center text-gray-400 py-8">Nessun movimento trovato</td>
          </tr>
        </tbody>
      </table>
    </div>

    <Modal :open="showForm" @close="showForm = false" title="Nuovo movimento">
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="label">Tipo *</label>
          <div class="flex gap-3">
            <label class="flex items-center gap-2 cursor-pointer">
              <input v-model="form.type" type="radio" value="in" class="text-green-500" />
              <span class="text-green-600 font-medium">Entrata</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <input v-model="form.type" type="radio" value="out" class="text-red-500" />
              <span class="text-red-600 font-medium">Uscita</span>
            </label>
          </div>
        </div>
        <div>
          <label class="label">Descrizione *</label>
          <input v-model="form.description" type="text" class="input" required />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="label">Importo (CHF) *</label>
            <input v-model="form.amount" type="number" step="0.01" min="0.01" class="input" required />
          </div>
          <div>
            <label class="label">Categoria</label>
            <select v-model="form.expense_category_id" class="input">
              <option :value="null">—</option>
              <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>
          <div>
            <label class="label">Metodo *</label>
            <select v-model="form.payment_method" class="input" required>
              <option value="cash">Contanti</option>
              <option value="card">Carta</option>
              <option value="transfer">Bonifico</option>
              <option value="check">Assegno</option>
            </select>
          </div>
          <div>
            <label class="label">Data *</label>
            <input v-model="form.movement_date" type="date" class="input" required />
          </div>
          <div class="col-span-2">
            <label class="label">Riferimento</label>
            <input v-model="form.reference" type="text" class="input" />
          </div>
          <div class="col-span-2">
            <label class="label">Note</label>
            <textarea v-model="form.notes" class="input" rows="2"></textarea>
          </div>
        </div>
        <div class="flex justify-end gap-3">
          <button type="button" @click="showForm = false" class="btn-outline">Annulla</button>
          <button type="submit" class="btn-primary" :disabled="form.processing">Salva</button>
        </div>
      </form>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
  movements:  Object,
  categories: Array,
  filters:    Object,
})

const filterType  = ref(props.filters.type ?? '')
const filterYear  = ref(props.filters.year ?? new Date().getFullYear())
const filterMonth = ref(props.filters.month ?? 0)
const showForm    = ref(false)

const years  = computed(() => { const y = new Date().getFullYear(); return [y+1, y, y-1, y-2] })
const months = ['Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic']

const totalIn  = computed(() => props.movements.data.filter(m => m.type === 'in').reduce((s, m) => s + parseFloat(m.amount), 0))
const totalOut = computed(() => props.movements.data.filter(m => m.type === 'out').reduce((s, m) => s + parseFloat(m.amount), 0))

const form = useForm({
  type: 'in',
  description: '',
  expense_category_id: null,
  amount: '',
  payment_method: 'cash',
  reference: '',
  notes: '',
  movement_date: new Date().toISOString().slice(0, 10),
})

function load() {
  router.get(route('accounting.cash'), {
    type: filterType.value, year: filterYear.value, month: filterMonth.value,
  }, { preserveState: true, replace: true })
}

function submit() {
  form.post(route('accounting.cash.store'), {
    onSuccess: () => { showForm.value = false; form.reset() }
  })
}

function deleteMovement(id) {
  if (confirm('Eliminare questo movimento?')) {
    router.delete(route('accounting.cash.destroy', id))
  }
}

const fmt     = v => new Intl.NumberFormat('it-CH', { style: 'currency', currency: 'CHF' }).format(v ?? 0)
const fmtDate = d => d ? new Date(d).toLocaleDateString('it-CH') : '—'
</script>
