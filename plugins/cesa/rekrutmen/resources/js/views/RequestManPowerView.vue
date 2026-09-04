<template>
  <div class="space-y-6 pb-12">
    <!-- Top Header Title & Action Button -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <div class="flex items-center gap-2.5">
          <h1 class="text-xl font-semibold text-zinc-900 tracking-tight">
            Permintaan Tenaga Kerja (FPTK)
          </h1>
          <Badge variant="outline" class="font-mono text-[11px] text-zinc-600 bg-zinc-50 border-zinc-200">
            {{ requests.length }} Pengajuan
          </Badge>
        </div>
        <p class="text-xs text-zinc-500 mt-1">
          Kelola dan pantau permohonan penambahan personil (Manpower Request) dari seluruh cabang dan divisi
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

        <a href="/man-power" target="_blank" class="inline-flex">
          <Button size="sm" variant="default" class="bg-zinc-900 hover:bg-zinc-800 text-white text-xs h-8 gap-1.5">
            <Plus class="w-3.5 h-3.5" />
            <span>Buat FPTK Baru</span>
          </Button>
        </a>
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
          <span class="text-xs font-medium text-zinc-500">Total FPTK</span>
          <FileSpreadsheet class="w-4 h-4 text-zinc-400" />
        </CardHeader>
        <CardContent class="p-4 pt-0">
          <div class="text-2xl font-bold tracking-tight text-zinc-900">{{ requests.length }}</div>
          <p class="text-[11px] text-zinc-500 mt-0.5">
            Akumulasi permohonan personil
          </p>
        </CardContent>
      </Card>

      <Card class="hover:border-zinc-300">
        <CardHeader class="p-4 pb-2 flex flex-row items-center justify-between space-y-0">
          <span class="text-xs font-medium text-zinc-500">Disetujui</span>
          <CheckCircle2 class="w-4 h-4 text-emerald-600" />
        </CardHeader>
        <CardContent class="p-4 pt-0">
          <div class="text-2xl font-bold tracking-tight text-emerald-700">{{ approvedCount }}</div>
          <p class="text-[11px] text-zinc-500 mt-0.5">
            Siap/sedang diproses rekrutmen
          </p>
        </CardContent>
      </Card>

      <Card class="hover:border-zinc-300">
        <CardHeader class="p-4 pb-2 flex flex-row items-center justify-between space-y-0">
          <span class="text-xs font-medium text-zinc-500">Menunggu Approval</span>
          <Clock class="w-4 h-4 text-amber-500" />
        </CardHeader>
        <CardContent class="p-4 pt-0">
          <div class="text-2xl font-bold tracking-tight text-amber-700">{{ pendingCount }}</div>
          <p class="text-[11px] text-zinc-500 mt-0.5">
            Perlu persetujuan manajemen
          </p>
        </CardContent>
      </Card>

      <Card class="hover:border-zinc-300">
        <CardHeader class="p-4 pb-2 flex flex-row items-center justify-between space-y-0">
          <span class="text-xs font-medium text-zinc-500">Kebutuhan Personil</span>
          <Users class="w-4 h-4 text-zinc-400" />
        </CardHeader>
        <CardContent class="p-4 pt-0">
          <div class="text-2xl font-bold tracking-tight text-[#0c2340]">{{ totalNeededPersonnel }}</div>
          <p class="text-[11px] text-zinc-500 mt-0.5">
            Total orang yang diajukan
          </p>
        </CardContent>
      </Card>
    </div>

    <!-- Filters & Toolbar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3 bg-white rounded-xl border border-zinc-200 shadow-2xs">
      <!-- Status Filter Tabs -->
      <div class="flex items-center gap-1 overflow-x-auto no-scrollbar pb-1 sm:pb-0">
        <button
          type="button"
          @click="statusFilter = 'all'"
          :class="[
            'px-3 py-1.5 rounded-md text-xs font-medium transition-all cursor-pointer shrink-0 select-none',
            statusFilter === 'all'
              ? 'bg-zinc-900 text-zinc-50 shadow-2xs font-semibold'
              : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100'
          ]"
        >
          Semua FPTK <span class="ml-1 opacity-70">({{ requests.length }})</span>
        </button>

        <button
          type="button"
          @click="statusFilter = 'approved'"
          :class="[
            'px-3 py-1.5 rounded-md text-xs font-medium transition-all cursor-pointer shrink-0 select-none',
            statusFilter === 'approved'
              ? 'bg-emerald-600 text-white shadow-2xs font-semibold'
              : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100'
          ]"
        >
          Disetujui <span class="ml-1 opacity-70">({{ approvedCount }})</span>
        </button>

        <button
          type="button"
          @click="statusFilter = 'pending'"
          :class="[
            'px-3 py-1.5 rounded-md text-xs font-medium transition-all cursor-pointer shrink-0 select-none',
            statusFilter === 'pending'
              ? 'bg-amber-600 text-white shadow-2xs font-semibold'
              : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100'
          ]"
        >
          Menunggu <span class="ml-1 opacity-70">({{ pendingCount }})</span>
        </button>
      </div>

      <!-- Search Input -->
      <div class="relative w-full sm:w-80">
        <Search class="w-3.5 h-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-zinc-400" />
        <Input
          v-model="searchQuery"
          type="text"
          placeholder="Cari nomor FPTK, posisi, divisi..."
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
        <div class="space-y-1.5 w-1/4">
          <Skeleton class="h-4 w-32" />
          <Skeleton class="h-3 w-24" />
        </div>
        <Skeleton class="h-4 w-16" />
        <Skeleton class="h-4 w-28" />
        <Skeleton class="h-5 w-20 rounded-full" />
        <Skeleton class="h-7 w-16 rounded-md" />
      </div>
    </div>

    <!-- DATA TABLE CARD: Shadcn Table Component -->
    <div v-else class="bg-white rounded-xl border border-zinc-200 shadow-2xs overflow-hidden">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead class="w-[28%]">No. FPTK & Posisi</TableHead>
            <TableHead class="w-[24%]">Divisi & Badan Usaha</TableHead>
            <TableHead class="w-[10%]">Kebutuhan</TableHead>
            <TableHead class="w-[16%]">Progres Pemenuhan</TableHead>
            <TableHead class="w-[12%]">Tgl Pengajuan</TableHead>
            <TableHead class="w-[10%]">Status</TableHead>
            <TableHead class="w-[8%] text-right">Aksi</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-for="item in filteredRequests" :key="item.id" class="group">
            <TableCell class="py-3.5">
              <div
                @click="openRequestDetail(item)"
                class="font-semibold text-xs text-zinc-900 hover:text-blue-900 cursor-pointer transition-colors"
              >
                {{ item.posisi_dibutuhkan || item.position_name || item.position_title || 'Staff' }}
              </div>
              <div class="text-[11px] text-zinc-400 font-mono mt-0.5">
                #{{ item.id || item.request_number }}
              </div>
            </TableCell>

            <TableCell class="py-3.5">
              <div class="font-medium text-zinc-800 text-xs truncate max-w-[220px]">
                {{ item.division_name || item.department || item.division?.name || '-' }}
              </div>
              <div class="text-[11px] text-zinc-500 truncate max-w-[220px] mt-0.5">
                {{ item.business_entity_name || item.company_name || '-' }}
              </div>
              <div class="text-[11px] text-zinc-400 flex items-center gap-1 mt-0.5">
                <MapPin class="w-3 h-3 text-zinc-400 shrink-0" />
                <span class="truncate">{{ item.lokasi_penempatan || item.branch || item.location || '-' }}</span>
              </div>
            </TableCell>

            <TableCell class="py-3.5 font-semibold text-zinc-900 text-xs">
              {{ item.jumlah_karyawan_dibutuhkan || item.quantity || 1 }} Orang
            </TableCell>

            <TableCell class="py-3.5">
              <div class="w-28 space-y-1">
                <div class="flex items-center justify-between text-[10px] font-medium text-zinc-600">
                  <span>{{ item.fulfilled_count || 0 }}/{{ item.jumlah_karyawan_dibutuhkan || item.quantity || 1 }}</span>
                  <span class="font-mono">{{ Math.min(100, Math.round(((item.fulfilled_count || 0) / (item.jumlah_karyawan_dibutuhkan || item.quantity || 1)) * 100)) }}%</span>
                </div>
                <div class="w-full bg-zinc-100 rounded-full h-1.5 overflow-hidden">
                  <div
                    class="bg-zinc-900 h-1.5 rounded-full transition-all duration-300"
                    :style="{ width: `${Math.min(100, Math.round(((item.fulfilled_count || 0) / (item.jumlah_karyawan_dibutuhkan || item.quantity || 1)) * 100))}%` }"
                  ></div>
                </div>
              </div>
            </TableCell>

            <TableCell class="py-3.5 text-zinc-600 text-xs whitespace-nowrap">
              {{ item.tanggal_pengajuan || item.submission_date || item.created_at || '-' }}
            </TableCell>

            <TableCell class="py-3.5">
              <Badge :variant="getBadgeVariant(item.status || item.approval_status)" class="text-[10px] px-2 py-0.5">
                {{ item.status || item.approval_status || 'Pending' }}
              </Badge>
            </TableCell>

            <TableCell class="py-3.5 text-right whitespace-nowrap">
              <Button
                variant="outline"
                size="xs"
                @click="openRequestDetail(item)"
                class="h-7 text-zinc-700 hover:text-zinc-900"
              >
                Detail
              </Button>
            </TableCell>
          </TableRow>

          <TableRow v-if="!filteredRequests.length">
            <TableCell colspan="7" class="py-12 text-center text-xs text-zinc-500">
              Tidak ada data permohonan tenaga kerja yang sesuai kriteria.
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>

    <!-- SLIDE-OVER SHEET: FPTK Detail Drawer -->
    <Sheet :open="!!selectedRequest" @update:open="(val) => { if (!val) selectedRequest = null; }">
      <SheetContent class="sm:max-w-lg">
        <SheetHeader>
          <div class="flex items-center gap-2">
            <SheetTitle>Detail Permintaan FPTK</SheetTitle>
            <Badge v-if="selectedRequest" :variant="getBadgeVariant(selectedRequest.status || selectedRequest.approval_status)" class="text-[10px]">
              {{ selectedRequest.status || selectedRequest.approval_status || 'Pending' }}
            </Badge>
          </div>
          <SheetDescription>
            Nomor Pengajuan #{{ selectedRequest?.id || selectedRequest?.request_number }}
          </SheetDescription>
        </SheetHeader>

        <div v-if="selectedRequest" class="p-6 space-y-4 overflow-y-auto flex-1 text-xs text-zinc-900">
          <div class="border border-zinc-200 rounded-lg p-4 space-y-3 bg-zinc-50/50">
            <div class="flex justify-between items-start pb-2 border-b border-zinc-200/70">
              <span class="text-zinc-500 font-medium">Posisi Dibutuhkan:</span>
              <span class="font-semibold text-zinc-900 text-right">{{ selectedRequest.posisi_dibutuhkan || selectedRequest.position_name || selectedRequest.position_title }}</span>
            </div>

            <div class="flex justify-between items-start pb-2 border-b border-zinc-200/70">
              <span class="text-zinc-500 font-medium">Divisi / Departemen:</span>
              <span class="font-medium text-zinc-800 text-right">{{ selectedRequest.division_name || selectedRequest.department || selectedRequest.division?.name || '-' }}</span>
            </div>

            <div class="flex justify-between items-start pb-2 border-b border-zinc-200/70">
              <span class="text-zinc-500 font-medium">Badan Usaha:</span>
              <span class="font-medium text-zinc-800 text-right">{{ selectedRequest.business_entity_name || selectedRequest.company_name || '-' }}</span>
            </div>

            <div class="flex justify-between items-start pb-2 border-b border-zinc-200/70">
              <span class="text-zinc-500 font-medium">Lokasi Penempatan:</span>
              <span class="font-medium text-zinc-800 text-right">{{ selectedRequest.lokasi_penempatan || selectedRequest.branch || selectedRequest.location || '-' }}</span>
            </div>

            <div class="flex justify-between items-start pb-2 border-b border-zinc-200/70">
              <span class="text-zinc-500 font-medium">Jumlah Kebutuhan:</span>
              <span class="font-bold text-zinc-900 text-right">{{ selectedRequest.jumlah_karyawan_dibutuhkan || selectedRequest.quantity || 1 }} Orang</span>
            </div>

            <div class="flex justify-between items-start pb-2 border-b border-zinc-200/70">
              <span class="text-zinc-500 font-medium">Tanggal Pengajuan:</span>
              <span class="font-medium text-zinc-800 text-right">{{ selectedRequest.tanggal_pengajuan || selectedRequest.submission_date || selectedRequest.created_at || '-' }}</span>
            </div>

            <div v-if="selectedRequest.nama_pengaju" class="flex justify-between items-start pb-2 border-b border-zinc-200/70">
              <span class="text-zinc-500 font-medium">Diajukan Oleh:</span>
              <span class="font-medium text-zinc-800 text-right">{{ selectedRequest.nama_pengaju }} ({{ selectedRequest.posisi_pengaju || '-' }})</span>
            </div>

            <div>
              <span class="text-zinc-500 font-medium block mb-1.5">Alasan / Kualifikasi Kebutuhan:</span>
              <p class="text-zinc-700 leading-relaxed whitespace-pre-line bg-white p-3 rounded-md border border-zinc-200 text-xs">
                {{ selectedRequest.requirements_kualifikasi || selectedRequest.keterangan || selectedRequest.reason || selectedRequest.justification || 'Kebutuhan operasional penambahan personil' }}
              </p>
            </div>
          </div>
        </div>

        <SheetFooter>
          <Button variant="outline" size="sm" @click="selectedRequest = null">
            Tutup
          </Button>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRekrutmenStore } from '../stores/rekrutmen';

// Shadcn UI Components
import { Button } from '../components/ui/button';
import { Badge } from '../components/ui/badge';
import { Card, CardHeader, CardContent } from '../components/ui/card';
import { Table, TableHeader, TableBody, TableHead, TableRow, TableCell } from '../components/ui/table';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetDescription, SheetFooter } from '../components/ui/sheet';
import { Skeleton } from '../components/ui/skeleton';
import { Input } from '../components/ui/input';

// Icons
import {
  Search,
  Plus,
  CheckCircle2,
  AlertCircle,
  Clock,
  Users,
  FileSpreadsheet,
  RotateCw,
  MapPin,
} from 'lucide-vue-next';

const store = useRekrutmenStore();

const isLoading = ref(true);
const isRefreshing = ref(false);
const statusFilter = ref('all');
const searchQuery = ref('');
const toastMessage = ref(null);
const toastType = ref('success');
const selectedRequest = ref(null);

onMounted(async () => {
  isLoading.value = true;
  try {
    await store.fetchRequests('', false);
  } finally {
    isLoading.value = false;
  }
});

const refreshData = async () => {
  isRefreshing.value = true;
  try {
    await store.fetchRequests('', true);
    toastType.value = 'success';
    toastMessage.value = 'Data permintaan FPTK berhasil disegarkan.';
    setTimeout(() => { toastMessage.value = null; }, 2500);
  } catch (e) {
    toastType.value = 'error';
    toastMessage.value = 'Gagal memperbarui data FPTK.';
  } finally {
    isRefreshing.value = false;
  }
};

const requests = computed(() => store.requests || []);

const approvedCount = computed(() => requests.value.filter(r => {
  const s = String(r.status || r.approval_status || r.raw_status || '').toLowerCase();
  return s.includes('approv') || s.includes('setuju');
}).length);

const pendingCount = computed(() => requests.value.filter(r => {
  const s = String(r.status || r.approval_status || r.raw_status || '').toLowerCase();
  return s.includes('pend') || s.includes('tunggu');
}).length);

const totalNeededPersonnel = computed(() => {
  return requests.value.reduce((acc, r) => acc + (Number(r.jumlah_karyawan_dibutuhkan || r.quantity || 1) || 1), 0);
});

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
    const company = String(r.business_entity_name || r.company_name || '').toLowerCase();
    const loc = String(r.lokasi_penempatan || r.branch || r.location || '').toLowerCase();
    const name = String(r.nama_pengaju || '').toLowerCase();
    return pos.includes(q) || num.includes(q) || div.includes(q) || company.includes(q) || loc.includes(q) || name.includes(q);
  });
});

const getBadgeVariant = (status) => {
  const s = String(status || '').toLowerCase();
  if (s.includes('approv') || s.includes('setuju')) return 'success';
  if (s.includes('reject') || s.includes('tolak')) return 'destructive';
  return 'warning';
};

const openRequestDetail = (item) => {
  selectedRequest.value = item;
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
