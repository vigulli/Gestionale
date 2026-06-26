<template>
  <AppLayout title="Spese">
    <div class="flex flex-wrap gap-3 mb-6 items-center">
      <input v-model="search" @keyup.enter="load" type="text" placeholder="Cerca spesa…" class="input-sm flex-1 min-w-48" />
      <select v-model="filterCat" @change="load" class="input-sm">
        <option value="">Tutte le categorie</option>
        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
      </select>
      <select v-model="filterYear" @change="load" class="input-sm">
        <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
      </select>
      <select v-model="filterMonth" @change="load" class="input-sm">
        <option :value="0">Tutti i mesi</option>
        <option v-for="(m, i) in months" :key="i" :value="i+1">{{ m }}</option>
      </select>
      <button @click="showForm = true" class="btn-primary ml-auto">+ Nuova spesa</button>
    </div>

    <div class="card overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="th">Data</th>
            <th class="th">Descrizione</th>
            <th class="th">Fornitore</th>
            <th class="th">Categoria</th>
            <th class="th">Metodo</th>
            <th class="th text-right">Importo</th>
            <th class="th"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="exp in expenses.data" :key="exp.id" class="tr-hover">
            <td class="td">{{ fmtDate(exp.expense_date) }}</td>
            <td class="td font-medium">
              {{ exp.description }}
              <span v-if="exp.is_recurring" class="ml-1 text-xs text-indigo-500">(ricorrente)</span>
            </td>
            <td class="td text-gray-500">{{ exp.supplier ?? '—' }}</td>
            <td class="td">
              <span v-if="exp.category" class="badge" :style="{ background: exp.category.color + '22', color: exp.category.color }">
                {{ exp.category.name }}
              </span>
              <span v-else class="text-gray-400">—</span>
            </td>
            <td class="td capitalize text-gray-500">{{ exp.payment_method }}</td>
            <td class="td text-right font-medium text-red-600">{{ fmt(exp.amount) }}</td>
            <td class="td">
              <button @click="editExpense(exp)" class="icon-btn text-gray-400 hover:text-indigo-600">✏️</button>
              <button @click="deleteExpense(exp.id)" class="icon-btn text-gray-400 hover:text-red-600">🗑</button>
            </td>
          </tr>
          <tr v-if="!expenses.data.length">
            <td colspan="7" class="td text-center text-gray-400 py-8">Nessuna spesa trovata</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Paginazione -->
    <div v-if="expenses.last_page > 1" class="flex justify-center gap-2 mt-4">
      <Link v-for="link in expenses.links" :key="link.label"
            :href="link.url ?? '#'"
            :class="['btn-outline btn-sm', link.active && 'btn-active', !link.url && 'opacity-40 cursor-default']"
            v-html="link.label" />
    </div>

    <!-- Modal form spesa -->
    <Modal :open="showForm" @close="closeForm" title="Spesa">
      <form @submit.prevent="submitForm" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div class="col-span-2">
            <label class="label">Descrizione *</label>
            <input v-model="form.description" type="text" class="input" required />
          </div>
          <div>
            <label class="label">Fornitore</label>
            <input v-model="form.supplier" type="text" class="input" />
          </div>
          <div>
            <label class="label">Categoria</label>
            <select v-model="form.expense_category_id" class="input">
              <option :value="null">—</option>
              <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>
          <div>
            <label class="label">Importo (CHF) *</label>
            <input v-model="form.amount" type="number" step="0.01" min="0" class="input" required />
          </div>
          <div>
            <label class="label">IVA %</label>
            <input v-model="form.vat_rate" type="number" step="0.01" min="0" class="input" />
          </div>
          <div>
            <label class="label">Metodo pagamento *</label>
            <select v-model="form.payment_method" class="input" required>
              <option value="cash">Contanti</option>
              <option value="card">Carta</option>
              <option value="transfer">Bonifico</option>
              <option value="check">Assegno</option>
            </select>
          </div>
          <div>
            <label class="label">Rif. fattura</label>
            <input v-model="form.reference" type="text" class="input" />
          </div>
          <div>
            <label class="label">Data *</label>
            <input v-model="form.expense_date" type="date" class="input" required />
          </div>
          <div class="col-span-2">
            <label class="label">Note</label>
            <textarea v-model="form.notes" class="input" rows="2"></textarea>
          </div>
          <div class="col-span-2 flex items-center gap-2">
            <input v-model="form.is_recurring" type="checkbox" id="recurring" class="rounded" />
            <label for="recurring" class="label mb-0">Spesa ricorrente</label>
            <select v-if="form.is_recurring" v-model="form.recurring_period" class="input-sm ml-2">
              <option value="monthly">Mensile</option>
              <option value="quarterly">Trimestrale</option>
              <option value="yearly">Annuale</option>
            </select>
          </div>
        </div>
        <div class="flex justify-end gap-3 pt-2">
          <button type="button" @click="closeForm" class="btn-outline">Annulla</button>
          <button type="submit" class="btn-primary" :disabled="form.processing">Salva</button>
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

const props = defineProps({
  expenses: Object,
  categories: Array,
  filters: Object,
})

const search      = ref(props.filters.search ?? '')
const filterCat   = ref(props.filters.category ?? '')
const filterYear  = ref(props.filters.year ?? new Date().getFullYear())
const filterMonth = ref(props.filters.month ?? 0)
const showForm    = ref(false)
const editingId   = ref(null)

const years  = computed(() => { const y = new Date().getFullYear(); return [y+1, y, y-1, y-2] })
const months = ['Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic']

const form = useForm({
  expense_category_id: null,
  description: '',
  supplier: '',
  amount: '',
  vat_rate: 0,
  vat_amount: 0,
  payment_method: 'transfer',
  reference: '',
  is_recurring: false,
  recurring_period: 'monthly',
  notes: '',
  expense_date: new Date().toISOString().slice(0, 10),
})

function load() {
  router.get(route('accounting.expenses'), {
    search: search.value, category: filterCat.value,
    year: filterYear.value, month: filterMonth.value,
  }, { preserveState: true, replace: true })
}

function editExpense(exp) {
  editingId.value = exp.id
  Object.assign(form, {
    expense_category_id: exp.expense_category_id,
    description: exp.description,
    supplier: exp.supplier ?? '',
    amount: exp.amount,
    vat_rate: exp.vat_rate,
    vat_amount: exp.vat_amount,
    payment_method: exp.payment_method,
    reference: exp.reference ?? '',
    is_recurring: exp.is_recurring,
    recurring_period: exp.recurring_period ?? 'monthly',
    notes: exp.notes ?? '',
    expense_date: exp.expense_date?.slice(0, 10) ?? '',
  })
  showForm.value = true
}

function closeForm() {
  showForm.value = false
  editingId.value = null
  form.reset()
}

function submitForm() {
  if (editingId.value) {
    form.patch(route('accounting.expenses.update', editingId.value), { onSuccess: closeForm })
  } else {
    form.post(route('accounting.expenses.store'), { onSuccess: closeForm })
  }
}

function deleteExpense(id) {
  if (confirm('Eliminare questa spesa?')) {
    router.delete(route('accounting.expenses.destroy', id))
  }
}

const fmt     = v => new Intl.NumberFormat('it-CH', { style: 'currency', currency: 'CHF' }).format(v ?? 0)
const fmtDate = d => d ? new Date(d).toLocaleDateString('it-CH') : '—'
</script>
