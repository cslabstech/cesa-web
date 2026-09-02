<template>
  <div class="space-y-6">
    <!-- Breadcrumb & Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <div class="flex items-center gap-1.5 text-xs text-gray-500 font-medium mb-1">
          <span>Recruitment Progress</span>
          <ChevronRight class="w-3.5 h-3.5 text-gray-400" />
          <span class="text-gray-700">List</span>
        </div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">
          Recruitment Progress
        </h1>
      </div>

      <div class="flex items-center gap-3">
        <button
          @click="exportExcel"
          :disabled="isExporting"
          class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-4 py-2.5 rounded-lg shadow-xs transition-colors cursor-pointer disabled:opacity-50"
        >
          <span v-if="isExporting" class="inline-block w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
          <FileSpreadsheet v-else class="w-4 h-4" />
          <span>{{ isExporting ? 'Mengekspor...' : 'Export Excel' }}</span>
        </button>
      </div>
    </div>

    <!-- White Table Card -->
    <div class="bg-white rounded-xl border border-gray-200/90 shadow-xs overflow-hidden">
      <!-- Toolbar -->
      <div class="p-4 border-b border-gray-100 flex items-center justify-between gap-3">
        <div class="text-xs font-bold text-gray-700 uppercase tracking-wider">
          Monitoring Pemenuhan Tenaga Kerja
        </div>

        <div class="flex items-center gap-3">
          <div class="relative w-64">
            <Search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search posisi / lokasi..."
              class="w-full bg-white border border-gray-200 rounded-lg pl-9 pr-3 py-1.5 text-xs text-gray-800 placeholder-gray-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
            />
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <LoadingState
        v-if="store.loading.progress"
        title="Sedang memuat data..."
        subtitle="Menyiapkan laporan progres rekrutmen & monitoring..."
      />

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead>
            <tr class="bg-gray-50/50 text-gray-900 border-b border-gray-200/80">
              <th class="py-3 px-4 font-semibold">Posisi Lowongan</th>
              <th class="py-3 px-4 font-semibold">Perusahaan & Lokasi</th>
              <th class="py-3 px-4 font-semibold">Kebutuhan</th>
              <th class="py-3 px-4 font-semibold">Total Pelamar</th>
              <th class="py-3 px-4 font-semibold">Dalam Proses</th>
              <th class="py-3 px-4 font-semibold">Hired (Lolos)</th>
              <th class="py-3 px-4 font-semibold">Persentase Pemenuhan</th>
              <th class="py-3 px-4 font-semibold">Status Health</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr
              v-for="item in filteredPositions"
              :key="item.job_posting_id"
              class="hover:bg-blue-50/20 transition-colors"
            >
              <!-- Posisi -->
              <td class="py-4 px-4 font-bold text-gray-900">
                {{ item.position }}
              </td>

              <!-- Perusahaan & Lokasi -->
              <td class="py-4 px-4 text-gray-600">
                <div>{{ item.company }}</div>
                <div class="text-[11px] text-gray-400">{{ item.location }}</div>
              </td>

              <!-- Kebutuhan -->
              <td class="py-4 px-4 font-semibold text-gray-900">
                {{ item.needed }} orang
              </td>

              <!-- Total Pelamar -->
              <td class="py-4 px-4 font-semibold text-blue-600">
                {{ item.total_applicants }} kandidat
              </td>

              <!-- Dalam Proses -->
              <td class="py-4 px-4 font-semibold text-amber-600">
                {{ item.in_process }} kandidat
              </td>

              <!-- Hired -->
              <td class="py-4 px-4 font-semibold text-emerald-600">
                {{ item.hired }} orang
              </td>

              <!-- Progress Bar -->
              <td class="py-4 px-4 w-44">
                <div class="flex items-center gap-2">
                  <div class="flex-1 bg-gray-100 rounded-full h-2 overflow-hidden border border-gray-200">
                    <div
                      class="bg-blue-600 h-full rounded-full transition-all"
                      :style="{ width: `${Math.min(item.fulfillment_percentage || 0, 100)}%` }"
                    ></div>
                  </div>
                  <span class="text-[11px] font-bold text-gray-700">{{ item.fulfillment_percentage || 0 }}%</span>
                </div>
              </td>

              <!-- Status Health -->
              <td class="py-4 px-4">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                  {{ item.cycle_health || 'Normal' }}
                </span>
              </td>
            </tr>

            <tr v-if="!filteredPositions?.length">
              <td colspan="8" class="py-16 text-center text-xs text-gray-500">
                Tidak ada data progres rekrutmen.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { ChevronRight, Search, FileSpreadsheet } from 'lucide-vue-next';
import { useRekrutmenStore } from '../stores/rekrutmen';
import LoadingState from '../components/LoadingState.vue';

const store = useRekrutmenStore();
const searchQuery = ref('');

const positions = computed(() => store.progressData?.positions || []);

const filteredPositions = computed(() => {
  if (!searchQuery.value) return positions.value;
  const q = searchQuery.value.toLowerCase();
  return positions.value.filter(p => 
    p.position?.toLowerCase().includes(q) || 
    p.location?.toLowerCase().includes(q) ||
    p.company?.toLowerCase().includes(q)
  );
});

const isExporting = ref(false);

const exportExcel = async () => {
  if (isExporting.value) return;
  isExporting.value = true;
  try {
    const response = await axios.get('/rekrutmen/api/progress-report/export', {
      responseType: 'blob',
    });

    let filename = 'recruitment-progress-mpp.xlsx';
    const disposition = response.headers['content-disposition'];
    if (disposition && disposition.includes('filename=')) {
      const filenameMatch = disposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
      if (filenameMatch && filenameMatch[1]) {
        filename = filenameMatch[1].replace(/['"]/g, '');
      }
    }

    const blob = new Blob([response.data], { 
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' 
    });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', filename);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
  } catch (err) {
    console.error('Export failed', err);
    // Fallback direct link
    window.location.href = '/rekrutmen/api/progress-report/export';
  } finally {
    isExporting.value = false;
  }
};

onMounted(() => {
  store.fetchProgressReport(true);
});
</script>
