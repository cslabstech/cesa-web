<template>
  <div class="space-y-6">
    <!-- Top Header Title & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
          Pengaturan & Master Data
        </h1>
        <p class="text-xs text-slate-500 mt-1">
          Kelola master data, SMTP rekrutmen, gateway WhatsApp multi-nomor, dan template notifikasi pelamar.
        </p>
      </div>

      <!-- Sub-tabs switcher (Pill Style) -->
      <div class="flex items-center flex-wrap bg-slate-100 border border-slate-200 rounded-lg p-0.5">
        <button
          @click="activeTab = 'divisions'"
          :class="[
            'px-3.5 py-1.5 rounded-md text-xs font-semibold transition-all cursor-pointer',
            activeTab === 'divisions'
              ? 'bg-white text-blue-600 shadow-xs font-bold'
              : 'text-slate-600 hover:text-slate-900'
          ]"
        >
          Divisi ({{ divisions.length }})
        </button>
        <button
          @click="activeTab = 'stages'"
          :class="[
            'px-3.5 py-1.5 rounded-md text-xs font-semibold transition-all cursor-pointer',
            activeTab === 'stages'
              ? 'bg-white text-blue-600 shadow-xs font-bold'
              : 'text-slate-600 hover:text-slate-900'
          ]"
        >
          Pipeline Stages ({{ stages.length }})
        </button>
        <button
          @click="activeTab = 'approvers'"
          :class="[
            'px-3.5 py-1.5 rounded-md text-xs font-semibold transition-all cursor-pointer',
            activeTab === 'approvers'
              ? 'bg-white text-blue-600 shadow-xs font-bold'
              : 'text-slate-600 hover:text-slate-900'
          ]"
        >
          Approvers ({{ approvers.length }})
        </button>
        <button
          @click="activeTab = 'ai'"
          :class="[
            'px-3.5 py-1.5 rounded-md text-xs font-semibold transition-all cursor-pointer',
            activeTab === 'ai'
              ? 'bg-white text-blue-600 shadow-xs font-bold'
              : 'text-slate-600 hover:text-slate-900'
          ]"
        >
          Integrasi AI
        </button>
        <button
          @click="activeTab = 'mail_gateway'"
          :class="[
            'px-3.5 py-1.5 rounded-md text-xs font-semibold transition-all cursor-pointer',
            activeTab === 'mail_gateway'
              ? 'bg-white text-blue-600 shadow-xs font-bold'
              : 'text-slate-600 hover:text-slate-900'
          ]"
        >
          Email
        </button>
        <button
          @click="activeTab = 'whatsapp_gateway'"
          :class="[
            'px-3.5 py-1.5 rounded-md text-xs font-semibold transition-all cursor-pointer',
            activeTab === 'whatsapp_gateway'
              ? 'bg-white text-blue-600 shadow-xs font-bold'
              : 'text-slate-600 hover:text-slate-900'
          ]"
        >
          WhatsApp
        </button>
        <button
          @click="activeTab = 'mail_templates'"
          :class="[
            'px-3.5 py-1.5 rounded-md text-xs font-semibold transition-all cursor-pointer',
            activeTab === 'mail_templates'
              ? 'bg-white text-blue-600 shadow-xs font-bold'
              : 'text-slate-600 hover:text-slate-900'
          ]"
        >
          Template Email
        </button>
      </div>
    </div>

    <!-- Loading State (Only if initial load has no data yet) -->
    <div v-if="store.loading.configurations && !divisions.length && !stages.length" class="bg-white rounded-xl border border-slate-200 shadow-xs">
      <LoadingState
        title="Sedang memuat data..."
        subtitle="Menyiapkan master konfigurasi rekrutmen..."
      />
    </div>

    <template v-else>
      <!-- DIVISIONS TABLE -->
      <div
        v-if="activeTab === 'divisions'"
        class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden"
      >
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
          <div class="text-xs font-bold text-slate-800 uppercase tracking-wider">
            Daftar Master Divisi Perusahaan
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead>
              <tr class="bg-slate-50/70 text-slate-500 border-b border-slate-200/80">
                <th class="py-3 px-4 font-bold uppercase tracking-wider w-20">ID</th>
                <th class="py-3 px-4 font-bold uppercase tracking-wider">Nama Divisi</th>
                <th class="py-3 px-4 font-bold uppercase tracking-wider">Status Operasional</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="div in divisions"
                :key="div.id"
                class="hover:bg-slate-50/70 transition-colors"
              >
                <td class="py-3.5 px-4 font-mono text-slate-400 font-semibold">#{{ div.id }}</td>
                <td class="py-3.5 px-4 font-bold text-slate-900">{{ div.name }}</td>
                <td class="py-3.5 px-4">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
                    {{ div.is_active ? 'Aktif' : 'Nonaktif' }}
                  </span>
                </td>
              </tr>
              <tr v-if="!divisions.length">
                <td colspan="3" class="py-12 text-center text-xs text-slate-400">Belum ada master divisi terdaftar.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- STAGES TABLE -->
      <div
        v-else-if="activeTab === 'stages'"
        class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden"
      >
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
          <div class="text-xs font-bold text-slate-800 uppercase tracking-wider">
            Daftar Tahapan Seleksi Pelamar (Pipeline Stages)
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead>
              <tr class="bg-slate-50/70 text-slate-500 border-b border-slate-200/80">
                <th class="py-3 px-4 font-bold uppercase tracking-wider w-20">Urutan</th>
                <th class="py-3 px-4 font-bold uppercase tracking-wider">Nama Tahap</th>
                <th class="py-3 px-4 font-bold uppercase tracking-wider">Warna Badge</th>
                <th class="py-3 px-4 font-bold uppercase tracking-wider">Total Kandidat Saat Ini</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="(stage, idx) in stages"
                :key="stage.id"
                class="hover:bg-slate-50/70 transition-colors"
              >
                <td class="py-3.5 px-4 font-bold text-slate-500">
                  <span class="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center text-xs font-mono">
                    {{ idx + 1 }}
                  </span>
                </td>
                <td class="py-3.5 px-4 font-bold text-slate-900">{{ stage.name }}</td>
                <td class="py-3.5 px-4">
                  <div class="flex items-center gap-2">
                    <span class="w-3.5 h-3.5 rounded-full border border-slate-200 shadow-2xs" :style="{ backgroundColor: stage.color || '#3b82f6' }"></span>
                    <span class="font-mono text-slate-500 text-[11px]">{{ stage.color || '#3b82f6' }}</span>
                  </div>
                </td>
                <td class="py-3.5 px-4">
                  <span class="font-bold text-blue-600">{{ stage.applications_count || 0 }} Orang</span>
                </td>
              </tr>
              <tr v-if="!stages.length">
                <td colspan="4" class="py-12 text-center text-xs text-slate-400">Belum ada tahapan pipeline terdaftar.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- APPROVERS TABLE -->
      <div
        v-else-if="activeTab === 'approvers'"
        class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden"
      >
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
          <div class="text-xs font-bold text-slate-800 uppercase tracking-wider">
            Daftar Konfigurasi Approver FPTK
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead>
              <tr class="bg-slate-50/70 text-slate-500 border-b border-slate-200/80">
                <th class="py-3 px-4 font-bold uppercase tracking-wider w-20">Level</th>
                <th class="py-3 px-4 font-bold uppercase tracking-wider">Nama Approver</th>
                <th class="py-3 px-4 font-bold uppercase tracking-wider">Jabatan / Role</th>
                <th class="py-3 px-4 font-bold uppercase tracking-wider">Email Notifikasi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="appr in approvers"
                :key="appr.id"
                class="hover:bg-slate-50/70 transition-colors"
              >
                <td class="py-3.5 px-4 font-bold text-slate-700">Level {{ appr.level || 1 }}</td>
                <td class="py-3.5 px-4 font-bold text-slate-900">{{ appr.name }}</td>
                <td class="py-3.5 px-4 text-slate-600 font-medium">{{ appr.role || appr.position || 'Head of Dept' }}</td>
                <td class="py-3.5 px-4 text-slate-500 font-mono text-[11px]">{{ appr.email }}</td>
              </tr>
              <tr v-if="!approvers.length">
                <td colspan="4" class="py-12 text-center text-xs text-slate-400">Belum ada approver terdaftar.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- AI SETTINGS TAB -->
      <div
        v-else-if="activeTab === 'ai'"
        class="bg-white rounded-xl border border-slate-200/90 shadow-xs p-6 space-y-6 max-w-3xl"
      >
        <div class="border-b border-slate-100 pb-4">
          <h3 class="text-sm font-bold text-slate-900">Konfigurasi Google Gemini API</h3>
          <p class="text-xs text-slate-500 mt-0.5">
            Kunci API ini digunakan untuk analisis dan evaluasi kualifikasi CV pelamar otomatis.
          </p>
        </div>

        <div class="space-y-4">
          <div>
            <label class="block font-bold text-xs text-slate-700 mb-1.5">Gemini API Key</label>
            <div class="relative">
              <input
                :type="showApiKey ? 'text' : 'password'"
                v-model="aiFormKey"
                placeholder="AQ.Ab8RN... atau AIzaSy..."
                class="w-full bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-slate-400 font-mono pr-20"
              />
              <button
                type="button"
                @click="showApiKey = !showApiKey"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-500 hover:text-slate-800 cursor-pointer"
              >
                {{ showApiKey ? 'Sembunyikan' : 'Lihat' }}
              </button>
            </div>
          </div>

          <div v-if="aiSettings.updated_at" class="text-[11px] text-slate-400">
            Terakhir diperbarui: {{ aiSettings.updated_at }}
          </div>

          <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
            <button
              type="button"
              @click="testAiConnection"
              :disabled="isTestingAi || !aiFormKey"
              class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 font-medium rounded-lg text-xs transition-colors cursor-pointer disabled:opacity-50 flex items-center gap-1.5"
            >
              <span v-if="isTestingAi" class="inline-block w-3 h-3 border-2 border-slate-600 border-t-transparent rounded-full animate-spin"></span>
              <span>{{ isTestingAi ? 'Menguji Koneksi...' : 'Uji Koneksi API' }}</span>
            </button>

            <button
              type="button"
              @click="saveAiSettings"
              :disabled="isSavingAi"
              class="px-5 py-2 bg-[#0c2340] hover:bg-[#07172b] text-white font-medium rounded-lg text-xs transition-colors cursor-pointer shadow-2xs disabled:opacity-50 flex items-center gap-1.5"
            >
              <span v-if="isSavingAi" class="inline-block w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
              <span>{{ isSavingAi ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- EMAIL GATEWAY TAB -->
      <div
        v-else-if="activeTab === 'mail_gateway'"
        class="bg-white rounded-xl border border-slate-200/90 shadow-xs p-6 space-y-6 max-w-4xl"
      >
        <div class="border-b border-slate-100 pb-4">
          <h3 class="text-sm font-bold text-slate-900">Gateway Email Rekrutmen</h3>
          <p class="text-xs text-slate-500 mt-0.5">
            SMTP ini hanya dipakai notifikasi rekrutmen. Pengaturan mail aplikasi lain tidak berubah.
          </p>
        </div>

        <label class="flex items-center gap-2.5 cursor-pointer select-none">
          <input type="checkbox" v-model="mailForm.enabled" class="rounded border-slate-300 text-blue-600 focus:ring-0 w-4 h-4 cursor-pointer" />
          <span class="text-xs font-semibold text-slate-800">Gunakan SMTP dari halaman ini</span>
        </label>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block font-bold text-xs text-slate-700 mb-1.5">Host SMTP</label>
            <input v-model="mailForm.host" type="text" placeholder="smtp.gmail.com" class="w-full bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-slate-400" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-xs text-slate-700 mb-1.5">Port</label>
              <input v-model="mailForm.port" type="number" placeholder="587" class="w-full bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-slate-400" />
            </div>
            <div>
              <label class="block font-bold text-xs text-slate-700 mb-1.5">Enkripsi</label>
              <select v-model="mailForm.encryption" class="w-full bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-slate-400 cursor-pointer">
                <option value="tls">TLS</option>
                <option value="ssl">SSL</option>
                <option value="none">Tanpa enkripsi</option>
              </select>
            </div>
          </div>
          <div>
            <label class="block font-bold text-xs text-slate-700 mb-1.5">Username</label>
            <input v-model="mailForm.username" type="text" autocomplete="off" class="w-full bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-slate-400" />
          </div>
          <div>
            <label class="block font-bold text-xs text-slate-700 mb-1.5">Password</label>
            <input v-model="mailForm.password" :placeholder="mailSettings.has_password ? 'Kosongkan jika tidak diubah' : 'Password SMTP'" type="password" autocomplete="new-password" class="w-full bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-slate-400" />
          </div>
          <div>
            <label class="block font-bold text-xs text-slate-700 mb-1.5">Email Pengirim</label>
            <input v-model="mailForm.from_address" type="email" placeholder="rekrutmen@perusahaan.com" class="w-full bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-slate-400" />
          </div>
          <div>
            <label class="block font-bold text-xs text-slate-700 mb-1.5">Nama Pengirim</label>
            <input v-model="mailForm.from_name" type="text" placeholder="Tim Rekrutmen" class="w-full bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-slate-400" />
          </div>
          <div>
            <label class="block font-bold text-xs text-slate-700 mb-1.5">Reply-To</label>
            <input v-model="mailForm.reply_to_address" type="email" class="w-full bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-slate-400" />
          </div>
          <div>
            <label class="block font-bold text-xs text-slate-700 mb-1.5">Nama Reply-To</label>
            <input v-model="mailForm.reply_to_name" type="text" class="w-full bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-slate-400" />
          </div>
        </div>

        <div class="pt-3 border-t border-slate-100 flex flex-col sm:flex-row sm:items-end gap-3">
          <div class="flex-1">
            <label class="block font-bold text-xs text-slate-700 mb-1.5">Email tujuan tes</label>
            <input v-model="mailTestRecipient" type="email" placeholder="anda@perusahaan.com" class="w-full bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-slate-400" />
          </div>
          <div class="flex items-center gap-2">
            <button type="button" @click="testMailSettings" :disabled="isTestingMail" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 font-medium rounded-lg text-xs transition-colors cursor-pointer disabled:opacity-50 flex items-center gap-1.5">
              <span v-if="isTestingMail" class="inline-block w-3 h-3 border-2 border-slate-600 border-t-transparent rounded-full animate-spin"></span>
              <span>{{ isTestingMail ? 'Menguji...' : 'Kirim Email Tes' }}</span>
            </button>
            <button type="button" @click="saveMailSettings" :disabled="isSavingMail" class="px-5 py-2 bg-[#0c2340] hover:bg-[#07172b] text-white font-medium rounded-lg text-xs transition-colors cursor-pointer shadow-2xs disabled:opacity-50 flex items-center gap-1.5">
              <span v-if="isSavingMail" class="inline-block w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
              <span>{{ isSavingMail ? 'Menyimpan...' : 'Simpan SMTP' }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- WHATSAPP GATEWAY TAB -->
      <div
        v-else-if="activeTab === 'whatsapp_gateway'"
        class="space-y-6"
      >
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs p-6 space-y-5 max-w-4xl">
          <div class="border-b border-slate-100 pb-4 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
            <div>
              <h3 class="text-sm font-bold text-slate-900">Hubungkan WhatsApp</h3>
              <p class="text-xs text-slate-500 mt-0.5">
                Scan QR dari HP, atau isi nomor untuk dapat kode pairing. Tidak perlu API key.
              </p>
            </div>
            <span
              class="inline-flex items-center px-2.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border"
              :class="whatsappSettings.engine_ready ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200'"
            >
              {{ whatsappSettings.engine_ready ? 'Engine siap' : 'Engine belum jalan' }}
            </span>
          </div>

          <label class="flex items-center gap-2.5 cursor-pointer select-none">
            <input type="checkbox" v-model="whatsappForm.enabled" class="rounded border-slate-300 text-blue-600 focus:ring-0 w-4 h-4 cursor-pointer" />
            <span class="text-xs font-semibold text-slate-800">Aktifkan pengiriman WhatsApp rekrutmen</span>
          </label>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block font-bold text-xs text-slate-700 mb-1.5">Nama (opsional)</label>
              <input v-model="whatsappConnectForm.name" type="text" placeholder="HR Recruitment" class="w-full bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-slate-400" />
            </div>
            <div>
              <label class="block font-bold text-xs text-slate-700 mb-1.5">Nomor HP WhatsApp (wajib untuk kode pairing)</label>
              <input v-model="whatsappConnectForm.phone_number" type="text" placeholder="0812xxxxxxx" class="w-full bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-slate-400" />
            </div>
          </div>

          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-3 border-t border-slate-100">
            <button type="button" @click="saveWhatsappSettings" :disabled="isSavingWhatsapp" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 font-medium rounded-lg text-xs cursor-pointer disabled:opacity-50">
              {{ isSavingWhatsapp ? 'Menyimpan...' : 'Simpan status aktif' }}
            </button>
            <div class="flex items-center gap-2">
              <button type="button" @click="startWhatsappConnect('qr')" :disabled="isConnectingWhatsapp" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-medium rounded-lg text-xs cursor-pointer disabled:opacity-50">
                {{ isConnectingWhatsapp ? 'Menyiapkan...' : 'Scan QR' }}
              </button>
              <button type="button" @click="startWhatsappConnect('pairing')" :disabled="isConnectingWhatsapp" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg text-xs cursor-pointer disabled:opacity-50">
                {{ isConnectingWhatsapp ? 'Membuat kode...' : 'Dapatkan Kode Pairing' }}
              </button>
            </div>
          </div>
        </div>

        <div v-if="whatsappConnect.open" class="bg-white rounded-xl border border-emerald-200 shadow-xs p-6 max-w-4xl">
          <div class="flex items-start justify-between gap-3 mb-4">
            <div>
              <h3 class="text-sm font-bold text-slate-900">{{ whatsappConnect.pairingCode ? 'Masukkan kode pairing' : 'Scan untuk menautkan' }}</h3>
              <p class="text-xs text-slate-500 mt-0.5">
                WhatsApp di HP → Perangkat tertaut → Tautkan perangkat{{ whatsappConnect.pairingCode ? ' → tautkan dengan nomor telepon' : '' }}.
              </p>
            </div>
            <button type="button" @click="closeWhatsappConnect" class="text-xs font-medium text-slate-500 hover:text-slate-800 cursor-pointer">Tutup</button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
            <div class="flex justify-center">
              <img
                v-if="whatsappConnect.qr"
                :src="whatsappConnect.qr"
                alt="QR WhatsApp"
                class="w-56 h-56 rounded-xl border border-slate-200 bg-white p-2"
              />
              <div v-else class="w-56 h-56 rounded-xl border border-dashed border-slate-300 flex items-center justify-center text-xs text-slate-400 text-center px-4">
                {{ whatsappConnect.status === 'connecting' ? 'Menyiapkan QR...' : 'Menunggu QR atau kode pairing' }}
              </div>
            </div>
            <div class="space-y-3">
              <div v-if="whatsappConnect.pairingCode" class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-center">
                <div class="text-[11px] uppercase tracking-wider text-emerald-700 font-bold">Kode pairing</div>
                <div class="mt-2 text-3xl font-mono font-bold tracking-[0.35em] text-slate-900">{{ whatsappConnect.pairingCode }}</div>
                <p class="text-[11px] text-slate-600 mt-2">Di HP pilih tautkan dengan nomor telepon, lalu ketik kode ini.</p>
                <button type="button" @click="copyPairingCode" class="mt-3 px-3 py-1.5 rounded-lg bg-white border border-emerald-200 text-emerald-700 text-xs font-semibold cursor-pointer">
                  Salin kode
                </button>
              </div>
              <div v-else-if="whatsappConnect.status === 'pairing'" class="rounded-xl bg-slate-50 border border-slate-200 p-4 text-center text-xs text-slate-500">
                Membuat kode pairing...
              </div>
              <div class="text-xs text-slate-600">
                Status: <strong>{{ whatsappStatusLabel(whatsappConnect) }}</strong>
              </div>
              <p v-if="whatsappConnect.engine_error" class="text-xs text-rose-600">{{ whatsappConnect.engine_error }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden max-w-4xl">
          <div class="p-5 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-900">Nomor yang sudah terhubung</h3>
            <p class="text-xs text-slate-500 mt-0.5">Saat kirim notifikasi, pilih salah satu nomor ini sebagai pengirim.</p>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead>
                <tr class="bg-slate-50/70 text-slate-500 border-b border-slate-200/80">
                  <th class="py-3 px-4 font-bold uppercase tracking-wider">Nama</th>
                  <th class="py-3 px-4 font-bold uppercase tracking-wider">Nomor</th>
                  <th class="py-3 px-4 font-bold uppercase tracking-wider">Status</th>
                  <th class="py-3 px-4 font-bold uppercase tracking-wider text-right">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="account in whatsappAccounts" :key="account.id" class="hover:bg-slate-50/70">
                  <td class="py-3.5 px-4">
                    <div class="font-bold text-slate-900">{{ account.name }}</div>
                    <div v-if="account.is_default" class="text-[10px] text-blue-600 font-semibold uppercase mt-0.5">Default</div>
                  </td>
                  <td class="py-3.5 px-4 font-mono text-slate-600">{{ account.phone_number || '-' }}</td>
                  <td class="py-3.5 px-4">
                    <span
                      class="inline-flex items-center px-2.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border"
                      :class="whatsappStatusClass(account)"
                    >
                      {{ whatsappStatusLabel(account) }}
                    </span>
                  </td>
                  <td class="py-3.5 px-4">
                    <div class="flex items-center justify-end gap-1.5">
                      <button v-if="account.status === 'connected'" type="button" @click="openWhatsappTest(account)" class="px-2 py-1 rounded-md bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-medium cursor-pointer">Tes Kirim</button>
                      <button v-if="account.status !== 'connected'" type="button" @click="startWhatsappConnect(account, 'qr')" class="px-2 py-1 rounded-md bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-medium cursor-pointer">Scan</button>
                      <button v-if="account.status !== 'connected'" type="button" @click="startWhatsappConnect(account, 'pairing')" class="px-2 py-1 rounded-md bg-amber-50 hover:bg-amber-100 text-amber-800 font-medium cursor-pointer">Kode</button>
                      <button v-else type="button" @click="disconnectWhatsappAccount(account)" class="px-2 py-1 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium cursor-pointer">Putuskan</button>
                      <button v-if="!account.is_default" type="button" @click="makeDefaultWhatsappAccount(account)" class="px-2 py-1 rounded-md bg-blue-50 hover:bg-blue-100 text-blue-700 font-medium cursor-pointer">Default</button>
                      <button type="button" @click="deleteWhatsappAccount(account)" class="px-2 py-1 rounded-md bg-rose-50 hover:bg-rose-100 text-rose-700 font-medium cursor-pointer">Hapus</button>
                    </div>
                  </td>
                </tr>
                <tr v-if="!whatsappAccounts.length">
                  <td colspan="4" class="py-12 text-center text-xs text-slate-400">Belum ada nomor. Klik Hubungkan WhatsApp lalu scan QR dari HP.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs p-6 space-y-4 max-w-4xl">
          <div class="border-b border-slate-100 pb-4">
            <h3 class="text-sm font-bold text-slate-900">Tes Kirim WhatsApp</h3>
            <p class="text-xs text-slate-500 mt-0.5">
              Kirim pesan uji dari nomor yang sudah terhubung, tanpa lewat data pelamar.
            </p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block font-bold text-xs text-slate-700 mb-1.5">Kirim dari</label>
              <select
                v-model="whatsappTest.accountId"
                class="w-full bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-slate-400 cursor-pointer"
              >
                <option :value="null" disabled>Pilih nomor terhubung</option>
                <option v-for="account in connectedWhatsappAccounts" :key="account.id" :value="account.id">
                  {{ account.name }}{{ account.phone_number ? ` • ${account.phone_number}` : '' }}
                </option>
              </select>
            </div>
            <div>
              <label class="block font-bold text-xs text-slate-700 mb-1.5">Nomor tujuan tes</label>
              <input
                v-model="whatsappTest.recipient"
                type="text"
                placeholder="0812xxxxxxx"
                class="w-full bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-slate-400"
              />
            </div>
          </div>

          <div class="flex justify-end">
            <button
              type="button"
              @click="sendWhatsappTest()"
              :disabled="isTestingWhatsapp || !whatsappTest.accountId"
              class="px-5 py-2 bg-[#0c2340] hover:bg-[#07172b] text-white font-medium rounded-lg text-xs cursor-pointer disabled:opacity-50 flex items-center gap-1.5"
            >
              <span v-if="isTestingWhatsapp" class="inline-block w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
              <span>{{ isTestingWhatsapp ? 'Mengirim tes...' : 'Kirim Pesan Tes' }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- MAIL TEMPLATES TAB -->
      <div
        v-else-if="activeTab === 'mail_templates'"
        class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden"
      >
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-slate-50/50">
          <div>
            <h3 class="text-sm font-bold text-slate-900">Template Email Notifikasi Pelamar</h3>
            <p class="text-xs text-slate-500 mt-0.5">
              Kelola template email resmi untuk undangan psikotes, jadwal wawancara, penawaran kerja (offering), dan pengumuman.
            </p>
          </div>
          <button
            type="button"
            @click="saveAllMailTemplates"
            :disabled="isSavingTemplates"
            class="px-5 py-2 bg-[#0c2340] hover:bg-[#07172b] text-white font-semibold rounded-lg text-xs transition-colors cursor-pointer shadow-2xs disabled:opacity-50 flex items-center gap-1.5 shrink-0"
          >
            <span v-if="isSavingTemplates" class="inline-block w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            <span>{{ isSavingTemplates ? 'Menyimpan...' : 'Simpan Semua Template' }}</span>
          </button>
        </div>

        <div class="p-6 grid grid-cols-1 lg:grid-cols-4 gap-6">
          <!-- Template Selector Sidebar -->
          <div class="space-y-1.5 lg:border-r lg:border-slate-100 lg:pr-6">
            <label class="block text-[11px] font-bold uppercase text-slate-400 tracking-wider mb-2">Pilih Kategori</label>
            <button
              v-for="(tpl, key) in mailTemplates"
              :key="key"
              type="button"
              @click="selectedTemplateKey = key"
              :class="[
                'w-full text-left px-3.5 py-3 rounded-xl text-xs transition-all cursor-pointer flex flex-col gap-1',
                selectedTemplateKey === key
                  ? 'bg-blue-50/80 text-blue-700 border border-blue-200 shadow-2xs font-bold'
                  : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 border border-transparent font-medium'
              ]"
            >
              <div class="flex items-center justify-between">
                <span>{{ tpl.name }}</span>
              </div>
              <span class="text-[10px] px-2 py-0.5 rounded-md bg-white border border-slate-200 text-slate-600 w-fit font-medium">
                Tahap: {{ tpl.stage }}
              </span>
            </button>
          </div>

          <!-- Template Form Editor -->
          <div v-if="currentMailTemplate" class="lg:col-span-3 space-y-4">
            <div class="bg-amber-50/60 border border-amber-200/70 rounded-xl p-3 text-[11px] text-amber-800 leading-relaxed">
              <strong>Tag Variabel Otomatis:</strong> Gunakan <code class="bg-white px-1.5 py-0.5 rounded border border-amber-200 text-amber-900 font-mono font-bold">{nama_pelamar}</code>, <code class="bg-white px-1.5 py-0.5 rounded border border-amber-200 text-amber-900 font-mono font-bold">{posisi}</code>, <code class="bg-white px-1.5 py-0.5 rounded border border-amber-200 text-amber-900 font-mono font-bold">{perusahaan}</code>, dan <code class="bg-white px-1.5 py-0.5 rounded border border-amber-200 text-amber-900 font-mono font-bold">{lokasi}</code> yang akan otomatis tergantikan dengan data asli saat email dikirim.
            </div>

            <div>
              <label class="block font-bold text-xs text-slate-700 mb-1.5">Subjek Email</label>
              <input
                type="text"
                v-model="currentMailTemplate.subject"
                class="w-full bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-slate-400 font-medium"
              />
            </div>

            <div>
              <label class="block font-bold text-xs text-slate-700 mb-1.5">Label Badge Header Email</label>
              <input
                type="text"
                v-model="currentMailTemplate.badge"
                placeholder="Misal: Tes Online / Wawancara Kerja / Job Offer"
                class="w-full bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-slate-400"
              />
            </div>

            <div>
              <label class="block font-bold text-xs text-slate-700 mb-1.5">Isi Pesan Surat (Body)</label>
              <textarea
                v-model="currentMailTemplate.body"
                rows="6"
                class="w-full bg-white border border-slate-200 rounded-lg p-3 text-xs text-slate-900 focus:outline-none focus:border-slate-400 leading-relaxed font-sans"
              ></textarea>
            </div>

            <div v-if="currentMailTemplate.has_link">
              <label class="block font-bold text-xs text-slate-700 mb-1.5">Teks Tombol Aksi (CTA Button)</label>
              <input
                type="text"
                v-model="currentMailTemplate.action_label"
                placeholder="Misal: Mulai Tes Psikotes Online"
                class="w-full bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-slate-400"
              />
            </div>

            <div v-if="currentMailTemplate.has_note">
              <label class="block font-bold text-xs text-slate-700 mb-1.5">Catatan Instruksi Default</label>
              <textarea
                v-model="currentMailTemplate.default_note"
                rows="2"
                placeholder="Petunjuk teknis pengerjaan / kehadiran..."
                class="w-full bg-white border border-slate-200 rounded-lg p-3 text-xs text-slate-900 focus:outline-none focus:border-slate-400"
              ></textarea>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRekrutmenStore } from '../stores/rekrutmen';
import LoadingState from '../components/LoadingState.vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

const store = useRekrutmenStore();
const activeTab = ref('divisions');

const aiSettings = ref({
  api_key: '',
  is_database: false,
  has_env: false,
  updated_at: null,
});
const aiFormKey = ref('');
const showApiKey = ref(false);
const isTestingAi = ref(false);
const isSavingAi = ref(false);

const mailTemplates = ref({});
const selectedTemplateKey = ref('psikotes');
const isSavingTemplates = ref(false);

const mailSettings = ref({ has_password: false });
const mailForm = ref({
  enabled: false,
  transport: 'smtp',
  host: '',
  port: 587,
  encryption: 'tls',
  username: '',
  password: '',
  timeout: 30,
  from_address: '',
  from_name: '',
  reply_to_address: '',
  reply_to_name: '',
});
const mailTestRecipient = ref('');
const isSavingMail = ref(false);
const isTestingMail = ref(false);

const whatsappSettings = ref({ enabled: true, engine_ready: false });
const whatsappForm = ref({
  enabled: true,
});
const whatsappConnectForm = ref({
  name: '',
  phone_number: '',
});
const whatsappAccounts = ref([]);
const whatsappConnect = ref({
  open: false,
  accountId: null,
  qr: null,
  pairingCode: null,
  status: null,
  engine_error: null,
});
const isSavingWhatsapp = ref(false);
const isConnectingWhatsapp = ref(false);
const isTestingWhatsapp = ref(false);
const whatsappTest = ref({
  accountId: null,
  recipient: '',
});
const connectedWhatsappAccounts = computed(() =>
  (whatsappAccounts.value || []).filter((account) => account.status === 'connected' && account.is_active !== false)
);
let whatsappPollTimer = null;

const currentMailTemplate = computed(() => {
  return mailTemplates.value[selectedTemplateKey.value] || null;
});

const fetchMailTemplates = async () => {
  try {
    const res = await axios.get('/rekrutmen/api/settings/mail-templates');
    if (res.data?.templates) {
      mailTemplates.value = res.data.templates;
    }
  } catch (err) {
    console.error('Failed fetching mail templates', err);
  }
};

const saveAllMailTemplates = async () => {
  const result = await Swal.fire({
    title: 'Simpan Template Email?',
    text: 'Perubahan template akan disimpan dan digunakan sebagai format pengiriman email ke pelamar.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Ya, Simpan',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#0c2340',
    cancelButtonColor: '#64748b',
    reverseButtons: true,
    customClass: {
      popup: 'rounded-2xl',
      confirmButton: 'rounded-lg px-4 py-2 text-xs font-semibold',
      cancelButton: 'rounded-lg px-4 py-2 text-xs font-semibold',
    }
  });

  if (!result.isConfirmed) return;

  isSavingTemplates.value = true;
  try {
    const res = await axios.post('/rekrutmen/api/settings/mail-templates', {
      templates: mailTemplates.value,
    });
    Swal.fire({
      title: 'Berhasil!',
      text: res.data.message || 'Template email berhasil disimpan.',
      icon: 'success',
      confirmButtonColor: '#0c2340',
      timer: 2000,
      customClass: {
        popup: 'rounded-2xl',
        confirmButton: 'rounded-lg px-4 py-2 text-xs font-semibold',
      }
    });
  } catch (err) {
    Swal.fire({
      title: 'Gagal Menyimpan',
      text: err.response?.data?.message || 'Terjadi kesalahan saat menyimpan template.',
      icon: 'error',
      confirmButtonColor: '#e11d48',
      customClass: {
        popup: 'rounded-2xl',
        confirmButton: 'rounded-lg px-4 py-2 text-xs font-semibold',
      }
    });
  } finally {
    isSavingTemplates.value = false;
  }
};

const fetchAiSettings = async () => {
  try {
    const res = await axios.get('/rekrutmen/api/settings/ai');
    if (res.data) {
      aiSettings.value = res.data;
      aiFormKey.value = res.data.api_key || '';
    }
  } catch (err) {
    console.error('Failed fetching AI settings', err);
  }
};

const saveAiSettings = async () => {
  const result = await Swal.fire({
    title: 'Simpan Kunci API Gemini?',
    text: 'Kunci baru akan disimpan ke database dan langsung aktif untuk proses screening CV.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Ya, Simpan',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#0c2340',
    cancelButtonColor: '#64748b',
    reverseButtons: true,
    customClass: {
      popup: 'rounded-2xl',
      confirmButton: 'rounded-lg px-4 py-2 text-xs font-semibold',
      cancelButton: 'rounded-lg px-4 py-2 text-xs font-semibold',
    }
  });

  if (!result.isConfirmed) return;

  isSavingAi.value = true;
  try {
    const res = await axios.post('/rekrutmen/api/settings/ai', {
      api_key: aiFormKey.value,
    });
    await fetchAiSettings();
    Swal.fire({
      title: 'Berhasil!',
      text: res.data.message || 'API Key berhasil diperbarui di database.',
      icon: 'success',
      confirmButtonColor: '#0c2340',
      timer: 2000,
      customClass: {
        popup: 'rounded-2xl',
        confirmButton: 'rounded-lg px-4 py-2 text-xs font-semibold',
      }
    });
  } catch (err) {
    Swal.fire({
      title: 'Gagal Menyimpan',
      text: err.response?.data?.message || 'Terjadi kesalahan saat menyimpan API Key.',
      icon: 'error',
      confirmButtonColor: '#e11d48',
      customClass: {
        popup: 'rounded-2xl',
        confirmButton: 'rounded-lg px-4 py-2 text-xs font-semibold',
      }
    });
  } finally {
    isSavingAi.value = false;
  }
};

const testAiConnection = async () => {
  isTestingAi.value = true;
  try {
    const res = await axios.post('/rekrutmen/api/settings/ai/test', {
      api_key: aiFormKey.value,
    });
    if (res.data.success) {
      Swal.fire({
        title: 'Koneksi Berhasil!',
        text: res.data.message,
        icon: 'success',
        confirmButtonColor: '#0c2340',
        customClass: {
          popup: 'rounded-2xl',
          confirmButton: 'rounded-lg px-4 py-2 text-xs font-semibold',
        }
      });
    }
  } catch (err) {
    Swal.fire({
      title: 'Koneksi Gagal',
      text: err.response?.data?.message || 'Gagal menghubungi Gemini API.',
      icon: 'error',
      confirmButtonColor: '#e11d48',
      customClass: {
        popup: 'rounded-2xl',
        confirmButton: 'rounded-lg px-4 py-2 text-xs font-semibold',
      }
    });
  } finally {
    isTestingAi.value = false;
  }
};

const applyMailSettings = (data) => {
  mailSettings.value = data || { has_password: false };
  mailForm.value = {
    enabled: !!data?.enabled,
    transport: data?.transport || 'smtp',
    host: data?.host || '',
    port: data?.port || 587,
    encryption: data?.encryption || 'tls',
    username: data?.username || '',
    password: '',
    timeout: data?.timeout || 30,
    from_address: data?.from_address || '',
    from_name: data?.from_name || '',
    reply_to_address: data?.reply_to_address || '',
    reply_to_name: data?.reply_to_name || '',
  };
};

const fetchMailSettings = async () => {
  try {
    const res = await axios.get('/rekrutmen/api/settings/mail');
    applyMailSettings(res.data);
  } catch (err) {
    console.error('Failed fetching mail settings', err);
  }
};

const saveMailSettings = async () => {
  isSavingMail.value = true;
  try {
    const payload = { ...mailForm.value };
    if (!payload.password) {
      delete payload.password;
    }
    const res = await axios.put('/rekrutmen/api/settings/mail', payload);
    applyMailSettings(res.data.data);
    Swal.fire({
      title: 'Berhasil!',
      text: res.data.message,
      icon: 'success',
      confirmButtonColor: '#0c2340',
      timer: 2200,
      customClass: { popup: 'rounded-2xl', confirmButton: 'rounded-lg px-4 py-2 text-xs font-semibold' },
    });
  } catch (err) {
    Swal.fire({
      title: 'Gagal Menyimpan',
      text: err.response?.data?.message || Object.values(err.response?.data?.errors || {})?.[0]?.[0] || 'Tidak dapat menyimpan SMTP.',
      icon: 'error',
      confirmButtonColor: '#e11d48',
      customClass: { popup: 'rounded-2xl', confirmButton: 'rounded-lg px-4 py-2 text-xs font-semibold' },
    });
  } finally {
    isSavingMail.value = false;
  }
};

const testMailSettings = async () => {
  if (!mailTestRecipient.value) {
    Swal.fire({
      title: 'Email Tes Kosong',
      text: 'Isi alamat email tujuan tes terlebih dahulu.',
      icon: 'warning',
      confirmButtonColor: '#0c2340',
    });
    return;
  }

  isTestingMail.value = true;
  try {
    const payload = { ...mailForm.value, recipient: mailTestRecipient.value };
    if (!payload.password) {
      delete payload.password;
    }
    const res = await axios.post('/rekrutmen/api/settings/mail/test', payload);
    Swal.fire({
      title: 'Email Tes Terkirim',
      text: res.data.message,
      icon: 'success',
      confirmButtonColor: '#0c2340',
      customClass: { popup: 'rounded-2xl', confirmButton: 'rounded-lg px-4 py-2 text-xs font-semibold' },
    });
  } catch (err) {
    Swal.fire({
      title: 'Tes Gagal',
      text: err.response?.data?.message || 'Gagal mengirim email tes.',
      icon: 'error',
      confirmButtonColor: '#e11d48',
      customClass: { popup: 'rounded-2xl', confirmButton: 'rounded-lg px-4 py-2 text-xs font-semibold' },
    });
  } finally {
    isTestingMail.value = false;
  }
};

const fetchWhatsappSettings = async () => {
  try {
    const res = await axios.get('/rekrutmen/api/settings/whatsapp');
    whatsappSettings.value = res.data.gateway || { enabled: true, engine_ready: false };
    whatsappForm.value.enabled = !!res.data.gateway?.enabled;
    whatsappAccounts.value = res.data.accounts || [];
    const stillValid = connectedWhatsappAccounts.value.some((account) => account.id === whatsappTest.value.accountId);
    if (!stillValid) {
      const fallback = connectedWhatsappAccounts.value.find((account) => account.is_default) || connectedWhatsappAccounts.value[0];
      whatsappTest.value.accountId = fallback ? fallback.id : null;
      if (fallback?.phone_number && !whatsappTest.value.recipient) {
        whatsappTest.value.recipient = fallback.phone_number;
      }
    }
  } catch (err) {
    console.error('Failed fetching WhatsApp settings', err);
  }
};

const saveWhatsappSettings = async () => {
  isSavingWhatsapp.value = true;
  try {
    const res = await axios.put('/rekrutmen/api/settings/whatsapp', {
      enabled: !!whatsappForm.value.enabled,
    });
    whatsappSettings.value = res.data.data;
    Swal.fire({
      title: 'Berhasil!',
      text: res.data.message,
      icon: 'success',
      confirmButtonColor: '#0c2340',
      timer: 1800,
      customClass: { popup: 'rounded-2xl', confirmButton: 'rounded-lg px-4 py-2 text-xs font-semibold' },
    });
  } catch (err) {
    Swal.fire({
      title: 'Gagal Menyimpan',
      text: err.response?.data?.message || 'Tidak dapat menyimpan pengaturan WhatsApp.',
      icon: 'error',
      confirmButtonColor: '#e11d48',
    });
  } finally {
    isSavingWhatsapp.value = false;
  }
};

const applyWhatsappSession = (data) => {
  if (!data) return;
  whatsappConnect.value = {
    open: true,
    accountId: data.id,
    qr: data.qr || null,
    pairingCode: data.pairing_code || null,
    status: data.status,
    engine_error: data.engine_error || null,
  };
};

const startWhatsappPoll = (accountId) => {
  stopWhatsappPoll();
  whatsappPollTimer = setInterval(async () => {
    try {
      const res = await axios.get(`/rekrutmen/api/settings/whatsapp/accounts/${accountId}/session`);
      applyWhatsappSession(res.data);
      if (res.data.status === 'connected') {
        stopWhatsappPoll();
        await fetchWhatsappSettings();
        Swal.fire({
          title: 'WhatsApp Terhubung',
          text: res.data.phone_number ? `Nomor ${res.data.phone_number} siap dipakai.` : 'Nomor WhatsApp berhasil ditautkan.',
          icon: 'success',
          confirmButtonColor: '#0c2340',
        });
      }
    } catch (_) {}
  }, 2000);
};

const stopWhatsappPoll = () => {
  if (whatsappPollTimer) {
    clearInterval(whatsappPollTimer);
    whatsappPollTimer = null;
  }
};

const copyPairingCode = async () => {
  const code = whatsappConnect.value.pairingCode;
  if (!code) return;
  try {
    await navigator.clipboard.writeText(code.replace(/-/g, ''));
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: 'Kode pairing disalin',
      showConfirmButton: false,
      timer: 1600,
    });
  } catch (_) {}
};

const startWhatsappConnect = async (modeOrAccount = 'qr', maybeMode = null) => {
  const account = typeof modeOrAccount === 'object' && modeOrAccount ? modeOrAccount : null;
  const mode = account ? (maybeMode || (whatsappConnectForm.value.phone_number ? 'pairing' : 'qr')) : (modeOrAccount || 'qr');

  if (mode === 'pairing' && !whatsappConnectForm.value.phone_number && !account?.phone_number) {
    Swal.fire({
      title: 'Isi Nomor HP',
      text: 'Kode pairing butuh nomor WhatsApp yang akan ditautkan, misalnya 0812xxxxxxx.',
      icon: 'warning',
      confirmButtonColor: '#0c2340',
    });
    return;
  }

  isConnectingWhatsapp.value = true;
  try {
    const payload = {
      name: whatsappConnectForm.value.name,
      phone_number: whatsappConnectForm.value.phone_number || account?.phone_number,
      mode,
    };
    const res = account
      ? await axios.post(`/rekrutmen/api/settings/whatsapp/accounts/${account.id}/connect`, payload)
      : await axios.post('/rekrutmen/api/settings/whatsapp/accounts/connect', payload);

    const data = res.data.data || res.data;
    applyWhatsappSession(data);
    startWhatsappPoll(data.id);
    await fetchWhatsappSettings();
  } catch (err) {
    Swal.fire({
      title: 'Gagal Menghubungkan',
      text: err.response?.data?.message || 'Engine WhatsApp belum siap. Jalankan php artisan rekrutmen:whatsapp-engine',
      icon: 'error',
      confirmButtonColor: '#e11d48',
    });
  } finally {
    isConnectingWhatsapp.value = false;
  }
};

const closeWhatsappConnect = () => {
  stopWhatsappPoll();
  whatsappConnect.value.open = false;
};

const makeDefaultWhatsappAccount = async (account) => {
  try {
    await axios.post(`/rekrutmen/api/settings/whatsapp/accounts/${account.id}/default`);
    await fetchWhatsappSettings();
  } catch (err) {
    Swal.fire({
      title: 'Gagal',
      text: err.response?.data?.message || 'Tidak dapat menjadikan default.',
      icon: 'error',
      confirmButtonColor: '#e11d48',
    });
  }
};

const openWhatsappTest = (account) => {
  whatsappTest.value.accountId = account.id;
  whatsappTest.value.recipient = account.phone_number || whatsappTest.value.recipient;
  sendWhatsappTest(account);
};

const sendWhatsappTest = async (account = null) => {
  const accountId = account?.id || whatsappTest.value.accountId;
  if (!accountId) {
    Swal.fire({
      title: 'Pilih Nomor Pengirim',
      text: 'Hubungkan WhatsApp dulu, lalu pilih nomor yang akan dipakai tes.',
      icon: 'warning',
      confirmButtonColor: '#0c2340',
    });
    return;
  }

  let recipient = whatsappTest.value.recipient || account?.phone_number || '';
  if (account) {
    const prompt = await Swal.fire({
      title: 'Tes Kirim WhatsApp',
      text: 'Masukkan nomor tujuan. Pesan uji akan dikirim dari nomor yang terhubung.',
      input: 'text',
      inputValue: recipient,
      inputPlaceholder: '0812xxxxxxx',
      showCancelButton: true,
      confirmButtonText: 'Kirim Tes',
      cancelButtonText: 'Batal',
      confirmButtonColor: '#0c2340',
      cancelButtonColor: '#64748b',
      reverseButtons: true,
    });
    if (!prompt.isConfirmed) return;
    recipient = prompt.value || '';
    whatsappTest.value.recipient = recipient;
  }

  if (!recipient) {
    Swal.fire({
      title: 'Nomor Tujuan Kosong',
      text: 'Isi nomor HP tujuan tes, misalnya nomor Anda sendiri.',
      icon: 'warning',
      confirmButtonColor: '#0c2340',
    });
    return;
  }

  isTestingWhatsapp.value = true;
  try {
    const res = await axios.post(`/rekrutmen/api/settings/whatsapp/accounts/${accountId}/test`, {
      recipient,
    });
    Swal.fire({
      title: 'Pesan Tes Terkirim',
      text: res.data.message || 'Cek WhatsApp tujuan tes.',
      icon: 'success',
      confirmButtonColor: '#0c2340',
    });
  } catch (err) {
    Swal.fire({
      title: 'Tes Gagal',
      text: err.response?.data?.message || 'Gagal mengirim pesan tes WhatsApp.',
      icon: 'error',
      confirmButtonColor: '#e11d48',
    });
  } finally {
    isTestingWhatsapp.value = false;
  }
};

const disconnectWhatsappAccount = async (account) => {
  try {
    await axios.post(`/rekrutmen/api/settings/whatsapp/accounts/${account.id}/disconnect`);
    await fetchWhatsappSettings();
  } catch (err) {
    Swal.fire({
      title: 'Gagal',
      text: err.response?.data?.message || 'Tidak dapat memutuskan nomor.',
      icon: 'error',
      confirmButtonColor: '#e11d48',
    });
  }
};

const deleteWhatsappAccount = async (account) => {
  const result = await Swal.fire({
    title: `Hapus ${account.name}?`,
    text: 'Nomor ini tidak bisa dipakai lagi sebagai pengirim WhatsApp rekrutmen.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, Hapus',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#e11d48',
    cancelButtonColor: '#64748b',
    reverseButtons: true,
  });
  if (!result.isConfirmed) return;

  try {
    await axios.delete(`/rekrutmen/api/settings/whatsapp/accounts/${account.id}`);
    await fetchWhatsappSettings();
  } catch (err) {
    Swal.fire({
      title: 'Gagal Menghapus',
      text: err.response?.data?.message || 'Tidak dapat menghapus nomor.',
      icon: 'error',
      confirmButtonColor: '#e11d48',
    });
  }
};

const whatsappStatusLabel = (account) => {
  if (account.is_active === false) return 'Nonaktif';
  if (account.status === 'connected') return 'Terhubung';
  if (account.status === 'qr') return 'Menunggu scan';
  if (account.status === 'pairing') return 'Menunggu kode pairing';
  if (account.status === 'connecting') return 'Menghubungkan';
  if (account.status === 'disconnected') return 'Terputus';
  return 'Belum terhubung';
};

const whatsappStatusClass = (account) => {
  if (account.is_active === false) return 'bg-slate-50 text-slate-500 border-slate-200';
  if (account.status === 'connected') return 'bg-emerald-50 text-emerald-700 border-emerald-200';
  if (account.status === 'qr' || account.status === 'pairing' || account.status === 'connecting') return 'bg-amber-50 text-amber-700 border-amber-200';
  if (account.status === 'disconnected') return 'bg-rose-50 text-rose-700 border-rose-200';
  return 'bg-slate-50 text-slate-500 border-slate-200';
};

onMounted(() => {
  store.fetchConfigurations(false).catch(() => {});
  fetchAiSettings();
  fetchMailTemplates();
  fetchMailSettings();
  fetchWhatsappSettings();
});

onUnmounted(() => {
  stopWhatsappPoll();
});

const divisions = computed(() => store.configurations?.divisions || []);
const stages = computed(() => store.stages || store.configurations?.stages || []);
const approvers = computed(() => store.configurations?.approvers || []);
</script>
