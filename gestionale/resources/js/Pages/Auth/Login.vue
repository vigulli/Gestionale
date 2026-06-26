<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-8">
      <!-- Logo -->
      <div class="text-center mb-8">
        <img v-if="$page.props.tenant?.logo_url" :src="$page.props.tenant.logo_url" class="h-12 mx-auto mb-4" />
        <h1 class="text-2xl font-bold text-gray-900" :style="{ color: $page.props.tenant?.primary_color }">
          {{ $page.props.tenant?.name ?? 'Gestionale' }}
        </h1>
        <p class="text-gray-400 text-sm mt-1">Accedi al tuo account</p>
      </div>

      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <input
            v-model="form.email"
            type="email"
            autocomplete="email"
            required
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
          />
          <p v-if="form.errors.email" class="text-xs text-red-500 mt-1">{{ form.errors.email }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
          <input
            v-model="form.password"
            type="password"
            autocomplete="current-password"
            required
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
          />
          <p v-if="form.errors.password" class="text-xs text-red-500 mt-1">{{ form.errors.password }}</p>
        </div>

        <label class="flex items-center gap-2 text-sm text-gray-600">
          <input v-model="form.remember" type="checkbox" class="rounded" />
          Ricordami
        </label>

        <button
          type="submit"
          :disabled="form.processing"
          class="w-full py-2.5 rounded-lg text-white text-sm font-semibold transition"
          :style="{ backgroundColor: $page.props.tenant?.primary_color ?? '#6366f1' }"
        >
          {{ form.processing ? 'Accesso...' : 'Accedi' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

function submit() {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  })
}
</script>
