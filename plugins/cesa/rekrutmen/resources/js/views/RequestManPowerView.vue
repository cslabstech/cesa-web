<template>
  <div class="space-y-6">
    <!-- Top Header Title & Action Button -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-xl font-bold text-slate-900 tracking-tight">
          Permintaan Tenaga Kerja (FPTK)
        </h1>
        <p class="text-xs text-slate-500 mt-0.5">
          Kelola dan pantau permohonan penambahan personil dari seluruh cabang dan divisi
        </p>
      </div>

      <div class="flex items-center gap-2.5">
        <a
          href="/man-power"
          target="_blank"
          class="inline-flex items-center gap-1.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold px-3.5 py-2 rounded-lg shadow-2xs transition-colors cursor-pointer"
        >
          <Plus class="w-3.5 h-3.5" />
          <span>Buat FPTK Baru</span>
        </a>
      </div>
    </div>

    <!-- Alert / Toast Message -->
    <div
      v-if="toastMessage"
      :class="[
        'p-3 rounded-lg border flex items-center justify-between text-xs font-medium transition-all shadow-2xs',
        toastType === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800'
      ]"
    >
      <div class="flex items-center gap-2">
        <CheckCircle2 v-if="toastType === 'success'" class="w-4 h-4 text-emerald-600" />
        <AlertCircle v-else class="w-4 h-4 text-rose-600" />
        <span>{{ toastMessage }}</span>
      </div>
      <button @click="toastMessage = null" class="text-slate-400 hover:text-slate-600 font-bold">&times;</button>
    </div>

    <!-- Filter Tabs & Search Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-200 pb-4">
      <!-- Status Filter Tabs -->
      <div class="flex items-center gap-2 overflow-x-auto custom-scrollbar pb-1 sm:pb-0">
        <button
          @click="statusFilter = 'all'"
          :class="[
            'px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer shrink-0',
            statusFilter === 'all'
              ? 'bg-slate-900 text-white'
              : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
          ]"
        >
          Semua FPTK <span class="ml-1 opacity-70">({{ requests.length }})</span>
        </button>

        <button
          @click="statusFilter = 'approved'"
          :class="[
            'px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer shrink-0',
            statusFilter === 'approved'
              ? 'bg-emerald-600 text-white'
              : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
          ]"
        >
          Disetujui <span class="ml-1 opacity-70">({{ approvedCount }})</span>
        </button>

        <button
          @click="statusFilter = 'pending'"
          :class="[
            'px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer shrink-0',
            statusFilter === 'pending'
              ? 'bg-amber-600 text-white'
              : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
          ]"
        >
          Menunggu <span class="ml-1 opacity-70">({{ pendingCount }})</span>
        </button>
      </div>

      <!-- Search Input -->
      <div class="relative w-full sm:w-80">
        <Search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Cari nomor FPTK, posisi, divisi..."
          class="w-full bg-white border border-slate-200 rounded-lg pl-9 pr-3 py-1.5 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
        />
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="store.loading.requests && !requests.length" class="bg-white rounded-xl border border-slate-200 p-8 text-center text-xs text-slate-500 shadow-2xs">
      Sedang memuat data permohonan tenaga kerja...
    </div>

    <!-- Data Table Card -->
    <div v-else class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead>
            <tr class="bg-slate-50 text-slate-500 border-b border-slate-200">
              <th class="py-3 px-4 font-semibold text-[11px]">No. FPTK & Posisi</th>
              <th class="py-3 px-4 font-semibold text-[11px]">Divisi & Cabang</th>
              <th class="py-3 px-4 font-semibold text-[11px]">Kebutuhan</th>
              <th class="py-3 px-4 font-semibold text-[11px]">Progres Pemenuhan</th>
              <th class="py-3 px-4 font-semibold text-[11px]">Tanggal Pengajuan</th>
              <th class="py-3 px-4 font-semibold text-[11px]">Status</th>
              <th class="py-3 px-4 text-right font-semibold text-[11px] w-20">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="item in filteredRequests" :key="item.id" class="hover:bg-slate-50 transition-colors">
              <td class="py-3.5 px-4 align-middle">
                <div class="font-bold text-xs text-slate-900">{{ item.posisi_dibutuhkan || item.position_name || item.position_title || 'Staff' }}</div>
                <div class="text-[11px] text-slate-400 font-mono mt-0.5">#{{ item.id || item.request_number }}</div>
              </td>
              <td class="py-3.5 px-4 align-middle text-slate-700">
                <div class="font-medium">{{ item.division_name || item.department || item.division?.name || '-' }}</div>
                <div class="text-[11px] text-slate-400">{{ item.lokasi_penempatan || item.branch || item.location || '-' }}</div>
              </td>
              <td class="py-3.5 px-4 align-middle font-bold text-slate-900">
                {{ item.jumlah_karyawan_dibutuhkan || item.quantity || 1 }} Orang
              </td>
              <td class="py-3.5 px-4 align-middle">
                <div class="w-32 space-y-1">
                  <div class="flex items-center justify-between text-[10px] font-semibold text-slate-600">
                    <span>{{ item.fulfilled_count || 0 }}/{{ item.jumlah_karyawan_dibutuhkan || item.quantity || 1 }}</span>
                    <span>{{ Math.min(100, Math.round(((item.fulfilled_count || 0) / (item.jumlah_karyawan_dibutuhkan || item.quantity || 1)) * 100)) }}%</span>
                  </div>
                  <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                    <div
                      class="bg-blue-600 h-1.5 rounded-full transition-all duration-300"
                      :style="{ width: `${Math.min(100, Math.round(((item.fulfilled_count || 0) / (item.jumlah_karyawan_dibutuhkan || item.quantity || 1)) * 100))}%` }"
                    ></div>
                  </div>
                </div>
              </td>
              <td class="py-3.5 px-4 align-middle text-slate-600 whitespace-nowrap text-xs">
                {{ item.tanggal_pengajuan || item.submission_date || item.created_at || '-' }}
              </td>
              <td class="py-3.5 px-4 align-middle">
                <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-semibold border', getApprovalBadge(item.status || item.approval_status)]">
                  {{ item.status || item.approval_status || 'Pending' }}
                </span>
              </td>
              <td class="py-3.5 px-4 align-middle text-right whitespace-nowrap">
                <button
                  @click="openRequestDetail(item)"
                  class="px-2.5 py-1 text-blue-600 hover:bg-blue-50 rounded-md text-xs font-semibold transition-colors cursor-pointer"
                >
                  Detail
                </button>
              </td>
            </tr>
            <tr v-if="!filteredRequests.length">
              <td colspan="7" class="py-12 text-center text-xs text-slate-500">
                Tidak ada data permintaan tenaga kerja yang sesuai.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Request Detail Modal -->
    <div
      v-if="selectedRequest"
      class="fixed inset-0 z-[100] bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
      @click.self="selectedRequest = null"
    >
      <div class="bg-white rounded-2xl border border-slate-200 w-full max-w-lg shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
          <div>
            <h3 class="text-sm font-bold text-slate-900">Detail Permintaan FPTK</h3>
            <p class="text-[11px] text-slate-500 mt-0.5">Nomor FPTK: #{{ selectedRequest.id || selectedRequest.request_number }}</p>
          </div>
          <button @click="selectedRequest = null" class="text-slate-400 hover:text-slate-600 text-xl font-bold cursor-pointer">&times;</button>
        </div>

        <div class="p-6 space-y-3.5 text-xs">
          <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-2.5">
            <div class="flex justify-between border-b border-slate-200/60 pb-2">
              <span class="text-slate-500">Posisi:</span>
              <span class="font-bold text-slate-900">{{ selectedRequest.posisi_dibutuhkan || selectedRequest.position_name || selectedRequest.position_title }}</span>
            </div>
            <div class="flex justify-between border-b border-slate-200/60 pb-2">
              <span class="text-slate-500">Divisi / Departemen:</span>
              <span class="font-semibold text-slate-800">{{ selectedRequest.division_name || selectedRequest.department || selectedRequest.division?.name || '-' }}</span>
            </div>
            <div class="flex justify-between border-b border-slate-200/60 pb-2">
              <span class="text-slate-500">Cabang / Lokasi:</span>
              <span class="font-semibold text-slate-800">{{ selectedRequest.lokasi_penempatan || selectedRequest.branch || selectedRequest.location || '-' }}</span>
            </div>
            <div class="flex justify-between border-b border-slate-200/60 pb-2">
              <span class="text-slate-500">Jumlah Kebutuhan:</span>
              <span class="font-bold text-slate-900">{{ selectedRequest.jumlah_karyawan_dibutuhkan || selectedRequest.quantity || 1 }} Orang</span>
            </div>
            <div class="flex justify-between border-b border-slate-200/60 pb-2">
              <span class="text-slate-500">Status Persetujuan:</span>
              <span class="font-bold text-emerald-600">{{ selectedRequest.status || selectedRequest.approval_status }}</span>
            </div>
            <div class="flex justify-between border-b border-slate-200/60 pb-2">
              <span class="text-slate-500">Tanggal Pengajuan:</span>
              <span class="font-semibold text-slate-800">{{ selectedRequest.tanggal_pengajuan || selectedRequest.submission_date || selectedRequest.created_at || '-' }}</span>
            </div>
            <div v-if="selectedRequest.nama_pengaju" class="flex justify-between border-b border-slate-200/60 pb-2">
              <span class="text-slate-500">Diajukan Oleh:</span>
              <span class="font-semibold text-slate-800">{{ selectedRequest.nama_pengaju }} ({{ selectedRequest.posisi_pengaju || '-' }})</span>
            </div>
            <div>
              <span class="text-slate-500 block mb-1">Keterangan / Kualifikasi:</span>
              <p class="text-slate-800 leading-relaxed whitespace-pre-line">{{ selectedRequest.requirements_kualifikasi || selectedRequest.keterangan || selectedRequest.reason || selectedRequest.justification || 'Kebutuhan operasional penambahan personil' }}</p>
            </div>
          </div>
        </div>

        <div class="px-6 py-3.5 border-t border-slate-100 flex items-center justify-end bg-slate-50">
          <button
            @click="selectedRequest = null"
            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg transition-colors cursor-pointer text-xs"
          >
            Tutup
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRekrutmenStore } from '../stores/rekrutmen';
import { 
  Search, Plus, CheckCircle2, AlertCircle
} from 'lucide-vue-next';

const store = useRekrutmenStore();
const statusFilter = ref('all');
const searchQuery = ref('');
const toastMessage = ref(null);
const toastType = ref('success');
const selectedRequest = ref(null);

onMounted(() => {
  store.fetchRequests('', false).catch(() => {});
});

const requests = computed(() => store.requests || []);

const approvedCount = computed(() => requests.value.filter(r => {
  const s = String(r.status || r.approval_status || r.raw_status || '').toLowerCase();
  return s.includes('approv') || s.includes('setuju');
}).length);

const pendingCount = computed(() => requests.value.filter(r => {
  const s = String(r.status || r.approval_status || r.raw_status || '').toLowerCase();
  return s.includes('pend') || s.includes('tunggu');
}).length);

const filteredRequests = computed(() => {
  let list = requests.value;

  if (statusFilter.value === 'approved') {
    list = list.filter(r => {
      const s = String(r.status || r.approval_status || r.raw_status || '').toLowerCase();
      return s.includes('approv') || s.includes('setuju');
    });
  } else if (statusFilter.value === 'pending') {
    list = list.filter(r => {
      const s = String(r.status || r.approval_status || r.raw_status || '').toLowerCase();
      return s.includes('pend') || s.includes('tunggu');
    });
  }

  if (!searchQuery.value) return list;
  const q = searchQuery.value.toLowerCase();
  return list.filter(r => {
    const pos = String(r.posisi_dibutuhkan || r.position_name || r.position_title || '').toLowerCase();
    const num = String(r.id || r.request_number || '').toLowerCase();
    const div = String(r.division_name || r.department || '').toLowerCase();
    const loc = String(r.lokasi_penempatan || r.branch || r.location || '').toLowerCase();
    const name = String(r.nama_pengaju || '').toLowerCase();
    return pos.includes(q) || num.includes(q) || div.includes(q) || loc.includes(q) || name.includes(q);
  });
});

const getApprovalBadge = (status) => {
  if (status === 'Approved' || status === 'Disetujui') return 'bg-emerald-50 text-emerald-700 border-emerald-200';
  if (status === 'Rejected' || status === 'Ditolak') return 'bg-rose-50 text-rose-700 border-rose-200';
  return 'bg-amber-50 text-amber-700 border-amber-200';
};

const openRequestDetail = (item) => {
  selectedRequest.value = item;
};
</script>
