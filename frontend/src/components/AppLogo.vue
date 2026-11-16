<script setup lang="ts">
import { Boxes } from '@lucide/vue'

withDefaults(
  defineProps<{
    light?: boolean
    compact?: boolean
  }>(),
  { light: false, compact: false },
)
</script>

<template>
  <div class="flex items-center gap-2.5">
    <div
      class="flex items-center justify-center rounded-xl bg-emerald-600 text-white shadow-sm"
      :class="compact ? 'size-9' : 'size-11'"
    >
      <Boxes :class="compact ? 'size-5' : 'size-6'" />
    </div>
    <div class="leading-tight">
      <p class="text-base font-bold tracking-tight" :class="light ? 'text-white' : 'text-slate-900'">
        Atacado<span class="text-emerald-500">Lima</span>
      </p>
      <p v-if="!compact" class="text-[11px] font-medium tracking-widest uppercase" :class="light ? 'text-slate-400' : 'text-slate-500'">
        Comércio B2B
      </p>
    </div>
  </div>
</template>
