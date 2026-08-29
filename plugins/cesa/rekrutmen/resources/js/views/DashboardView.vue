<template>
  <div class="space-y-6">
    <!-- Header Hero Banner -->
    <div class="bg-gradient-to-r from-indigo-900/60 via-purple-900/40 to-slate-900 border border-indigo-500/20 rounded-2xl p-6 relative overflow-hidden flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <h1 class="text-xl font-extrabold text-white tracking-tight flex items-center gap-2">
          Dashboard Rekrutmen 🚀
        </h1>
        <p class="text-xs text-slate-300 mt-1 max-w-xl">
          Pantau seluruh aktivitas rekrutmen karyawan, lowongan aktif, permintaan man power, dan pipeline pelamar secara instan & real-time.
        </p>
      </div>
      <div class="flex items-center gap-3">
        <router-link
          to="/rekrutmen/applications"
          class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold px-4 py-2.5 rounded-xl shadow-lg shadow-indigo-600/30 transition-all cursor-pointer"
        >
          <Users class="w-4 h-4" />
          <span>Buka Kanban Board</span>
        </router-link>
      </div>
    </div>

    <!-- Stat Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <StatCard
        title="Lowongan Aktif"
        :value="data?.stats?.active_postings ?? 0"
        :subtext="`dari total ${data?.stats?.total_postings ?? 0} loker`"
        :icon="Briefcase"
        iconBg="bg-indigo-500/10"
        iconColor="text-indigo-400"
      />
      <StatCard
        title="Total Pelamar Masuk"
        :value="data?.stats?.total_applications ?? 0"
        subtext="seluruh posisi"
        :icon="Users"
        iconBg="bg-emerald-500/10"
        iconColor="text-emerald-400"
      />
      <StatCard
        title="Permintaan Man Power"
        :value="data?.stats?.total_requests ?? 0"
        subtext="FPTK terdaftar"
        :icon="FileSpreadsheet"
        iconBg="bg-purple-500/10"
        iconColor="text-purple-400"
      />
      <StatCard
        title="FPTK Menunggu Review"
        :value="data?.stats?.pending_requests ?? 0"
        subtext="butuh tindakan"
        :icon="Clock"
        iconBg="bg-amber-500/10"
        iconColor="text-amber-400"
      />
    </div>

    <!-- Two Columns: Pipeline Stages Distribution & Recent Applications -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Pipeline Distribution Card -->
      <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 flex flex-col">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
          <h2 class="text-sm font-bold text-slate-200">Distribusi Tahapan Pipeline</h2>
          <router-link to="/rekrutmen/applications" class="text-xs text-indigo-400 hover:text-indigo-300 font-medium">
            Lihat Semua
          </router-link>
        </div>

        <div class="mt-4 space-y-3 flex-1 overflow-y-auto max-h-80 custom-scrollbar pr-1">
          <div
            v-for="stage in data?.stages_distribution"
            :key="stage.id"
            class="flex items-center justify-between p-3 rounded-xl bg-slate-800/40 border border-slate-800 hover:border-slate-700 transition-colors"
          >
            <div class="flex items-center gap-3">
              <span
                class="w-3 h-3 rounded-full"
                :style="{ backgroundColor: stage.color || '#6366f1' }"
              ></span>
              <span class="text-xs font-semibold text-slate-200">{{ stage.name }}</span>
            </div>
            <span class="text-xs font-bold px-2 py-0.5 rounded-md bg-slate-800 text-slate-300 border border-slate-700">
              {{ stage.job_applications_count }} Pelamar
            </span>
          </div>
          <div v-if="!data?.stages_distribution?.length" class="text-xs text-slate-500 text-center py-6">
            Belum ada data stage pipeline.
          </div>
        </div>
      </div>

      <!-- Recent Applications List -->
      <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl p-5 flex flex-col">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
          <h2 class="text-sm font-bold text-slate-200">Pelamar Terbaru</h2>
          <router-link to="/rekrutmen/applications" class="text-xs text-indigo-400 hover:text-indigo-300 font-medium">
            Kelola Pelamar &rarr;
          </router-link>
        </div>

        <div class="mt-3 divide-y divide-slate-800/60 overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead>
              <tr class="text-slate-400 border-b border-slate-800">
                <th class="py-2.5 px-3 font-semibold">Nama Pelamar</th>
                <th class="py-2.5 px-3 font-semibold">Posisi Dilamar</th>
                <th class="py-2.5 px-3 font-semibold">Tahapan</th>
                <th class="py-2.5 px-3 font-semibold">Tanggal Masuk</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/40">
              <tr
                v-for="app in data?.recent_applications"
                :key="app.id"
                class="hover:bg-slate-800/30 transition-colors group"
              >
                <td class="py-3 px-3">
                  <div class="font-bold text-slate-200">{{ app.name }}</div>
                  <div class="text-[11px] text-slate-500">{{ app.email }}</div>
                </td>
                <td class="py-3 px-3 text-slate-300">
                  {{ app.job_posting?.title || '-' }}
                </td>
                <td class="py-3 px-3">
                  <span
                    class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[11px] font-semibold border"
                    :style="{
                      backgroundColor: (app.stage?.color || '#6366f1') + '15',
                      borderColor: (app.stage?.color || '#6366f1') + '40',
                      color: app.stage?.color || '#818cf8'
                    }"
                  >
                    <span class="w-1.5 h-1.5 rounded-full" :style="{ backgroundColor: app.stage?.color || '#818cf8' }"></span>
                    {{ app.stage?.name || 'Review' }}
                  </span>
                </td>
                <td class="py-3 px-3 text-slate-400 text-[11px]">
                  {{ formatDate(app.created_at) }}
                </td>
              </tr>
              <tr v-if="!data?.recent_applications?.length">
                <td colspan="4" class="py-8 text-center text-xs text-slate-500">
                  Belum ada pelamar baru yang masuk.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { Briefcase, Users, FileSpreadsheet, Clock } from 'lucide-vue-next';
import StatCard from '../components/StatCard.vue';
import { useRekrutmenStore } from '../stores/rekrutmen';

const store = useRekrutmenStore();
const data = computed(() => store.dashboardData);

const formatDate = (dateStr) => {
  if (!dateStr) return '-';
  const d = new Date(dateStr);
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};

onMounted(() => {
  store.fetchDashboard();
});
</script>
