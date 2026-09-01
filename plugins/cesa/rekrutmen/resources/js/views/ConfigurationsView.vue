<template>
  <div class="space-y-6">
    <!-- Top Header Title & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
          Pengaturan & Master Data
        </h1>
        <p class="text-xs text-slate-500 mt-1">
          Kelola master data divisi perusahaan, tahapan pipeline seleksi pelamar, dan alur approver FPTK.
        </p>
      </div>

      <!-- Sub-tabs switcher (Pill Style) -->
      <div class="flex items-center bg-slate-100 border border-slate-200 rounded-lg p-0.5">
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
import { ref, computed, onMounted } from 'vue';
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

onMounted(() => {
  store.fetchConfigurations(false).catch(() => {});
  fetchAiSettings();
  fetchMailTemplates();
});

const divisions = computed(() => store.configurations?.divisions || []);
const stages = computed(() => store.stages || store.configurations?.stages || []);
const approvers = computed(() => store.configurations?.approvers || []);
</script>
