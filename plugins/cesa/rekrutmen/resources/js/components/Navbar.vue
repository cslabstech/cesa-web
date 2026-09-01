<template>
  <header class="sticky top-0 z-50 bg-[#739ec5] shadow-xs select-none font-sans text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="relative flex items-center justify-between h-16">
        
        <!-- Left: Brand Logo -->
        <div class="flex items-center shrink-0 z-10">
          <router-link to="/admin/job-postings" class="flex items-center gap-2.5 group">
            <img
              :src="logoUrl"
              alt="CESA Logo"
              class="h-8 w-8 shrink-0 object-contain drop-shadow-sm transition-transform group-hover:scale-105"
            />
            <div class="flex flex-col leading-none">
              <span class="text-base font-black tracking-wider text-white uppercase drop-shadow-xs">CESA</span>
              <span class="text-[9px] font-bold tracking-widest text-white/90 uppercase mt-0.5">REKRUTMEN</span>
            </div>
          </router-link>
        </div>

        <!-- Center: Mathematically Centered Navigation Menu Links -->
        <nav class="hidden md:flex items-center justify-center gap-7 lg:gap-9 text-sm absolute inset-x-0 mx-auto w-fit z-0 pointer-events-none [&>*]:pointer-events-auto">
          <router-link
            to="/admin/job-postings"
            class="relative py-1 text-xs lg:text-sm font-semibold transition-all inline-flex flex-col items-center"
            :class="[
              isActive('/admin/job-postings')
                ? 'text-white font-bold'
                : 'text-white/80 hover:text-white font-medium'
            ]"
          >
            <span>Lowongan Kerja</span>
            <span
              v-if="isActive('/admin/job-postings')"
              class="absolute -bottom-1 left-0 right-0 h-[2.5px] bg-white rounded-full shadow-xs"
            ></span>
          </router-link>

          <router-link
            to="/admin/job-applications"
            class="relative py-1 text-xs lg:text-sm font-semibold transition-all inline-flex flex-col items-center"
            :class="[
              isActive('/admin/job-applications')
                ? 'text-white font-bold'
                : 'text-white/80 hover:text-white font-medium'
            ]"
          >
            <span>Data Pelamar</span>
            <span
              v-if="isActive('/admin/job-applications')"
              class="absolute -bottom-1 left-0 right-0 h-[2.5px] bg-white rounded-full shadow-xs"
            ></span>
          </router-link>

          <router-link
            to="/admin/request-man-powers"
            class="relative py-1 text-xs lg:text-sm font-semibold transition-all inline-flex flex-col items-center"
            :class="[
              isActive('/admin/request-man-powers')
                ? 'text-white font-bold'
                : 'text-white/80 hover:text-white font-medium'
            ]"
          >
            <span>Permintaan FPTK</span>
            <span
              v-if="isActive('/admin/request-man-powers')"
              class="absolute -bottom-1 left-0 right-0 h-[2.5px] bg-white rounded-full shadow-xs"
            ></span>
          </router-link>

          <router-link
            to="/admin/recruitment-progress"
            class="relative py-1 text-xs lg:text-sm font-semibold transition-all inline-flex flex-col items-center"
            :class="[
              isActive('/admin/recruitment-progress')
                ? 'text-white font-bold'
                : 'text-white/80 hover:text-white font-medium'
            ]"
          >
            <span>Monitoring & Progress</span>
            <span
              v-if="isActive('/admin/recruitment-progress')"
              class="absolute -bottom-1 left-0 right-0 h-[2.5px] bg-white rounded-full shadow-xs"
            ></span>
          </router-link>

          <router-link
            to="/admin/configurations"
            class="relative py-1 text-xs lg:text-sm font-semibold transition-all inline-flex flex-col items-center"
            :class="[
              isActive('/admin/configurations')
                ? 'text-white font-bold'
                : 'text-white/80 hover:text-white font-medium'
            ]"
          >
            <span>Master Data</span>
            <span
              v-if="isActive('/admin/configurations')"
              class="absolute -bottom-1 left-0 right-0 h-[2.5px] bg-white rounded-full shadow-xs"
            ></span>
          </router-link>
        </nav>

        <!-- Right: Admin Panel Icon Button + Profile Icon Button -->
        <div class="flex items-center gap-3 shrink-0 z-10">
          <!-- 1. Admin Panel Icon Button (Outside) -->
          <a
            href="/admin"
            class="w-9 h-9 rounded-xl flex items-center justify-center bg-white/15 hover:bg-white/25 text-white border border-white/25 shadow-2xs transition-all cursor-pointer"
            title="Panel Admin Filament"
          >
            <LayoutDashboard class="w-4 h-4" />
          </a>

          <!-- 2. Profile Icon Trigger -->
          <div class="relative" ref="dropdownRef">
            <button
              @click="dropdownOpen = !dropdownOpen"
              class="w-9 h-9 rounded-xl flex items-center justify-center bg-white/15 hover:bg-white/25 text-white border border-white/25 shadow-2xs transition-all cursor-pointer focus:outline-none"
              :class="{ 'ring-2 ring-white/50 bg-white/30': dropdownOpen }"
              title="Profil Pengguna"
              aria-label="Profil Pengguna"
            >
              <User class="w-4 h-4" />
            </button>

            <!-- Dropdown Card -->
            <div
              v-if="dropdownOpen"
              class="absolute right-0 top-full mt-2.5 w-76 bg-white rounded-2xl shadow-xl border border-slate-100 p-5 z-50 text-slate-800 animate-in fade-in zoom-in-95 duration-100"
            >
              <!-- User Info Header -->
              <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-xl bg-slate-100 border border-slate-200 shrink-0 flex items-center justify-center text-slate-700 font-bold text-sm">
                  {{ userInitials }}
                </div>

                <div class="min-w-0 flex-1">
                  <h4 class="font-bold text-slate-900 text-xs tracking-tight uppercase leading-tight truncate">
                    {{ user?.name || 'REZA' }}
                  </h4>
                  <p class="text-[11px] text-slate-400 font-medium truncate mt-0.5">
                    {{ user?.email || 'reza@completeselular.com' }}
                  </p>
                </div>
              </div>

              <!-- Sign Out Button -->
              <button
                @click="logout"
                class="w-full mt-4 flex items-center justify-center gap-2 bg-[#a3c7e4] hover:bg-[#92bade] text-[#1c5d99] font-bold text-xs py-2.5 px-4 rounded-xl transition-colors cursor-pointer shadow-2xs"
              >
                <LogOut class="w-4 h-4" />
                <span>Sign Out</span>
              </button>
            </div>
          </div>

          <!-- Mobile Hamburger Toggle -->
          <button
            @click="mobileMenuOpen = !mobileMenuOpen"
            class="md:hidden p-2 rounded-lg text-white hover:bg-white/15 transition-colors"
          >
            <Menu v-if="!mobileMenuOpen" class="w-5 h-5" />
            <X v-else class="w-5 h-5" />
          </button>
        </div>

      </div>
    </div>

    <!-- Mobile Drawer Menu -->
    <div v-if="mobileMenuOpen" class="md:hidden border-t border-white/20 bg-[#638db4] px-4 pt-2 pb-4 space-y-1">
      <router-link
        to="/admin/job-postings"
        @click="mobileMenuOpen = false"
        class="block px-3 py-2 rounded-md text-xs font-semibold"
        :class="isActive('/admin/job-postings') ? 'text-white font-bold border-l-2 border-white pl-2' : 'text-white/90 hover:bg-white/10'"
      >
        Lowongan Kerja
      </router-link>

      <router-link
        to="/admin/job-applications"
        @click="mobileMenuOpen = false"
        class="block px-3 py-2 rounded-md text-xs font-semibold"
        :class="isActive('/admin/job-applications') ? 'text-white font-bold border-l-2 border-white pl-2' : 'text-white/90 hover:bg-white/10'"
      >
        Data Pelamar
      </router-link>

      <router-link
        to="/admin/request-man-powers"
        @click="mobileMenuOpen = false"
        class="block px-3 py-2 rounded-md text-xs font-semibold"
        :class="isActive('/admin/request-man-powers') ? 'text-white font-bold border-l-2 border-white pl-2' : 'text-white/90 hover:bg-white/10'"
      >
        Permintaan FPTK
      </router-link>

      <router-link
        to="/admin/recruitment-progress"
        @click="mobileMenuOpen = false"
        class="block px-3 py-2 rounded-md text-xs font-semibold"
        :class="isActive('/admin/recruitment-progress') ? 'text-white font-bold border-l-2 border-white pl-2' : 'text-white/90 hover:bg-white/10'"
      >
        Monitoring & Progress
      </router-link>

      <router-link
        to="/admin/configurations"
        @click="mobileMenuOpen = false"
        class="block px-3 py-2 rounded-md text-xs font-semibold"
        :class="isActive('/admin/configurations') ? 'text-white font-bold border-l-2 border-white pl-2' : 'text-white/90 hover:bg-white/10'"
      >
        Master Data
      </router-link>
    </div>
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRoute } from 'vue-router';
import { LayoutDashboard, User, LogOut, Menu, X } from 'lucide-vue-next';

const props = defineProps({
  user: {
    type: Object,
    default: () => ({ name: 'REZA', email: 'reza@completeselular.com' })
  }
});

const logoUrl = '/images/logo.png';
const dropdownOpen = ref(false);
const dropdownRef = ref(null);
const mobileMenuOpen = ref(false);
const route = useRoute();

const isActive = (path) => {
  return route.path.startsWith(path);
};

const userInitials = computed(() => {
  if (!props.user?.name) return 'RE';
  const parts = props.user.name.trim().split(/\s+/);
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase();
  }
  return props.user.name.substring(0, 2).toUpperCase();
});

const handleClickOutside = (event) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    dropdownOpen.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});

const logout = () => {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  if (token) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/admin/logout';
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = '_token';
    input.value = token;
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
  } else {
    window.location.href = '/admin/login';
  }
};
</script>
