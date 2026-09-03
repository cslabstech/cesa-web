<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-xl font-bold text-slate-100 flex items-center gap-2">
        Pengaturan Master Rekrutmen
      </h1>
      <p class="text-xs text-slate-400 mt-0.5">
        Konfigurasi pipeline tahapan wawancara, divisi, dan hak persetujuan.
      </p>
    </div>

    <!-- Master Data Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Pipeline Stages List -->
      <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
        <h3 class="text-sm font-bold text-slate-200 pb-3 border-b border-slate-800 flex items-center justify-between">
          <span>Tahapan Seleksi Pipeline</span>
          <span class="text-xs text-slate-500">{{ masterData?.stages?.length || 0 }} Tahap</span>
        </h3>
        <div class="mt-4 space-y-2 max-h-80 overflow-y-auto custom-scrollbar">
          <div
            v-for="(stage, idx) in masterData?.stages"
            :key="stage.id"
            class="flex items-center justify-between p-3 bg-slate-800/40 border border-slate-800 rounded-xl"
          >
            <div class="flex items-center gap-3">
              <span class="text-xs font-mono text-slate-500 w-4">{{ idx + 1 }}.</span>
              <span class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: stage.color || '#6366f1' }"></span>
              <span class="text-xs font-bold text-slate-200">{{ stage.name }}</span>
            </div>
            <span class="text-[10px] text-slate-500 font-mono">Urutan #{{ stage.sort_order }}</span>
          </div>
        </div>
      </div>

      <!-- Divisions List -->
      <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
        <h3 class="text-sm font-bold text-slate-200 pb-3 border-b border-slate-800 flex items-center justify-between">
          <span>Divisi Terdaftar</span>
          <span class="text-xs text-slate-500">{{ masterData?.divisions?.length || 0 }} Divisi</span>
        </h3>
        <div class="mt-4 space-y-2 max-h-80 overflow-y-auto custom-scrollbar">
          <div
            v-for="div in masterData?.divisions"
            :key="div.id"
            class="flex items-center justify-between p-3 bg-slate-800/40 border border-slate-800 rounded-xl"
          >
            <div>
              <span class="text-xs font-bold text-slate-200">{{ div.name }}</span>
              <div class="text-[10px] text-slate-500 mt-0.5">{{ div.company_name || div.badan_usaha || div.company?.name || '-' }}</div>
            </div>
            <span class="text-[10px] px-2 py-0.5 rounded-md bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-semibold">
              Aktif
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useRekrutmenStore } from '../stores/rekrutmen';

const store = useRekrutmenStore();
const masterData = computed(() => store.configurations);

onMounted(() => {
  store.fetchConfigurations();
});
</script>
