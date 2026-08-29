<template>
  <div class="space-y-6">
    <!-- Breadcrumb & Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <div class="flex items-center gap-1.5 text-xs text-gray-500 font-medium mb-1">
          <span>Configurations</span>
          <ChevronRight class="w-3.5 h-3.5 text-gray-400" />
          <span class="text-gray-700">Master Data</span>
        </div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">
          Configurations
        </h1>
      </div>

      <!-- Sub-tabs switcher -->
      <div class="flex items-center bg-gray-100/80 border border-gray-200 rounded-lg p-0.5">
        <button
          @click="activeTab = 'divisions'"
          :class="[
            'px-3.5 py-1.5 rounded-md text-xs font-semibold transition-all cursor-pointer',
            activeTab === 'divisions'
              ? 'bg-white text-blue-600 shadow-xs'
              : 'text-gray-600 hover:text-gray-900'
          ]"
        >
          Divisi ({{ divisions.length }})
        </button>
        <button
          @click="activeTab = 'stages'"
          :class="[
            'px-3.5 py-1.5 rounded-md text-xs font-semibold transition-all cursor-pointer',
            activeTab === 'stages'
              ? 'bg-white text-blue-600 shadow-xs'
              : 'text-gray-600 hover:text-gray-900'
          ]"
        >
          Pipeline Stages ({{ stages.length }})
        </button>
        <button
          @click="activeTab = 'approvers'"
          :class="[
            'px-3.5 py-1.5 rounded-md text-xs font-semibold transition-all cursor-pointer',
            activeTab === 'approvers'
              ? 'bg-white text-blue-600 shadow-xs'
              : 'text-gray-600 hover:text-gray-900'
          ]"
        >
          Approvers ({{ approvers.length }})
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="store.loading.configurations" class="bg-white rounded-xl border border-gray-200/90 shadow-xs">
      <LoadingState
        title="Sedang memuat data..."
        subtitle="Menyiapkan master konfigurasi rekrutmen..."
      />
    </div>

    <template v-else>
      <!-- DIVISIONS TABLE -->
      <div
        v-if="activeTab === 'divisions'"
        class="bg-white rounded-xl border border-gray-200/90 shadow-xs overflow-hidden"
      >
      <div class="p-4 border-b border-gray-100 flex items-center justify-between">
        <div class="text-xs font-bold text-gray-700 uppercase tracking-wider">
          Daftar Divisi Perusahaan
        </div>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead>
            <tr class="bg-gray-50/50 text-gray-900 border-b border-gray-200/80">
              <th class="py-3 px-4 font-semibold w-16">ID</th>
              <th class="py-3 px-4 font-semibold">Nama Divisi</th>
              <th class="py-3 px-4 font-semibold">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr
              v-for="div in divisions"
              :key="div.id"
              class="hover:bg-blue-50/20 transition-colors"
            >
              <td class="py-3.5 px-4 font-mono text-gray-400">#{{ div.id }}</td>
              <td class="py-3.5 px-4 font-semibold text-gray-900">{{ div.name }}</td>
              <td class="py-3.5 px-4">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                  {{ div.is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- PIPELINE STAGES TABLE -->
    <div
      v-else-if="activeTab === 'stages'"
      class="bg-white rounded-xl border border-gray-200/90 shadow-xs overflow-hidden"
    >
      <div class="p-4 border-b border-gray-100 flex items-center justify-between">
        <div class="text-xs font-bold text-gray-700 uppercase tracking-wider">
          Tahapan Pipeline Seleksi Pelamar
        </div>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead>
            <tr class="bg-gray-50/50 text-gray-900 border-b border-gray-200/80">
              <th class="py-3 px-4 font-semibold w-24">Urutan</th>
              <th class="py-3 px-4 font-semibold">Nama Tahapan Seleksi</th>
              <th class="py-3 px-4 font-semibold">Warna Indikator</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr
              v-for="(stage, idx) in stages"
              :key="stage.id"
              class="hover:bg-blue-50/20 transition-colors"
            >
              <td class="py-3.5 px-4 font-mono font-bold text-blue-600">Urutan #{{ stage.sort_order || (idx + 1) }}</td>
              <td class="py-3.5 px-4 font-bold text-gray-900 flex items-center gap-2">
                <span class="w-3 h-3 rounded-full" :style="{ backgroundColor: stage.color || '#3b82f6' }"></span>
                <span>{{ stage.name }}</span>
              </td>
              <td class="py-3.5 px-4 font-mono text-gray-500">{{ stage.color || '#3b82f6' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- APPROVERS TABLE -->
    <div
      v-else
      class="bg-white rounded-xl border border-gray-200/90 shadow-xs overflow-hidden"
    >
      <div class="p-4 border-b border-gray-100 flex items-center justify-between">
        <div class="text-xs font-bold text-gray-700 uppercase tracking-wider">
          Daftar Penyetuju (Approvers)
        </div>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead>
            <tr class="bg-gray-50/50 text-gray-900 border-b border-gray-200/80">
              <th class="py-3 px-4 font-semibold">Nama Approver</th>
              <th class="py-3 px-4 font-semibold">Divisi</th>
              <th class="py-3 px-4 font-semibold">Kontak</th>
              <th class="py-3 px-4 font-semibold">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr
              v-for="appr in approvers"
              :key="appr.id"
              class="hover:bg-blue-50/20 transition-colors"
            >
              <td class="py-3.5 px-4 font-bold text-gray-900">{{ appr.name }}</td>
              <td class="py-3.5 px-4 text-gray-600">{{ appr.division?.name || '-' }}</td>
              <td class="py-3.5 px-4 text-gray-500">{{ appr.email || appr.phone || '-' }}</td>
              <td class="py-3.5 px-4">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                  Aktif
                </span>
              </td>
            </tr>
            <tr v-if="!approvers.length">
              <td colspan="4" class="py-12 text-center text-xs text-gray-500">
                Belum ada data approver yang terdaftar.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { ChevronRight } from 'lucide-vue-next';
import { useRekrutmenStore } from '../stores/rekrutmen';
import LoadingState from '../components/LoadingState.vue';

const store = useRekrutmenStore();
const activeTab = ref('divisions');

const divisions = computed(() => store.configurationsData?.divisions || []);
const stages = computed(() => store.configurationsData?.stages || []);
const approvers = computed(() => store.configurationsData?.approvers || []);

onMounted(() => {
  store.fetchConfigurations(true);
});
</script>
