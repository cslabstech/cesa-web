<template>
  <div class="min-h-screen bg-[#f8fafc] text-gray-900 flex flex-col antialiased">
    <!-- Top Header Navigation Bar -->
    <Navbar :user="user" />

    <!-- Main Content Container with smooth transitions -->
    <main class="flex-1 w-full max-w-[1720px] mx-auto px-6 py-6">
      <router-view v-slot="{ Component, route }">
        <transition name="fade" mode="out-in">
          <component :is="Component" :key="route.fullPath" />
        </transition>
      </router-view>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import Navbar from './components/Navbar.vue';
import { useRekrutmenStore } from './stores/rekrutmen';

const store = useRekrutmenStore();
const user = ref({ name: 'Reza', email: '' });

onMounted(() => {
  const rootEl = document.getElementById('rekrutmen-app');
  if (rootEl && rootEl.dataset.user) {
    try {
      user.value = JSON.parse(rootEl.dataset.user);
    } catch (e) {
      console.warn('Failed to parse user dataset', e);
    }
  }

  // Preload all module data once into Pinia store
  store.fetchRequests().catch(() => {});
  store.fetchPostings().catch(() => {});
  store.fetchApplications().catch(() => {});
  store.fetchProgressReport().catch(() => {});
  store.fetchConfigurations().catch(() => {});
});
</script>
