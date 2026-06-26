<template>
  <AppLayout title="Impostazioni">
    <div class="max-w-4xl space-y-4">

      <!-- Flash messages -->
      <div v-if="$page.props.flash?.success"
        class="flex items-center gap-3 bg-green-50 border border-green-200 rounded-xl px-4 py-3 text-sm text-green-800">
        <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
        </svg>
        {{ $page.props.flash.success }}
      </div>
      <div v-if="$page.props.flash?.error"
        class="flex items-center gap-3 bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-800">
        <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
        {{ $page.props.flash.error }}
      </div>

      <!-- Tabs -->
      <div class="flex gap-1 bg-gray-100 p-1 rounded-xl w-fit">
        <button v-for="tab in tabs" :key="tab.key"
          @click="activeTab = tab.key"
          :class="['px-4 py-2 rounded-lg text-sm font-medium transition',
            activeTab === tab.key ? 'bg-white shadow text-gray-900' : 'text-gray-500 hover:text-gray-700']">
          {{ tab.label }}
        </button>
      </div>

      <!-- ═══ TAB: Branding ═══ -->
      <div v-if="activeTab === 'branding'">
        <form @submit.prevent="submitBranding" class="space-y-4">
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="font-semibold text-gray-800 mb-5">Identità visiva</h2>
            <div class="grid grid-cols-2 gap-5">

              <!-- Logo -->
              <div class="col-span-2">
                <label class="label">Logo attività</label>
                <div class="flex items-center gap-5">
                  <div class="w-20 h-20 rounded-xl border-2 border-dashed border-gray-300 flex items-center justify-center overflow-hidden bg-gray-50">
                    <img v-if="logoPreview || tenant.logo_url" :src="logoPreview || tenant.logo_url"
                      class="w-full h-full object-contain p-1" />
                    <svg v-else class="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                  </div>
                  <div>
                    <label class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                      </svg>
                      Carica logo
                      <input type="file" class="sr-only" accept=".png,.jpg,.jpeg,.svg,.webp"
                        @change="handleLogo" />
                    </label>
                    <p class="text-xs text-gray-400 mt-1">PNG, SVG, JPG — max 2MB<br/>Consigliato: sfondo trasparente</p>
                  </div>
                </div>
              </div>

              <div>
                <label class="label">Nome attività *</label>
                <input v-model="brandingForm.name" type="text" required class="input" />
              </div>

              <div>
                <label class="label">Favicon</label>
                <div class="flex items-center gap-3">
                  <img v-if="faviconPreview || tenant.favicon_url" :src="faviconPreview || tenant.favicon_url"
                    class="w-8 h-8 rounded object-contain border border-gray-200" />
                  <label class="cursor-pointer text-sm text-indigo-600 hover:underline">
                    Carica favicon
                    <input type="file" class="sr-only" accept=".png,.ico" @change="handleFavicon" />
                  </label>
                </div>
              </div>

              <!-- Colori -->
              <div>
                <label class="label">Colore primario</label>
                <div class="flex items-center gap-3">
                  <input v-model="brandingForm.primary_color" type="color"
                    class="w-10 h-10 rounded-lg border border-gray-300 cursor-pointer p-0.5" />
                  <input v-model="brandingForm.primary_color" type="text"
                    class="input flex-1 font-mono uppercase" maxlength="7" placeholder="#6366f1" />
                </div>
              </div>
              <div>
                <label class="label">Colore secondario</label>
                <div class="flex items-center gap-3">
                  <input v-model="brandingForm.secondary_color" type="color"
                    class="w-10 h-10 rounded-lg border border-gray-300 cursor-pointer p-0.5" />
                  <input v-model="brandingForm.secondary_color" type="text"
                    class="input flex-1 font-mono uppercase" maxlength="7" placeholder="#818cf8" />
                </div>
              </div>

              <!-- Preview sidebar -->
              <div class="col-span-2">
                <label class="label">Anteprima sidebar</label>
                <div class="w-48 rounded-xl overflow-hidden shadow-sm border border-gray-200">
                  <div class="p-3 flex items-center gap-2" :style="{ backgroundColor: brandingForm.primary_color }">
                    <img v-if="logoPreview || tenant.logo_url" :src="logoPreview || tenant.logo_url"
                      class="h-6 w-auto object-contain" />
                    <span class="text-white text-sm font-bold truncate">{{ brandingForm.name }}</span>
                  </div>
                  <div class="p-2 space-y-1" :style="{ backgroundColor: brandingForm.primary_color + 'dd' }">
                    <div class="px-2 py-1 rounded text-white/80 text-xs">Riparazioni</div>
                    <div class="px-2 py-1 rounded bg-white/20 text-white text-xs font-medium">Clienti</div>
                    <div class="px-2 py-1 rounded text-white/80 text-xs">Fatture</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Moduli abilitati -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="font-semibold text-gray-800 mb-4">Moduli abilitati</h2>
            <p class="text-sm text-gray-500 mb-4">I moduli disabilitati non appaiono nel menu.</p>
            <div class="space-y-3">
              <label v-for="mod in modules" :key="mod.key"
                class="flex items-center justify-between p-3 rounded-xl border border-gray-200 cursor-pointer hover:border-indigo-300 transition">
                <div>
                  <div class="text-sm font-medium text-gray-800">{{ mod.label }}</div>
                  <div class="text-xs text-gray-500">{{ mod.desc }}</div>
                </div>
                <div class="relative">
                  <input type="checkbox" v-model="modulesForm[mod.key]" class="sr-only peer" />
                  <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-indigo-600 transition-colors" />
                  <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5" />
                </div>
              </label>
            </div>
            <button type="button" @click="submitModules" :disabled="modulesForm.processing"
              class="mt-4 px-5 py-2 bg-gray-800 text-white rounded-lg text-sm font-semibold hover:bg-gray-900 disabled:opacity-50">
              Salva moduli
            </button>
          </div>

          <button type="submit" :disabled="brandingForm.processing"
            class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 disabled:opacity-50">
            {{ brandingForm.processing ? 'Salvataggio...' : 'Salva branding' }}
          </button>
        </form>
      </div>

      <!-- ═══ TAB: Azienda + Fattura ═══ -->
      <div v-if="activeTab === 'business'">
        <form @submit.prevent="submitBusiness" class="space-y-4">
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="font-semibold text-gray-800 mb-5">Dati aziendali</h2>
            <div class="grid grid-cols-2 gap-4">
              <div class="col-span-2">
                <label class="label">Ragione sociale</label>
                <input v-model="businessForm.company_name" type="text" class="input" />
              </div>
              <div class="col-span-2">
                <label class="label">Indirizzo</label>
                <input v-model="businessForm.address" type="text" class="input" />
              </div>
              <div>
                <label class="label">NPA</label>
                <input v-model="businessForm.zip" type="text" class="input" />
              </div>
              <div>
                <label class="label">Città</label>
                <input v-model="businessForm.city" type="text" class="input" />
              </div>
              <div>
                <label class="label">Paese</label>
                <select v-model="businessForm.country" class="input">
                  <option value="CH">Svizzera</option>
                  <option value="IT">Italia</option>
                  <option value="FR">Francia</option>
                </select>
              </div>
              <div>
                <label class="label">Telefono</label>
                <input v-model="businessForm.phone" type="tel" class="input" />
              </div>
              <div>
                <label class="label">Email pubblica</label>
                <input v-model="businessForm.email" type="email" class="input" />
              </div>
              <div>
                <label class="label">Sito web</label>
                <input v-model="businessForm.website" type="url" class="input" placeholder="https://" />
              </div>
              <div>
                <label class="label">N° UID (Svizzera)</label>
                <input v-model="businessForm.uid_number" type="text" class="input" placeholder="CHE-123.456.789" />
              </div>
              <div>
                <label class="label">IVA standard %</label>
                <input v-model.number="businessForm.vat_rate_standard" type="number" step="0.1" class="input" />
              </div>
              <div>
                <label class="label">IVA ridotta %</label>
                <input v-model.number="businessForm.vat_rate_reduced" type="number" step="0.1" class="input" />
              </div>
            </div>
          </div>

          <!-- Dati bancari per QR-bill -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="font-semibold text-gray-800 mb-2">QR-Bill svizzero</h2>
            <p class="text-sm text-gray-500 mb-4">
              Questi dati vengono usati per generare il QR di pagamento sulle fatture secondo lo standard Swiss QR Bill.
            </p>
            <div class="grid grid-cols-2 gap-4">
              <div class="col-span-2">
                <label class="label">IBAN</label>
                <input v-model="businessForm.iban" type="text" class="input font-mono"
                  placeholder="CH56 0483 5012 3456 7800 9" />
              </div>
              <div class="col-span-2">
                <label class="label">Nome banca</label>
                <input v-model="businessForm.bank_name" type="text" class="input" />
              </div>
            </div>
          </div>

          <!-- Numerazione fatture -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="font-semibold text-gray-800 mb-4">Numerazione fatture</h2>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="label">Prefisso</label>
                <input v-model="businessForm.invoice_prefix" type="text" class="input font-mono"
                  placeholder="IL-" maxlength="10" />
                <p class="text-xs text-gray-400 mt-1">Esempio: IL-0042</p>
              </div>
              <div>
                <label class="label">Prossimo numero</label>
                <input v-model.number="businessForm.invoice_next_number" type="number" min="1" class="input" />
              </div>
            </div>
          </div>

          <!-- Layout fattura -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="font-semibold text-gray-800 mb-2">Layout fattura personalizzato</h2>
            <p class="text-sm text-gray-500 mb-4">
              Carica un'immagine PNG come sfondo della fattura (es. carta intestata con logo e decorazioni).
              Il gestionale ci sovrapporrà automaticamente i dati del documento.
            </p>

            <div class="grid grid-cols-2 gap-5 items-start">
              <div>
                <label class="label">Sfondo fattura (PNG)</label>
                <label class="flex flex-col items-center justify-center border-2 border-dashed border-gray-300
                  rounded-xl p-6 cursor-pointer hover:border-indigo-400 transition">
                  <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                  </svg>
                  <span class="text-sm text-gray-500">PNG A4 — max 5MB</span>
                  <input type="file" class="sr-only" accept=".png,.jpg,.jpeg"
                    @change="handleInvoiceBg" />
                </label>
              </div>

              <!-- Preview -->
              <div>
                <label class="label">Anteprima</label>
                <div class="border border-gray-200 rounded-xl overflow-hidden shadow-sm aspect-[210/297] relative bg-white">
                  <img v-if="invoiceBgPreview || tenant.invoice_background_url"
                    :src="invoiceBgPreview || tenant.invoice_background_url"
                    class="absolute inset-0 w-full h-full object-cover" />
                  <!-- Elementi fattura sovrapposti (mock) -->
                  <div class="absolute inset-0 p-4 text-xs text-gray-600">
                    <div class="flex justify-between mb-6">
                      <div class="opacity-40">
                        <div class="font-bold text-sm">{{ businessForm.company_name || 'Azienda' }}</div>
                        <div>{{ businessForm.address }}</div>
                        <div>{{ businessForm.zip }} {{ businessForm.city }}</div>
                      </div>
                      <div class="text-right opacity-40">
                        <div class="font-bold text-lg">FATTURA</div>
                        <div>{{ businessForm.invoice_prefix }}0042</div>
                      </div>
                    </div>
                    <div class="mt-4 opacity-30 border-t border-gray-400 pt-2">
                      <div class="flex justify-between"><span>Servizio</span><span>CHF 100.00</span></div>
                      <div class="flex justify-between font-bold mt-2"><span>Totale</span><span>CHF 108.10</span></div>
                    </div>
                    <!-- Mock QR-bill in fondo -->
                    <div class="absolute bottom-4 left-4 right-4 border-t border-gray-300 pt-2 opacity-30">
                      <div class="flex items-center gap-2">
                        <div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center text-xs">QR</div>
                        <div class="text-xs">
                          <div>{{ businessForm.iban || 'CH56 0483...' }}</div>
                          <div>{{ businessForm.company_name }}</div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <button type="submit" :disabled="businessForm.processing"
            class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 disabled:opacity-50">
            {{ businessForm.processing ? 'Salvataggio...' : 'Salva dati aziendali' }}
          </button>
        </form>
      </div>

      <!-- ═══ TAB: Email SMTP ═══ -->
      <div v-if="activeTab === 'email'">
        <form @submit.prevent="submitSmtp" class="space-y-4">
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="font-semibold text-gray-800 mb-2">Configurazione email (SMTP)</h2>
            <p class="text-sm text-gray-500 mb-5">
              Ogni attività usa il proprio server SMTP. Le email di preventivi, notifiche e fatture
              vengono inviate da questo indirizzo.
            </p>
            <div class="grid grid-cols-2 gap-4">
              <div class="col-span-2">
                <label class="label">Host SMTP</label>
                <input v-model="smtpForm.smtp_host" type="text" class="input font-mono"
                  placeholder="mail.tuodominio.ch" />
              </div>
              <div>
                <label class="label">Porta</label>
                <select v-model.number="smtpForm.smtp_port" class="input">
                  <option :value="587">587 (STARTTLS — consigliato)</option>
                  <option :value="465">465 (SSL)</option>
                  <option :value="25">25</option>
                  <option :value="2525">2525</option>
                </select>
              </div>
              <div>
                <label class="label">Crittografia</label>
                <select v-model="smtpForm.smtp_encryption" class="input">
                  <option value="tls">TLS / STARTTLS</option>
                  <option value="ssl">SSL</option>
                  <option value="none">Nessuna</option>
                </select>
              </div>
              <div>
                <label class="label">Utente / Email</label>
                <input v-model="smtpForm.smtp_user" type="email" class="input font-mono"
                  placeholder="noreply@tuodominio.ch" />
              </div>
              <div>
                <label class="label">Password</label>
                <input v-model="smtpForm.smtp_password" type="password" class="input"
                  placeholder="Lascia vuoto per non modificare" autocomplete="new-password" />
              </div>
              <div>
                <label class="label">Nome mittente</label>
                <input v-model="smtpForm.smtp_from_name" type="text" class="input"
                  placeholder="i-Lab Assistenza" />
              </div>
              <div>
                <label class="label">Email mittente</label>
                <input v-model="smtpForm.smtp_from_email" type="email" class="input font-mono"
                  placeholder="info@i-lab.ch" />
              </div>
            </div>
          </div>

          <div class="flex gap-3">
            <button type="submit" :disabled="smtpForm.processing"
              class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 disabled:opacity-50">
              {{ smtpForm.processing ? 'Salvataggio...' : 'Salva configurazione email' }}
            </button>
            <button type="button" @click="testSmtp" :disabled="testingSmtp"
              class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-50 disabled:opacity-50">
              {{ testingSmtp ? 'Invio test...' : '✉ Invia email di test' }}
            </button>
          </div>
        </form>
      </div>

      <!-- ═══ TAB: BulkGate ═══ -->
      <div v-if="activeTab === 'bulkgate'">
        <form @submit.prevent="submitBulkgate" class="space-y-4">
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-start gap-4 mb-5">
              <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                </svg>
              </div>
              <div>
                <h2 class="font-semibold text-gray-800">BulkGate — SMS e WhatsApp</h2>
                <p class="text-sm text-gray-500 mt-1">
                  Configura le credenziali BulkGate per inviare SMS e messaggi WhatsApp ai tuoi clienti.
                  Ottieni le credenziali dal portale BulkGate.com.
                </p>
              </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="label">Application ID</label>
                <input v-model="bulkgateForm.bulkgate_app_id" type="text" class="input font-mono"
                  placeholder="1234" />
              </div>
              <div>
                <label class="label">Application Token</label>
                <input v-model="bulkgateForm.bulkgate_app_token" type="password" class="input font-mono"
                  placeholder="Lascia vuoto per non modificare" autocomplete="new-password" />
              </div>
              <div>
                <label class="label">Sender ID (SMS)</label>
                <input v-model="bulkgateForm.bulkgate_sender_id" type="text" class="input"
                  placeholder="gSYS o nome alfanumerico" maxlength="11" />
                <p class="text-xs text-gray-400 mt-1">Max 11 caratteri, solo lettere/numeri</p>
              </div>
              <div class="flex items-center gap-3 pt-6">
                <div class="relative">
                  <input type="checkbox" v-model="bulkgateForm.bulkgate_whatsapp_enabled" class="sr-only peer" id="wa-toggle"/>
                  <label for="wa-toggle" class="cursor-pointer">
                    <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-green-500 transition-colors" />
                    <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5" />
                  </label>
                </div>
                <label for="wa-toggle" class="text-sm font-medium text-gray-700 cursor-pointer">
                  Abilita WhatsApp
                </label>
              </div>
            </div>
          </div>

          <!-- Test -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-700 mb-4">Test invio</h3>
            <div class="flex gap-3 items-end">
              <div class="flex-1">
                <label class="label">Numero di test</label>
                <input v-model="testPhone" type="tel" class="input" placeholder="+41 79 000 00 00" />
              </div>
              <div>
                <label class="label">Canale</label>
                <select v-model="testChannel" class="input">
                  <option value="sms">SMS</option>
                  <option value="whatsapp">WhatsApp</option>
                </select>
              </div>
              <button type="button" @click="testBulkgate"
                :disabled="testingBulkgate || !testPhone"
                class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-semibold hover:bg-green-700 disabled:opacity-50">
                {{ testingBulkgate ? 'Invio...' : 'Invia test' }}
              </button>
            </div>
          </div>

          <div class="flex gap-3">
            <button type="submit" :disabled="bulkgateForm.processing"
              class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 disabled:opacity-50">
              {{ bulkgateForm.processing ? 'Salvataggio...' : 'Salva configurazione BulkGate' }}
            </button>
          </div>
        </form>
      </div>

      <!-- ═══ TAB: SumUp ═══ -->
      <div v-if="activeTab === 'sumup'">
        <form @submit.prevent="submitSumup" class="space-y-4">
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-start gap-4 mb-5">
              <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center text-blue-700 font-bold text-lg flex-shrink-0">S</div>
              <div>
                <h2 class="font-semibold text-gray-800">SumUp — Pagamenti POS</h2>
                <p class="text-sm text-gray-500 mt-1">
                  Collega il lettore SumUp per accettare pagamenti con carta direttamente dal gestionale.
                  Ottieni le credenziali dal portale SumUp Developer.
                </p>
              </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="label">API Key SumUp</label>
                <input v-model="sumupForm.sumup_api_key" type="password" class="input font-mono"
                  placeholder="sup_sk_..." autocomplete="new-password" />
              </div>
              <div>
                <label class="label">Merchant Code</label>
                <input v-model="sumupForm.sumup_merchant_code" type="text" class="input font-mono"
                  placeholder="M12345" />
              </div>
            </div>
            <div class="mt-4 p-4 bg-blue-50 rounded-xl text-sm text-blue-700">
              <p class="font-medium mb-1">Come ottenere le credenziali SumUp:</p>
              <ol class="list-decimal list-inside space-y-1 text-blue-600">
                <li>Accedi al portale sviluppatori SumUp</li>
                <li>Crea una nuova applicazione OAuth</li>
                <li>Copia l'API Key e il Merchant Code del tuo account</li>
              </ol>
            </div>
          </div>

          <button type="submit" :disabled="sumupForm.processing"
            class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 disabled:opacity-50">
            {{ sumupForm.processing ? 'Salvataggio...' : 'Salva configurazione SumUp' }}
          </button>
        </form>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({ tenant: Object })

const activeTab = ref('branding')

const tabs = [
  { key: 'branding',  label: 'Branding' },
  { key: 'business',  label: 'Azienda & Fattura' },
  { key: 'email',     label: 'Email SMTP' },
  { key: 'bulkgate',  label: 'SMS & WhatsApp' },
  { key: 'sumup',     label: 'SumUp POS' },
]

const modules = [
  { key: 'module_repairs',      label: 'Riparazioni',      desc: 'Gestione lavori, ticket, stati' },
  { key: 'module_print_orders', label: 'Ordini Stampa DTF',desc: 'Stampa personalizzata abbigliamento' },
  { key: 'module_pos',          label: 'POS & Vendite',    desc: 'Cassa, fatture, preventivi' },
  { key: 'module_inventory',    label: 'Inventario',       desc: 'Prodotti, ricambi, movimenti' },
  { key: 'module_loyalty',      label: 'Fidelizzazione',   desc: 'Punti fedeltà e premi' },
]

// Forms
const brandingForm = useForm({
  name:            props.tenant.name ?? '',
  primary_color:   props.tenant.primary_color ?? '#6366f1',
  secondary_color: props.tenant.secondary_color ?? '#818cf8',
  logo:            null,
  favicon:         null,
})

const modulesForm = useForm(
  Object.fromEntries(modules.map(m => [m.key, props.tenant[m.key] ?? false]))
)

const businessForm = useForm({
  company_name:         props.tenant.company_name ?? '',
  address:              props.tenant.address ?? '',
  city:                 props.tenant.city ?? '',
  zip:                  props.tenant.zip ?? '',
  country:              props.tenant.country ?? 'CH',
  phone:                props.tenant.phone ?? '',
  email:                props.tenant.email ?? '',
  website:              props.tenant.website ?? '',
  uid_number:           props.tenant.uid_number ?? '',
  iban:                 props.tenant.iban ?? '',
  bank_name:            props.tenant.bank_name ?? '',
  invoice_prefix:       props.tenant.invoice_prefix ?? '',
  invoice_next_number:  props.tenant.invoice_next_number ?? 1,
  vat_rate_standard:    props.tenant.vat_rate_standard ?? 8.1,
  vat_rate_reduced:     props.tenant.vat_rate_reduced ?? 2.6,
  invoice_background:   null,
})

const smtpForm = useForm({
  smtp_host:       props.tenant.smtp_host ?? '',
  smtp_port:       props.tenant.smtp_port ?? 587,
  smtp_user:       props.tenant.smtp_user ?? '',
  smtp_password:   '',
  smtp_encryption: props.tenant.smtp_encryption ?? 'tls',
  smtp_from_name:  props.tenant.smtp_from_name ?? '',
  smtp_from_email: props.tenant.smtp_from_email ?? '',
})

const bulkgateForm = useForm({
  bulkgate_app_id:           props.tenant.bulkgate_app_id ?? '',
  bulkgate_app_token:        '',
  bulkgate_sender_id:        props.tenant.bulkgate_sender_id ?? '',
  bulkgate_whatsapp_enabled: props.tenant.bulkgate_whatsapp_enabled ?? false,
})

const sumupForm = useForm({
  sumup_api_key:       '',
  sumup_merchant_code: props.tenant.sumup_merchant_code ?? '',
})

// File previews
const logoPreview      = ref(null)
const faviconPreview   = ref(null)
const invoiceBgPreview = ref(null)

function handleLogo(e) {
  brandingForm.logo = e.target.files[0]
  logoPreview.value = URL.createObjectURL(e.target.files[0])
}
function handleFavicon(e) {
  brandingForm.favicon = e.target.files[0]
  faviconPreview.value = URL.createObjectURL(e.target.files[0])
}
function handleInvoiceBg(e) {
  businessForm.invoice_background = e.target.files[0]
  invoiceBgPreview.value = URL.createObjectURL(e.target.files[0])
}

// Submit handlers
function submitBranding() {
  brandingForm.post(route('settings.branding'))
}
function submitModules() {
  modulesForm.post(route('settings.modules'))
}
function submitBusiness() {
  businessForm.post(route('settings.business'))
}
function submitSmtp() {
  smtpForm.post(route('settings.smtp'))
}
function submitBulkgate() {
  bulkgateForm.post(route('settings.bulkgate'))
}
function submitSumup() {
  sumupForm.post(route('settings.sumup'))
}

// Test handlers
const testingSmtp    = ref(false)
const testingBulkgate = ref(false)
const testPhone      = ref('')
const testChannel    = ref('sms')

function testSmtp() {
  testingSmtp.value = true
  router.post(route('settings.smtp.test'), {}, {
    onFinish: () => { testingSmtp.value = false },
  })
}
function testBulkgate() {
  testingBulkgate.value = true
  router.post(route('settings.bulkgate.test'), {
    test_phone: testPhone.value,
    test_channel: testChannel.value,
  }, {
    onFinish: () => { testingBulkgate.value = false },
  })
}
</script>

<style scoped>
.label { @apply block text-sm font-medium text-gray-700 mb-1; }
.input  { @apply w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent; }
</style>
