<template>
  <AppLayout :title="customer ? 'Modifica Cliente' : 'Nuovo Cliente'">
    <form @submit.prevent="submit" class="max-w-2xl space-y-6">

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="font-semibold text-gray-800 mb-5">Dati anagrafici</h2>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="label">Nome *</label>
            <input v-model="form.first_name" type="text" required class="input" />
            <p v-if="form.errors.first_name" class="err">{{ form.errors.first_name }}</p>
          </div>
          <div>
            <label class="label">Cognome *</label>
            <input v-model="form.last_name" type="text" required class="input" />
            <p v-if="form.errors.last_name" class="err">{{ form.errors.last_name }}</p>
          </div>
          <div>
            <label class="label">Telefono</label>
            <input v-model="form.phone" type="tel" class="input" placeholder="+41 79 000 00 00" />
          </div>
          <div>
            <label class="label">Email</label>
            <input v-model="form.email" type="email" class="input" />
          </div>
          <div class="col-span-2">
            <label class="label">Azienda</label>
            <input v-model="form.company" type="text" class="input" />
          </div>
          <div>
            <label class="label">UID / Partita IVA</label>
            <input v-model="form.tax_number" type="text" class="input" placeholder="CHE-123.456.789" />
          </div>
          <div>
            <label class="label">Categoria</label>
            <input v-model="form.category" type="text" class="input" list="cat-list"
              placeholder="Privato, Azienda..." />
            <datalist id="cat-list">
              <option v-for="c in categories" :key="c" :value="c" />
            </datalist>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="font-semibold text-gray-800 mb-5">Indirizzo</h2>
        <div class="grid grid-cols-2 gap-4">
          <div class="col-span-2">
            <label class="label">Via / Indirizzo</label>
            <input v-model="form.address" type="text" class="input" />
          </div>
          <div>
            <label class="label">NPA</label>
            <input v-model="form.zip" type="text" class="input" placeholder="1234" />
          </div>
          <div>
            <label class="label">Città</label>
            <input v-model="form.city" type="text" class="input" />
          </div>
          <div>
            <label class="label">Paese</label>
            <select v-model="form.country" class="input">
              <option value="CH">Svizzera</option>
              <option value="IT">Italia</option>
              <option value="FR">Francia</option>
              <option value="DE">Germania</option>
            </select>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <label class="label">Note interne</label>
        <textarea v-model="form.notes" rows="3" class="input"
          placeholder="Note visibili solo internamente..."></textarea>
      </div>

      <div class="flex gap-3">
        <Link :href="customer ? route('customers.show', customer.id) : route('customers.index')"
          class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50">
          Annulla
        </Link>
        <button type="submit" :disabled="form.processing"
          class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 disabled:opacity-50">
          {{ form.processing ? 'Salvataggio...' : (customer ? 'Salva modifiche' : 'Crea cliente') }}
        </button>
      </div>
    </form>
  </AppLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({ customer: Object, categories: Array })

const form = useForm({
  first_name:  props.customer?.first_name  ?? '',
  last_name:   props.customer?.last_name   ?? '',
  email:       props.customer?.email       ?? '',
  phone:       props.customer?.phone       ?? '',
  company:     props.customer?.company     ?? '',
  address:     props.customer?.address     ?? '',
  city:        props.customer?.city        ?? '',
  zip:         props.customer?.zip         ?? '',
  country:     props.customer?.country     ?? 'CH',
  tax_number:  props.customer?.tax_number  ?? '',
  category:    props.customer?.category    ?? '',
  notes:       props.customer?.notes       ?? '',
})

function submit() {
  if (props.customer) {
    form.put(route('customers.update', props.customer.id))
  } else {
    form.post(route('customers.store'))
  }
}
</script>

<style scoped>
.label { @apply block text-sm font-medium text-gray-700 mb-1; }
.input  { @apply w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent; }
.err    { @apply text-xs text-red-500 mt-1; }
</style>
