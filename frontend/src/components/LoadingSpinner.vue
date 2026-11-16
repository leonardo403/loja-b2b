<script setup lang="ts">
import { Loader2 } from '@lucide/vue'

withDefaults(
  defineProps<{
    label?: string
    inline?: boolean
  }>(),
  { label: 'Carregando...', inline: false },
)
</script>

<template>
  <div v-if="inline" class="inline-flex items-center gap-2 text-sm text-slate-500">
    <Loader2 class="size-4 animate-spin" />
    {{ label }}
  </div>
  <div v-else class="flex flex-col items-center justify-center gap-3 py-16 text-slate-500">
    <Loader2 class="size-7 animate-spin text-emerald-600" />
    <p class="text-sm">{{ label }}</p>
  </div>
</template>
