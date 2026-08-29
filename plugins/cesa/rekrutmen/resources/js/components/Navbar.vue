<template>
  <header class="h-16 bg-white border-b border-slate-200/90 px-6 flex items-center justify-between sticky top-0 z-30 shadow-2xs">
    <!-- Left: App Launcher + Official CESA Brand Logo + Navigation Tabs -->
    <div class="flex items-center gap-5">
      <!-- 3x3 App Launcher Icon (links back to /admin) -->
      <a
        href="/admin"
        title="Kembali ke Menu Utama Admin"
        class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg hover:bg-slate-100 transition-colors"
      >
        <LayoutGrid class="w-5 h-5 text-slate-500" />
      </a>

      <!-- Official CESA Brand Logo + Name (Matching Screenshot 2) -->
      <a href="/admin/request-man-powers" class="flex items-center gap-2.5 pr-2">
        <img
          :src="'/images/logo.png'"
          alt="Complete Selular"
          class="h-7 w-auto object-contain shrink-0"
        />
        <span class="text-base font-bold text-slate-900 tracking-tight">Rekrutmen</span>
      </a>

      <!-- Horizontal Tabs Navigation (Matching Enterprise System) -->
      <nav class="hidden lg:flex items-center gap-1 ml-2">
        <router-link
          v-for="tab in tabs"
          :key="tab.to"
          :to="tab.to"
          class="px-3.5 py-1.5 rounded-lg text-xs font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 transition-colors"
          active-class="!font-bold !text-blue-600 !bg-blue-50/80"
        >
          {{ tab.label }}
        </router-link>
      </nav>
    </div>

    <!-- Right Controls: Search, Lang, Notifications, User Profile -->
    <div class="flex items-center gap-3 relative">
      <!-- Global Live Search Input (Auto-searches candidates) -->
      <div class="relative hidden sm:block w-52 xl:w-64">
        <Search class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
        <input
          v-model="navSearch"
          @input="handleGlobalSearch"
          @keydown.enter="handleSearchEnter"
          type="text"
          placeholder="Cari kandidat / lowongan..."
          class="w-full bg-white border border-slate-200 rounded-lg pl-8 pr-7 py-1.5 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
        />
        <button
          v-if="navSearch"
          @click="clearSearch"
          class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs font-bold p-0.5"
        >
          &times;
        </button>
      </div>

      <!-- Language / Flag Dropdown -->
      <div class="relative">
        <button
          @click.stop="toggleLangMenu"
          class="w-7 h-5 rounded overflow-hidden shadow-2xs border border-slate-200 cursor-pointer shrink-0 block hover:opacity-80 transition-opacity"
          title="Ganti Bahasa"
        >
          <img
            :src="currentLang === 'id' ? 'https://flagcdn.com/w40/id.png' : 'https://flagcdn.com/w40/gb.png'"
            :alt="currentLang === 'id' ? 'Indonesia' : 'English'"
            class="w-full h-full object-cover"
          />
        </button>

        <div
          v-if="showLangMenu"
          class="absolute right-0 mt-2 w-36 bg-white border border-slate-200 rounded-xl shadow-lg py-1.5 z-50 text-xs"
        >
          <button
            @click="setLang('id')"
            class="w-full px-3 py-1.5 text-left flex items-center gap-2 hover:bg-slate-50 cursor-pointer"
            :class="currentLang === 'id' ? 'font-bold text-blue-600' : 'text-slate-700'"
          >
            <img src="https://flagcdn.com/w40/id.png" class="w-4 h-3 rounded object-cover" />
            <span>Indonesia</span>
          </button>
          <button
            @click="setLang('en')"
            class="w-full px-3 py-1.5 text-left flex items-center gap-2 hover:bg-slate-50 cursor-pointer"
            :class="currentLang === 'en' ? 'font-bold text-blue-600' : 'text-slate-700'"
          >
            <img src="https://flagcdn.com/w40/gb.png" class="w-4 h-3 rounded object-cover" />
            <span>English</span>
          </button>
        </div>
      </div>

      <!-- Notification Bell with Dropdown -->
      <div class="relative">
        <button
          @click.stop="toggleNotifications"
          class="p-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition-colors relative cursor-pointer"
          title="Notifikasi"
        >
          <Bell class="w-4 h-4" />
          <span class="w-1.5 h-1.5 rounded-full bg-blue-600 absolute top-1.5 right-1.5"></span>
        </button>

        <div
          v-if="showNotifications"
          class="absolute right-0 mt-2 w-80 bg-white border border-slate-200 rounded-2xl shadow-xl p-4 z-50 text-xs space-y-3"
        >
          <div class="flex items-center justify-between border-b border-slate-100 pb-2">
            <span class="font-bold text-slate-900 text-xs">Notifikasi Rekrutmen</span>
            <span class="text-[10px] text-blue-600 font-semibold cursor-pointer">Tandai sudah dibaca</span>
          </div>
          <div class="space-y-2">
            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100 space-y-1">
              <div class="font-semibold text-slate-800 text-[11px]">Lamaran Baru Masuk</div>
              <p class="text-slate-500 text-[10px] leading-relaxed">Kandidat baru telah melamar posisi Web App Developer Cirebon.</p>
              <span class="text-[9px] text-slate-400">10 menit yang lalu</span>
            </div>
            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100 space-y-1">
              <div class="font-semibold text-slate-800 text-[11px]">AI Screening Selesai</div>
              <p class="text-slate-500 text-[10px] leading-relaxed">Analisis kualifikasi seluruh pelamar telah diperbarui.</p>
              <span class="text-[9px] text-slate-400">30 menit yang lalu</span>
            </div>
          </div>
        </div>
      </div>

      <!-- User Profile Avatar with Interactive Dropdown (Logout) -->
      <div class="relative">
        <button
          @click.stop="toggleUserMenu"
          class="w-8 h-8 rounded-full bg-slate-950 hover:bg-slate-800 text-white flex items-center justify-center font-bold text-xs shadow-2xs cursor-pointer ml-1 transition-colors"
          title="Menu Pengguna"
        >
          {{ userInitial }}
        </button>

        <!-- User Dropdown Menu -->
        <div
          v-if="showUserMenu"
          class="absolute right-0 mt-2 w-60 bg-white border border-slate-200 rounded-2xl shadow-xl p-3 z-50 text-xs space-y-3"
        >
          <!-- User Info Banner -->
          <div class="flex items-center gap-3 pb-3 border-b border-slate-100 px-1">
            <div class="w-10 h-10 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold text-sm shadow-xs">
              {{ userInitial }}
            </div>
            <div class="min-w-0 flex-1">
              <div class="font-bold text-slate-900 truncate text-xs">{{ user.name || 'Administrator' }}</div>
              <div class="text-[10px] text-slate-400 truncate">{{ user.email || 'admin@completeselular.com' }}</div>
              <span class="inline-block mt-1 px-1.5 py-0.2 rounded text-[9px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                HR Recruiter
              </span>
            </div>
          </div>

          <!-- Navigation Links -->
          <div class="space-y-1 text-slate-700">
            <a
              href="/admin"
              class="flex items-center gap-2.5 px-2.5 py-2 rounded-xl hover:bg-slate-50 text-slate-700 hover:text-slate-900 transition-colors font-medium text-xs"
            >
              <LayoutGrid class="w-4 h-4 text-slate-400" />
              <span>Menu Utama CESA</span>
            </a>

            <router-link
              to="/admin/configurations"
              @click="showUserMenu = false"
              class="flex items-center gap-2.5 px-2.5 py-2 rounded-xl hover:bg-slate-50 text-slate-700 hover:text-slate-900 transition-colors font-medium text-xs"
            >
              <Settings class="w-4 h-4 text-slate-400" />
              <span>Pengaturan Rekrutmen</span>
            </router-link>
          </div>

          <!-- Logout Button -->
          <div class="pt-2 border-t border-slate-100">
            <button
              @click="handleLogout"
              class="w-full flex items-center gap-2.5 px-2.5 py-2 rounded-xl text-rose-600 hover:bg-rose-50 font-bold transition-colors cursor-pointer text-xs"
            >
              <LogOut class="w-4 h-4 text-rose-500" />
              <span>Keluar (Logout)</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { LayoutGrid, Search, Bell, LogOut, Settings } from 'lucide-vue-next';
import { useRekrutmenStore } from '../stores/rekrutmen';

const router = useRouter();
const route = useRoute();
const store = useRekrutmenStore();

const props = defineProps({
  user: {
    type: Object,
    default: () => ({ name: 'Reza', email: '' })
  }
});

const navSearch = ref('');
const currentLang = ref('id');
const showUserMenu = ref(false);
const showNotifications = ref(false);
const showLangMenu = ref(false);

const userInitial = computed(() => {
  if (!props.user?.name) return 'R';
  return props.user.name.charAt(0).toUpperCase();
});

const tabs = [
  { label: 'Manpower Requests', to: '/admin/request-man-powers' },
  { label: 'Job Postings', to: '/admin/job-postings' },
  { label: 'Job Applications', to: '/admin/job-applications' },
  { label: 'Recruitment Progress', to: '/admin/recruitment-progress' },
  { label: 'Configurations', to: '/admin/configurations' },
];

let searchDebounce = null;
const handleGlobalSearch = () => {
  clearTimeout(searchDebounce);
  searchDebounce = setTimeout(() => {
    // If already on job-applications, load immediately
    if (route.path.includes('job-applications')) {
      store.fetchApplications({ search: navSearch.value, job_id: route.query.job_id || '' }, true);
    } else {
      // Auto-navigate to job-applications with the query
      router.push({ path: '/admin/job-applications', query: { search: navSearch.value } });
    }
  }, 250);
};

const handleSearchEnter = () => {
  if (!route.path.includes('job-applications')) {
    router.push({ path: '/admin/job-applications', query: { search: navSearch.value } });
  } else {
    store.fetchApplications({ search: navSearch.value, job_id: route.query.job_id || '' }, true);
  }
};

const clearSearch = () => {
  navSearch.value = '';
  if (route.path.includes('job-applications')) {
    store.fetchApplications({ search: '', job_id: route.query.job_id || '' }, true);
  }
};

const toggleUserMenu = () => {
  showUserMenu.value = !showUserMenu.value;
  showNotifications.value = false;
  showLangMenu.value = false;
};

const toggleNotifications = () => {
  showNotifications.value = !showNotifications.value;
  showUserMenu.value = false;
  showLangMenu.value = false;
};

const toggleLangMenu = () => {
  showLangMenu.value = !showLangMenu.value;
  showUserMenu.value = false;
  showNotifications.value = false;
};

const setLang = (lang) => {
  currentLang.value = lang;
  showLangMenu.value = false;
};

const closeAllDropdowns = () => {
  showUserMenu.value = false;
  showNotifications.value = false;
  showLangMenu.value = false;
};

const handleLogout = () => {
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = '/admin/logout';

  const tokenInput = document.createElement('input');
  tokenInput.type = 'hidden';
  tokenInput.name = '_token';
  tokenInput.value = csrfToken || '';
  form.appendChild(tokenInput);

  document.body.appendChild(form);
  form.submit();
};

onMounted(() => {
  document.addEventListener('click', closeAllDropdowns);
});

onUnmounted(() => {
  document.removeEventListener('click', closeAllDropdowns);
});
</script>
