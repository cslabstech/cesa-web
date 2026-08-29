<template>
  <div class="space-y-6">
    <!-- Breadcrumb & Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <div class="flex items-center gap-1.5 text-xs text-gray-500 font-medium mb-1">
          <span>Manpower Requests</span>
          <ChevronRight class="w-3.5 h-3.5 text-gray-400" />
          <span class="text-gray-700">List</span>
        </div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">
          Manpower Requests
        </h1>
      </div>

      <!-- Action Button -->
      <div class="flex items-center gap-3">
        <a
          href="/man-power"
          target="_blank"
          class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-4 py-2.5 rounded-lg shadow-xs transition-colors cursor-pointer"
        >
          <PlusCircle class="w-4 h-4" />
          <span>New Manpower Request</span>
        </a>
      </div>
    </div>

    <!-- Alert / Toast Message -->
    <div
      v-if="toastMessage"
      :class="[
        'p-4 rounded-xl border flex items-center justify-between transition-all shadow-sm',
        toastType === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800'
      ]"
    >
      <div class="flex items-center gap-2 text-xs font-semibold">
        <CheckCircle2 v-if="toastType === 'success'" class="w-4 h-4 text-emerald-600" />
        <AlertCircle v-else class="w-4 h-4 text-rose-600" />
        <span>{{ toastMessage }}</span>
      </div>
      <button @click="toastMessage = null" class="text-gray-400 hover:text-gray-600 font-bold">&times;</button>
    </div>

    <!-- White Table Card -->
    <div class="bg-white rounded-xl border border-gray-200/90 shadow-xs overflow-hidden">
      <!-- Toolbar -->
      <div class="p-4 border-b border-gray-100 flex items-center justify-end gap-3">
        <div class="relative w-72">
          <Search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
          <input
            v-model="searchQuery"
            @input="handleSearch"
            type="text"
            placeholder="Search"
            class="w-full bg-white border border-gray-200 rounded-lg pl-9 pr-3 py-1.5 text-xs text-gray-800 placeholder-gray-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all shadow-[0_1px_2px_rgba(0,0,0,0.02)]"
          />
        </div>

        <button class="flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
          <Filter class="w-3.5 h-3.5 text-gray-500" />
          <span class="text-[11px] font-bold text-gray-700">0</span>
        </button>

        <button class="p-1.5 text-gray-500 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors" title="Toggle Columns">
          <Columns3 class="w-4 h-4" />
        </button>
      </div>

      <!-- Data Table -->
      <!-- Loading State -->
      <LoadingState
        v-if="store.loading.requests"
        title="Sedang memuat data..."
        subtitle="Menyiapkan daftar Manpower Request & FPTK..."
      />

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead>
            <tr class="bg-gray-50/50 text-gray-900 border-b border-gray-200/80">
              <th class="py-3 px-4 w-10">
                <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4" />
              </th>
              <th class="py-3 px-4 font-semibold">
                <div class="flex items-center gap-1 cursor-pointer select-none hover:text-blue-600">
                  <span>Requested Position</span>
                  <ChevronDown class="w-3.5 h-3.5 text-gray-400" />
                </div>
              </th>
              <th class="py-3 px-4 font-semibold">
                <div class="flex items-center gap-1 cursor-pointer select-none hover:text-blue-600">
                  <span>Quantity</span>
                  <ChevronDown class="w-3.5 h-3.5 text-gray-400" />
                </div>
              </th>
              <th class="py-3 px-4 font-semibold">
                <div class="flex items-center gap-1 cursor-pointer select-none hover:text-blue-600">
                  <span>Fulfillment Progress</span>
                </div>
              </th>
              <th class="py-3 px-4 font-semibold">
                <div class="flex items-center gap-1 cursor-pointer select-none hover:text-blue-600">
                  <span>Submission Date</span>
                  <ChevronDown class="w-3.5 h-3.5 text-gray-400" />
                </div>
              </th>
              <th class="py-3 px-4 font-semibold">
                <div class="flex items-center gap-1 cursor-pointer select-none hover:text-blue-600">
                  <span>Approval Status</span>
                  <ChevronDown class="w-3.5 h-3.5 text-gray-400" />
                </div>
              </th>
              <th class="py-3 px-4 text-right w-16"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr
              v-for="item in requests"
              :key="item.id"
              class="hover:bg-blue-50/20 transition-colors group"
            >
              <!-- Checkbox -->
              <td class="py-4 px-4 align-top">
                <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4 mt-0.5" />
              </td>

              <!-- Requested Position + Subtitles -->
              <td class="py-4 px-4 align-top max-w-sm">
                <div
                  class="font-semibold text-sm text-gray-900 group-hover:text-blue-600 transition-colors cursor-pointer"
                  @click="openDetail(item)"
                >
                  {{ item.posisi_dibutuhkan }}
                </div>
                <div class="text-xs text-gray-500 mt-1 leading-relaxed">
                  {{ item.position_description }}
                </div>
              </td>

              <!-- Quantity Badge -->
              <td class="py-4 px-4 align-top whitespace-nowrap">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-600 border border-blue-200">
                  {{ item.jumlah_karyawan_dibutuhkan }} orang
                </span>
              </td>

              <!-- Fulfillment Progress -->
              <td class="py-4 px-4 align-top">
                <span
                  :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-semibold border',
                    getFulfillmentBadge(item.fulfillment_status)
                  ]"
                >
                  {{ item.fulfillment_status }}
                </span>
                <div class="text-xs text-gray-500 mt-1">
                  {{ item.fulfillment_summary }}
                </div>
              </td>

              <!-- Submission Date -->
              <td class="py-4 px-4 align-top text-gray-600 whitespace-nowrap">
                {{ item.tanggal_pengajuan }}
              </td>

              <!-- Approval Status Badge & Desc -->
              <td class="py-4 px-4 align-top max-w-xs">
                <span
                  :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-semibold border',
                    getApprovalBadge(item.status)
                  ]"
                >
                  {{ item.status }}
                </span>
                <div v-if="item.approval_description" class="text-xs text-gray-500 mt-1 font-normal leading-relaxed">
                  {{ item.approval_description }}
                </div>
              </td>

              <!-- Action Buttons (Approve, Reject, Detail, Menu) -->
              <td class="py-4 px-4 align-top text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-1.5">
                  <!-- Quick Approve Button (if pending/hold) -->
                  <button
                    v-if="item.can_approve_reject"
                    @click="handleApprove(item)"
                    class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors border border-emerald-200/60 shadow-2xs cursor-pointer"
                    title="Setujui (Approve) Permintaan Ini"
                  >
                    <CheckCircle2 class="w-4 h-4" />
                  </button>

                  <!-- Quick Reject Button (if pending/hold) -->
                  <button
                    v-if="item.can_approve_reject"
                    @click="handleReject(item)"
                    class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition-colors border border-rose-200/60 shadow-2xs cursor-pointer"
                    title="Tolak (Reject) Permintaan Ini"
                  >
                    <XCircle class="w-4 h-4" />
                  </button>

                  <!-- More Dropdown -->
                  <div class="relative inline-block text-left">
                    <button
                      @click="activeActionId = activeActionId === item.id ? null : item.id"
                      class="p-1.5 text-gray-500 hover:text-blue-600 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer"
                    >
                      <MoreVertical class="w-4 h-4" />
                    </button>

                    <div
                      v-if="activeActionId === item.id"
                      class="origin-top-right absolute right-0 mt-1 w-48 rounded-xl shadow-lg bg-white border border-gray-100 py-1.5 z-20"
                      @click="activeActionId = null"
                    >
                      <button
                        @click="openDetail(item)"
                        class="w-full text-left flex items-center gap-2 px-3.5 py-2 text-xs text-gray-700 hover:bg-gray-50 cursor-pointer"
                      >
                        <Eye class="w-3.5 h-3.5 text-gray-400" />
                        <span>Lihat Detail MPP</span>
                      </button>

                      <button
                        v-if="item.can_approve_reject"
                        @click="handleApprove(item)"
                        class="w-full text-left flex items-center gap-2 px-3.5 py-2 text-xs text-emerald-700 hover:bg-emerald-50 font-semibold cursor-pointer"
                      >
                        <CheckCircle2 class="w-3.5 h-3.5 text-emerald-600" />
                        <span>Approve Request</span>
                      </button>

                      <button
                        v-if="item.can_approve_reject"
                        @click="handleReject(item)"
                        class="w-full text-left flex items-center gap-2 px-3.5 py-2 text-xs text-rose-700 hover:bg-rose-50 font-semibold cursor-pointer"
                      >
                        <XCircle class="w-3.5 h-3.5 text-rose-600" />
                        <span>Reject Request</span>
                      </button>

                      <a
                        v-if="item.public_progress_url"
                        :href="item.public_progress_url"
                        target="_blank"
                        class="flex items-center gap-2 px-3.5 py-2 text-xs text-gray-700 hover:bg-gray-50"
                      >
                        <ExternalLink class="w-3.5 h-3.5 text-gray-400" />
                        <span>Lihat Link Progress</span>
                      </a>
                    </div>
                  </div>
                </div>
              </td>
            </tr>

            <!-- Empty State -->
            <tr v-if="!requests?.length">
              <td colspan="7" class="py-16 text-center text-xs text-gray-500">
                Tidak ada data Manpower Requests yang ditemukan.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- MPP Detail Modal -->
    <div
      v-if="selectedItem"
      class="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-center justify-center p-4"
      @click.self="selectedItem = null"
    >
      <div class="bg-white rounded-2xl border border-gray-200 w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-2xl custom-scrollbar">
        <div class="p-6 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white z-10">
          <div>
            <div class="text-xs text-gray-500 font-semibold uppercase tracking-wider">Detail Manpower Request</div>
            <h2 class="text-lg font-bold text-gray-900 mt-0.5">{{ selectedItem.posisi_dibutuhkan }}</h2>
          </div>
          <button @click="selectedItem = null" class="text-gray-400 hover:text-gray-600 text-xl font-bold cursor-pointer">&times;</button>
        </div>

        <div class="p-6 space-y-6 text-xs text-gray-800">
          <!-- Status Banner -->
          <div class="flex items-center justify-between p-4 rounded-xl bg-gray-50 border border-gray-200">
            <div>
              <span class="text-gray-500 font-medium">Status Approval</span>
              <div class="font-bold text-sm mt-0.5">{{ selectedItem.status }}</div>
            </div>
            <div class="text-right">
              <span class="text-gray-500 font-medium">Progress Pemenuhan</span>
              <div class="font-bold text-sm text-blue-600 mt-0.5">{{ selectedItem.fulfillment_status }}</div>
            </div>
          </div>

          <!-- Section: Info Pengajuan -->
          <div>
            <h3 class="font-bold text-sm text-gray-900 border-b pb-2 mb-3">Informasi Pengajuan</h3>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <span class="text-gray-500 font-semibold">Nama Pengaju:</span>
                <div class="font-bold text-gray-900 mt-0.5">{{ selectedItem.nama_pengaju || '-' }}</div>
              </div>
              <div>
                <span class="text-gray-500 font-semibold">Posisi Pengaju:</span>
                <div class="text-gray-900 mt-0.5">{{ selectedItem.posisi_pengaju || '-' }}</div>
              </div>
              <div>
                <span class="text-gray-500 font-semibold">Divisi / Perusahaan:</span>
                <div class="text-gray-900 mt-0.5">{{ selectedItem.division_name }} / {{ selectedItem.company_name }}</div>
              </div>
              <div>
                <span class="text-gray-500 font-semibold">Lokasi Penempatan:</span>
                <div class="text-gray-900 mt-0.5">{{ selectedItem.lokasi_penempatan }}</div>
              </div>
              <div>
                <span class="text-gray-500 font-semibold">Jumlah Karyawan Dibutuhkan:</span>
                <div class="font-bold text-blue-600 mt-0.5">{{ selectedItem.jumlah_karyawan_dibutuhkan }} Orang</div>
              </div>
              <div>
                <span class="text-gray-500 font-semibold">Estimasi Tanggal Join:</span>
                <div class="text-gray-900 mt-0.5">{{ selectedItem.estimasi_tanggal_join }}</div>
              </div>
            </div>
          </div>

          <!-- Section: Kualifikasi & Jobdesk -->
          <div v-if="selectedItem.requirements_kualifikasi">
            <h3 class="font-bold text-sm text-gray-900 border-b pb-2 mb-3">Kualifikasi / Requirements</h3>
            <div class="whitespace-pre-line bg-gray-50 p-4 rounded-xl border border-gray-200 text-gray-700 leading-relaxed">
              {{ selectedItem.requirements_kualifikasi }}
            </div>
          </div>

          <div v-if="selectedItem.job_description">
            <h3 class="font-bold text-sm text-gray-900 border-b pb-2 mb-3">Job Description</h3>
            <div class="whitespace-pre-line bg-gray-50 p-4 rounded-xl border border-gray-200 text-gray-700 leading-relaxed">
              {{ selectedItem.job_description }}
            </div>
          </div>
        </div>

        <div class="p-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between sticky bottom-0">
          <div class="flex items-center gap-2">
            <button
              v-if="selectedItem.can_approve_reject"
              @click="handleApprove(selectedItem); selectedItem = null"
              class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg text-xs cursor-pointer shadow-xs"
            >
              Setujui (Approve)
            </button>
            <button
              v-if="selectedItem.can_approve_reject"
              @click="handleReject(selectedItem); selectedItem = null"
              class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-semibold rounded-lg text-xs cursor-pointer shadow-xs"
            >
              Tolak (Reject)
            </button>
          </div>

          <button
            @click="selectedItem = null"
            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-xs font-semibold rounded-lg cursor-pointer"
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
import {
  PlusCircle,
  Search,
  MoreVertical,
  ChevronRight,
  ExternalLink,
  Eye,
  CheckCircle2,
  XCircle,
  AlertCircle
} from 'lucide-vue-next';
import { useRekrutmenStore } from '../stores/rekrutmen';
import LoadingState from '../components/LoadingState.vue';

const store = useRekrutmenStore();
const requests = computed(() => store.requests);
const searchQuery = ref('');
const activeActionId = ref(null);
const selectedItem = ref(null);
const toastMessage = ref(null);
const toastType = ref('success');

onMounted(() => {
  store.fetchRequests(searchQuery.value, true);
});

let debounceTimer = null;
const handleSearch = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    store.fetchRequests(searchQuery.value, true);
  }, 200);
};

const showToast = (msg, type = 'success') => {
  toastMessage.value = msg;
  toastType.value = type;
  setTimeout(() => {
    toastMessage.value = null;
  }, 4000);
};

const handleApprove = async (item) => {
  if (!confirm(`Apakah Anda yakin ingin menyetujui (Approve) permintaan "${item.posisi_dibutuhkan}"?`)) {
    return;
  }
  try {
    const res = await store.approveRequest(item.id);
    showToast(res.message || 'Permintaan berhasil disetujui!', 'success');
  } catch (err) {
    showToast(err.response?.data?.message || 'Gagal menyetujui permintaan.', 'error');
  }
};

const handleReject = async (item) => {
  if (!confirm(`Apakah Anda yakin ingin menolak (Reject) permintaan "${item.posisi_dibutuhkan}"?`)) {
    return;
  }
  try {
    const res = await store.rejectRequest(item.id);
    showToast(res.message || 'Permintaan telah ditolak.', 'success');
  } catch (err) {
    showToast(err.response?.data?.message || 'Gagal menolak permintaan.', 'error');
  }
};

const openDetail = (item) => {
  selectedItem.value = item;
};

const getFulfillmentBadge = (status) => {
  const s = status?.toLowerCase() || '';
  if (s.includes('closed') || s.includes('selesai')) {
    return 'bg-gray-100 text-gray-700 border-gray-200';
  }
  if (s.includes('process') || s.includes('proses')) {
    return 'bg-blue-50 text-blue-600 border-blue-200';
  }
  return 'bg-rose-50 text-rose-600 border-rose-200';
};

const getApprovalBadge = (status) => {
  const s = status?.toLowerCase() || '';
  if (s.includes('approved') || s.includes('disetujui')) {
    return 'bg-emerald-50 text-emerald-700 border-emerald-200';
  }
  if (s.includes('rejected') || s.includes('ditolak')) {
    return 'bg-rose-50 text-rose-700 border-rose-200';
  }
  return 'bg-amber-50 text-amber-700 border-amber-200';
};

onMounted(() => {
  store.fetchRequests();
});
</script>
