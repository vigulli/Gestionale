<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center">
          <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
          </svg>
        </div>
        <span class="font-semibold text-gray-900">Gestionale</span>
      </div>
      <div class="flex items-center gap-4">
        <span class="text-sm text-gray-500">{{ $page.props.auth.user?.name }}</span>
        <form @submit.prevent="logout">
          <button type="submit" class="text-sm text-gray-500 hover:text-gray-700 transition">Esci</button>
        </form>
      </div>
    </header>

    <!-- Content -->
    <main class="flex-1 flex flex-col items-center justify-center px-4 py-12">
      <div class="text-center mb-10">
        <h1 class="text-3xl font-bold text-gray-900">Seleziona attività</h1>
        <p class="text-gray-500 mt-2">Scegli con quale attività vuoi lavorare</p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 w-full max-w-4xl">
        <button
          v-for="tenant in tenants"
          :key="tenant.id"
          @click="select(tenant.id)"
          :class="[
            'relative text-left rounded-2xl border-2 p-6 transition-all hover:shadow-md focus:outline-none',
            current === tenant.id
              ? 'border-indigo-500 shadow-md'
              : 'border-gray-200 bg-white hover:border-gray-300',
          ]"
        >
          <!-- Active badge -->
          <div v-if="current === tenant.id"
            class="absolute top-4 right-4 w-2.5 h-2.5 rounded-full bg-green-400">
          </div>

          <!-- Logo / icon -->
          <div class="mb-4">
            <img
              v-if="tenant.logo_path"
              :src="`/storage/${tenant.logo_path}`"
              class="h-10 object-contain"
            />
            <div
              v-else
              class="w-12 h-12 rounded-xl flex items-center justify-center text-white text-xl font-bold"
              :style="{ backgroundColor: tenant.primary_color ?? '#6366f1' }"
            >
              {{ tenant.name.charAt(0).toUpperCase() }}
            </div>
          </div>

          <h2 class="text-lg font-semibold text-gray-900">{{ tenant.name }}</h2>
          <p v-if="tenant.company_name" class="text-sm text-gray-500 mt-0.5">{{ tenant.company_name }}</p>

          <!-- Modules -->
          <div class="mt-4 flex flex-wrap gap-1.5">
            <span v-if="tenant.module_repairs"
              class="px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
              Riparazioni
            </span>
            <span v-if="tenant.module_print_orders"
              class="px-2 py-0.5 rounded-full text-xs font-medium bg-purple-50 text-purple-700">
              Stampa DTF
            </span>
            <span v-if="tenant.module_pos"
              class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700">
              POS
            </span>
          </div>
        </button>
      </div>
    </main>
  </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3'

const props = defineProps({
  tenants: Array,
  current: String,
})

function select(tenantId) {
  router.post(route('tenant.switch'), { tenant_id: tenantId })
}

function logout() {
  router.post(route('logout'))
}
</script>
