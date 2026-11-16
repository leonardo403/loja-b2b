<script setup lang="ts">
import { PackageSearch } from '@lucide/vue'

withDefaults(
  defineProps<{
    title: string
    description?: string
  }>(),
  { description: '' },
)
</script>

<template>
  <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center">
    <div class="mb-3 flex size-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
      <PackageSearch class="size-7" />
    </div>
    <h3 class="text-base font-semibold text-slate-900">{{ title }}</h3>
    <p v-if="description" class="mt-1 max-w-sm text-sm text-slate-500">{{ description }}</p>
    <div v-if="$slots.action" class="mt-5">
      <slot name="action" />
    </div>
  </div>
</template>
