<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-8 text-center">
      <div v-if="action === 'accepted'">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
          </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Preventivo accettato!</h1>
        <p class="text-gray-500">Grazie per aver accettato il preventivo <strong>{{ invoice_number }}</strong>.<br/>
          Ti contatteremo a breve per procedere.</p>
      </div>
      <div v-else>
        <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Preventivo rifiutato</h1>
        <p class="text-gray-500">Il preventivo <strong>{{ invoice_number }}</strong> è stato rifiutato. Grazie per il riscontro.</p>
      </div>
      <p class="text-sm text-gray-400 mt-6">— {{ tenant_name }}</p>
    </div>
  </div>
</template>

<script setup>
defineProps({ action: String, invoice_number: String, tenant_name: String })
</script>
