<template>
  <div class="space-y-6 pb-12">
    <!-- Top Header & Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <div class="flex items-center gap-2.5">
          <h1 class="text-xl font-semibold text-zinc-900 tracking-tight">
            Monitoring & Progres Rekrutmen
          </h1>
          <Badge variant="outline" class="font-mono text-[11px] text-zinc-600 bg-zinc-50 border-zinc-200">
            {{ positions.length }} Posisi
          </Badge>
        </div>
        <p class="text-xs text-zinc-500 mt-1">
          Pantau rasio pemenuhan personil Manpower Planning (MPP), kandidat dalam proses seleksi, dan status pemenuhan
        </p>
      </div>

      <div class="flex items-center gap-2">
        <Button
          variant="outline"
          size="sm"
          @click="refreshData"
          :disabled="isRefreshing"
          class="text-xs h-8 text-zinc-700 hover:text-zinc-900"
        >
          <RotateCw :class="['w-3.5 h-3.5 mr-1.5', isRefreshing ? 'animate-spin' : '']" />
          <span>Segarkan</span>
        </Button>

        <Button
          variant="default"
          size="sm"
          @click="exportExcel"
          :disabled="isExporting"
          class="bg-zinc-900 hover:bg-zinc-800 text-white text-xs h-8 gap-1.5"
        >
          <RotateCw v-if="isExporting" class="w-3.5 h-3.5 animate-spin" />
          <FileSpreadsheet v-else class="w-3.5 h-3.5" />
          <span>{{ isExporting ? 'Mengekspor...' : 'Export Excel' }}</span>
        </Button>
      </div>
    </div>

    <!-- Alert / Toast Banner -->
    <transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="transform -translate-y-2 opacity-0"
      enter-to-class="transform translate-y-0 opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="transform translate-y-0 opacity-100"
      leave-to-class="transform -translate-y-2 opacity-0"
    >
      <div
        v-if="toastMessage"
        :class="[
          'p-3 rounded-lg border flex items-center justify-between text-xs font-medium shadow-2xs',
          toastType === 'success'
            ? 'bg-emerald-50/70 border-emerald-200 text-emerald-800'
            : 'bg-rose-50/70 border-rose-200 text-rose-800'
        ]"
      >
        <div class="flex items-center gap-2">
          <CheckCircle2 v-if="toastType === 'success'" class="w-4 h-4 text-emerald-600 shrink-0" />
          <AlertCircle v-else class="w-4 h-4 text-rose-600 shrink-0" />
          <span>{{ toastMessage }}</span>
        </div>
        <button
          @click="toastMessage = null"
          class="text-zinc-400 hover:text-zinc-700 font-bold px-1 rounded hover:bg-zinc-200/50"
        >
          &times;
        </button>
      </div>
    </transition>

    <!-- KPI Summary Metrics (New York Style Clean Cards) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5">
      <Card class="hover:border-zinc-300">
        <CardHeader class="p-4 pb-2 flex flex-row items-center justify-between space-y-0">
          <span class="text-xs font-medium text-zinc-500">Posisi Dipantau</span>
          <Briefcase class="w-4 h-4 text-zinc-400" />
        </CardHeader>
        <CardContent class="p-4 pt-0">
          <div class="text-2xl font-bold tracking-tight text-zinc-900">{{ positions.length }}</div>
          <p class="text-[11px] text-zinc-500 mt-0.5">
            Total jabatan dalam pipeline
          </p>
        </CardContent>
      </Card>

      <Card class="hover:border-zinc-300">
        <CardHeader class="p-4 pb-2 flex flex-row items-center justify-between space-y-0">
          <span class="text-xs font-medium text-zinc-500">Target Kebutuhan</span>
          <Users class="w-4 h-4 text-zinc-400" />
        </CardHeader>
        <CardContent class="p-4 pt-0">
          <div class="text-2xl font-bold tracking-tight text-zinc-900">{{ totalNeeded }}</div>
          <p class="text-[11px] text-zinc-500 mt-0.5">
            Total personil yang dicari
          </p>
        </CardContent>
      </Card>

      <Card class="hover:border-zinc-300">
        <CardHeader class="p-4 pb-2 flex flex-row items-center justify-between space-y-0">
          <span class="text-xs font-medium text-zinc-500">Dalam Proses</span>
          <Clock class="w-4 h-4 text-zinc-400" />
        </CardHeader>
        <CardContent class="p-4 pt-0">
          <div class="text-2xl font-bold tracking-tight text-zinc-900">{{ totalInProcess }}</div>
          <p class="text-[11px] text-zinc-400 mt-0.5">
            Kandidat sedang diseleksi
          </p>
        </CardContent>
      </Card>

      <Card class="hover:border-zinc-300">
        <CardHeader class="p-4 pb-2 flex flex-row items-center justify-between space-y-0">
          <span class="text-xs font-medium text-zinc-500">Terpenuhi (Hired)</span>
          <CheckCircle2 class="w-4 h-4 text-zinc-400" />
        </CardHeader>
        <CardContent class="p-4 pt-0">
          <div class="text-2xl font-bold tracking-tight text-zinc-900">{{ totalHired }}</div>
          <p class="text-[11px] text-zinc-400 mt-0.5">
            Kandidat telah diterima kerja
          </p>
        </CardContent>
      </Card>
    </div>

    <!-- Filter & Toolbar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3 bg-white rounded-xl border border-zinc-200 shadow-2xs">
      <div class="text-xs font-semibold text-zinc-700 uppercase tracking-wider">
        Monitoring Pemenuhan Pipeline
      </div>

      <div class="relative w-full sm:w-80">
        <Search class="w-3.5 h-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-zinc-400" />
        <Input
          v-model="searchQuery"
          type="text"
          placeholder="Cari posisi, lokasi, perusahaan..."
          class="pl-8 h-8 text-xs bg-zinc-50 focus:bg-white"
        />
        <button
          v-if="searchQuery"
          type="button"
          @click="searchQuery = ''"
          class="absolute right-2.5 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 text-xs font-bold"
        >
          &times;
        </button>
      </div>
    </div>

    <!-- SKELETON LOADING STATE -->
    <div v-if="isLoading" class="bg-white rounded-xl border border-zinc-200 shadow-2xs p-4 space-y-3">
      <div v-for="i in 5" :key="i" class="flex items-center justify-between gap-4 py-3 border-b border-zinc-100 last:border-0">
        <div class="space-y-1.5 w-1/4">
          <Skeleton class="h-4 w-36" />
          <Skeleton class="h-3 w-20" />
        </div>
        <Skeleton class="h-4 w-16" />
        <Skeleton class="h-4 w-16" />
        <Skeleton class="h-4 w-16" />
        <Skeleton class="h-4 w-28" />
        <Skeleton class="h-5 w-20 rounded-full" />
      </div>
    </div>

    <!-- TABLE VIEW: Shadcn Table Component -->
    <div v-else class="bg-white rounded-xl border border-zinc-200 shadow-2xs overflow-hidden">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead class="w-[28%]">Posisi Lowongan</TableHead>
            <TableHead class="w-[22%]">Perusahaan & Lokasi</TableHead>
            <TableHead class="w-[10%]">Kebutuhan</TableHead>
            <TableHead class="w-[10%]">Total Pelamar</TableHead>
            <TableHead class="w-[10%]">Dalam Proses</TableHead>
            <TableHead class="w-[10%]">Hired</TableHead>
            <TableHead class="w-[16%]">Persentase Pemenuhan</TableHead>
            <TableHead class="w-[10%] text-right">Status Health</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow
            v-for="item in filteredPositions"
            :key="item.job_posting_id"
            class="group hover:bg-zinc-50/80 transition-colors"
          >
            <!-- Posisi -->
            <TableCell class="py-3.5">
              <div class="font-semibold text-xs text-zinc-900">
                {{ item.position }}
              </div>
              <div class="text-[11px] text-zinc-400 font-mono mt-0.5">
                ID #{{ item.job_posting_id }}
              </div>
            </TableCell>

            <!-- Perusahaan & Lokasi -->
            <TableCell class="py-3.5">
              <div class="font-medium text-xs text-zinc-800 truncate max-w-[200px]">
                {{ item.company }}
              </div>
              <div class="text-[11px] text-zinc-400 flex items-center gap-1 mt-0.5">
                <MapPin class="w-3 h-3 text-zinc-400 shrink-0" />
                <span class="truncate">{{ item.location || 'Indonesia' }}</span>
              </div>
            </TableCell>

            <!-- Kebutuhan -->
            <TableCell class="py-3.5 font-semibold text-zinc-900 text-xs">
              {{ item.needed }} orang
            </TableCell>

            <!-- Total Pelamar -->
            <TableCell class="py-3.5 text-zinc-700 text-xs font-medium">
              {{ item.total_applicants }} kandidat
            </TableCell>

            <!-- Dalam Proses -->
            <TableCell class="py-3.5 text-amber-700 text-xs font-medium">
              {{ item.in_process }} kandidat
            </TableCell>

            <!-- Hired -->
            <TableCell class="py-3.5 text-emerald-700 text-xs font-semibold">
              {{ item.hired }} orang
            </TableCell>

            <!-- Progress Bar -->
            <TableCell class="py-3.5">
              <div class="w-32 space-y-1">
                <div class="flex items-center justify-between text-[10px] font-medium text-zinc-600">
                  <span>{{ item.hired }}/{{ item.needed }}</span>
                  <span class="font-mono">{{ item.fulfillment_percentage || 0 }}%</span>
                </div>
                <div class="w-full bg-zinc-100 rounded-full h-1.5 overflow-hidden">
                  <div
                    class="bg-zinc-900 h-1.5 rounded-full transition-all duration-300"
                    :style="{ width: `${Math.min(item.fulfillment_percentage || 0, 100)}%` }"
                  ></div>
                </div>
              </div>
            </TableCell>

            <!-- Status Health -->
            <TableCell class="py-3.5 text-right">
              <Badge :variant="getHealthBadgeVariant(item)">
                {{ getHealthBadgeLabel(item) }}
              </Badge>
            </TableCell>
          </TableRow>

          <TableRow v-if="!filteredPositions.length">
            <TableCell colspan="8" class="py-12 text-center text-xs text-zinc-500">
              Tidak ada data progres rekrutmen yang sesuai kriteria.
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { useRekrutmenStore } from '../stores/rekrutmen';

// Shadcn UI Components
import { Button } from '../components/ui/button';
import { Badge } from '../components/ui/badge';
import { Card, CardHeader, CardContent } from '../components/ui/card';
import { Table, TableHeader, TableBody, TableHead, TableRow, TableCell } from '../components/ui/table';
import { Skeleton } from '../components/ui/skeleton';
import { Input } from '../components/ui/input';

// Icons
import {
  Search,
  FileSpreadsheet,
  RotateCw,
  Briefcase,
  Users,
  Clock,
  CheckCircle2,
  AlertCircle,
  MapPin,
} from 'lucide-vue-next';

const store = useRekrutmenStore();

const isLoading = ref(true);
const isRefreshing = ref(false);
const searchQuery = ref('');
const isExporting = ref(false);
const toastMessage = ref(null);
const toastType = ref('success');

onMounted(async () => {
  isLoading.value = true;
  try {
    await store.fetchProgressReport(true);
  } finally {
    isLoading.value = false;
  }
});

const refreshData = async () => {
  isRefreshing.value = true;
  try {
    await store.fetchProgressReport(true);
    toastType.value = 'success';
    toastMessage.value = 'Laporan progres berhasil disegarkan.';
    setTimeout(() => { toastMessage.value = null; }, 2500);
  } catch (e) {
    toastType.value = 'error';
    toastMessage.value = 'Gagal memperbarui laporan progres.';
  } finally {
    isRefreshing.value = false;
  }
};

const positions = computed(() => store.progressData?.positions || []);

const totalNeeded = computed(() => {
  return positions.value.reduce((acc, p) => acc + (Number(p.needed) || 0), 0);
});

const totalInProcess = computed(() => {
  return positions.value.reduce((acc, p) => acc + (Number(p.in_process) || 0), 0);
});

const totalHired = computed(() => {
  return positions.value.reduce((acc, p) => acc + (Number(p.hired) || 0), 0);
});

const filteredPositions = computed(() => {
  if (!searchQuery.value) return positions.value;
  const q = searchQuery.value.toLowerCase();
  return positions.value.filter(p =>
    p.position?.toLowerCase().includes(q) ||
    p.location?.toLowerCase().includes(q) ||
    p.company?.toLowerCase().includes(q)
  );
});

const getHealthBadgeVariant = (item) => {
  const status = item?.cycle_health_status || '';
  if (status === 'risk') return 'destructive';
  if (status === 'watch') return 'warning';
  return 'outline';
};

const getHealthBadgeLabel = (item) => {
  const status = item?.cycle_health_status || '';
  if (status === 'risk') return 'Kritis';
  if (status === 'watch') return 'Perhatian';
  return 'Normal';
};

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
    toastType.value = 'success';
    toastMessage.value = 'Laporan progres berhasil diekspor ke Excel.';
    setTimeout(() => { toastMessage.value = null; }, 3000);
  } catch (err) {
    console.error('Export failed', err);
    window.location.href = '/rekrutmen/api/progress-report/export';
  } finally {
    isExporting.value = false;
  }
};
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
