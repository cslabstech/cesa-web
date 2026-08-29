<template>
  <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-5 relative overflow-hidden group hover:border-slate-700 transition-all duration-200 shadow-sm">
    <div class="flex items-center justify-between">
      <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ title }}</span>
      <div :class="['w-10 h-10 rounded-xl flex items-center justify-center', iconBg]">
        <component :is="icon" :class="['w-5 h-5', iconColor]" />
      </div>
    </div>
    <div class="mt-3 flex items-baseline gap-2">
      <span class="text-2xl font-extrabold text-slate-100 tracking-tight">{{ value }}</span>
      <span v-if="subtext" class="text-xs text-slate-500">{{ subtext }}</span>
    </div>
    <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-indigo-500/5 rounded-full blur-xl group-hover:bg-indigo-500/10 transition-colors"></div>
  </div>
</template>

<script setup>
defineProps({
  title: String,
  value: [Number, String],
  subtext: String,
  icon: Object,
  iconBg: {
    type: String,
    default: 'bg-indigo-500/10'
  },
  iconColor: {
    type: String,
    default: 'text-indigo-400'
  }
});
</script>
