<template>
  <DropdownMenuPortal>
    <DropdownMenuContent
      :class="cn(
        'z-50 min-w-[9rem] overflow-hidden rounded-md border border-zinc-200 bg-white p-1 text-zinc-950 shadow-md animate-in fade-in-80 zoom-in-95 data-[side=bottom]:slide-in-from-top-2 data-[side=top]:slide-in-from-bottom-2 text-xs',
        $attrs.class
      )"
      :side-offset="sideOffset"
      :align="align"
      v-bind="$attrs"
    >
      <slot />
    </DropdownMenuContent>
  </DropdownMenuPortal>
</template>

<script setup>
import { DropdownMenuPortal, DropdownMenuContent } from 'radix-vue';
import { cn } from '../../../lib/utils';

defineProps({
  sideOffset: {
    type: Number,
    default: 4,
  },
  align: {
    type: String,
    default: 'end',
  },
});
</script>
