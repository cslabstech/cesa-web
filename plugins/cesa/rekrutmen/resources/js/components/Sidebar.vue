<template>
  <aside class="w-64 bg-slate-900 border-r border-slate-800 flex flex-col h-full shrink-0 select-none">
    <!-- Brand Header -->
    <div class="h-16 flex items-center px-6 border-b border-slate-800/80 gap-3">
      <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/20 text-white font-bold text-lg">
        R
      </div>
      <div>
        <div class="font-bold text-slate-100 text-sm tracking-wide">REKRUTMEN</div>
        <div class="text-[11px] text-slate-400 font-medium">CESA Instant SPA</div>
      </div>
    </div>

    <!-- Navigation Menu -->
    <div class="flex-1 overflow-y-auto py-4 px-3 space-y-1 custom-scrollbar">
      <div class="px-3 pb-2 text-[10px] font-semibold tracking-wider text-slate-500 uppercase">
        Menu Utama
      </div>

      <router-link
        v-for="item in menuItems"
        :key="item.to"
        :to="item.to"
        class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-100 text-slate-400 hover:text-slate-200 hover:bg-slate-800/60"
        active-class="!bg-indigo-600 !text-white shadow-md shadow-indigo-600/25 !font-semibold"
        :exact="item.exact"
      >
        <component
          :is="item.icon"
          class="w-5 h-5 transition-colors text-slate-400 group-hover:text-slate-200 group-[.router-link-active]:text-white"
        />
        <span class="flex-1">{{ item.label }}</span>
        <span
          v-if="item.badge"
          class="text-[10px] px-2 py-0.5 rounded-full font-bold bg-indigo-500/30 text-indigo-200"
        >
          {{ item.badge }}
        </span>
      </router-link>
    </div>

    <!-- Quick Back to Filament Admin Panel -->
    <div class="p-3 border-t border-slate-800/80">
      <a
        href="/admin"
        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-medium text-slate-400 hover:text-slate-100 hover:bg-slate-800/60 transition-colors"
      >
        <ArrowLeft class="w-4 h-4 text-slate-400" />
        <span>Kembali ke Panel Admin</span>
      </a>
    </div>
  </aside>
</template>

<script setup>
import { 
  LayoutDashboard, 
  Users, 
  Briefcase, 
  FileSpreadsheet, 
  Settings, 
  ArrowLeft 
} from 'lucide-vue-next';

const menuItems = [
  { label: 'Dashboard', to: '/rekrutmen', icon: LayoutDashboard, exact: true },
  { label: 'Permintaan (FPTK)', to: '/rekrutmen/requests', icon: FileSpreadsheet, exact: false },
  { label: 'Lowongan Kerja', to: '/rekrutmen/postings', icon: Briefcase, exact: false },
  { label: 'Pelamar & Kanban', to: '/rekrutmen/applications', icon: Users, exact: false },
  { label: 'Pengaturan', to: '/rekrutmen/settings', icon: Settings, exact: false },
];
</script>
