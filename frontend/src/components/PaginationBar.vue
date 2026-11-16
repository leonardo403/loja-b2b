<script setup lang="ts">
import { computed } from 'vue'
import { ChevronLeft, ChevronRight } from '@lucide/vue'

const props = defineProps<{
  currentPage: number
  lastPage: number
  total: number
}>()

const emit = defineEmits<{
  change: [page: number]
}>()

const pages = computed(() => {
  const total = props.lastPage
  const current = props.currentPage
  const windowSize = 5
  let start = Math.max(1, current - Math.floor(windowSize / 2))
  const end = Math.min(total, start + windowSize - 1)

  start = Math.max(1, end - windowSize + 1)

  return Array.from({ length: end - start + 1 }, (_, index) => start + index)
})

function go(page: number) {
  if (page >= 1 && page <= props.lastPage && page !== props.currentPage) {
    emit('change', page)
  }
}
</script>

<template>
  <div v-if="lastPage > 1" class="flex flex-col items-center justify-between gap-3 sm:flex-row">
    <p class="text-sm text-slate-500">
      Página <span class="font-semibold text-slate-700">{{ currentPage }}</span> de
      <span class="font-semibold text-slate-700">{{ lastPage }}</span>
      · {{ total }} produtos
    </p>

    <div class="flex items-center gap-1">
      <button type="button" class="btn btn-outline btn-sm size-9 p-0" :disabled="currentPage <= 1" @click="go(currentPage - 1)">
        <ChevronLeft class="size-4" />
      </button>

      <button
        v-for="page in pages"
        :key="page"
        type="button"
        class="btn btn-sm size-9 p-0"
        :class="page === currentPage ? 'btn-dark' : 'btn-outline'"
        @click="go(page)"
      >
        {{ page }}
      </button>

      <button type="button" class="btn btn-outline btn-sm size-9 p-0" :disabled="currentPage >= lastPage" @click="go(currentPage + 1)">
        <ChevronRight class="size-4" />
      </button>
    </div>
  </div>
</template>
