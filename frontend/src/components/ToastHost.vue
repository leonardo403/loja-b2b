<script setup lang="ts">
import { AlertCircle, CheckCircle2, Info, X } from '@lucide/vue'
import { useToastStore } from '@/stores/toast'

const toast = useToastStore()

const styles = {
  success: 'border-emerald-200 bg-emerald-50 text-emerald-900',
  error: 'border-red-200 bg-red-50 text-red-900',
  info: 'border-sky-200 bg-sky-50 text-sky-900',
}

const iconColor = {
  success: 'text-emerald-600',
  error: 'text-red-600',
  info: 'text-sky-600',
}
</script>

<template>
  <div class="pointer-events-none fixed inset-x-0 bottom-4 z-[100] flex flex-col items-center gap-2 px-4 sm:items-end sm:pr-6">
    <TransitionGroup name="toast">
      <div
        v-for="message in toast.messages"
        :key="message.id"
        class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl border px-4 py-3 shadow-lg backdrop-blur"
        :class="styles[message.type]"
      >
        <CheckCircle2 v-if="message.type === 'success'" class="mt-0.5 size-5 shrink-0" :class="iconColor.success" />
        <AlertCircle v-else-if="message.type === 'error'" class="mt-0.5 size-5 shrink-0" :class="iconColor.error" />
        <Info v-else class="mt-0.5 size-5 shrink-0" :class="iconColor.info" />
        <p class="flex-1 text-sm font-medium">{{ message.message }}</p>
        <button type="button" class="shrink-0 opacity-60 transition hover:opacity-100" @click="toast.dismiss(message.id)">
          <X class="size-4" />
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.25s ease;
}

.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(8px);
}
</style>
