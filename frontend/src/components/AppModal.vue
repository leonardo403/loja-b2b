<script setup lang="ts">
import { onBeforeUnmount, onMounted } from 'vue'
import { X } from '@lucide/vue'

withDefaults(
  defineProps<{
    open: boolean
    title: string
    description?: string
    width?: 'md' | 'lg' | 'xl'
  }>(),
  { description: '', width: 'md' },
)

const emit = defineEmits<{
  close: []
}>()

const widthClass = {
  md: 'max-w-lg',
  lg: 'max-w-2xl',
  xl: 'max-w-4xl',
}

function onKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape') emit('close')
}

onMounted(() => window.addEventListener('keydown', onKeydown))
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown))
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="open" class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="emit('close')" />

        <div
          class="relative flex max-h-[92vh] w-full flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl"
          :class="widthClass[width]"
          role="dialog"
          aria-modal="true"
        >
          <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
            <div>
              <h2 class="text-lg font-semibold text-slate-900">{{ title }}</h2>
              <p v-if="description" class="mt-0.5 text-sm text-slate-500">{{ description }}</p>
            </div>
            <button type="button" class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" @click="emit('close')">
              <X class="size-5" />
            </button>
          </div>

          <div class="overflow-y-auto px-5 py-5">
            <slot />
          </div>

          <div v-if="$slots.footer" class="flex justify-end gap-2 border-t border-slate-200 bg-slate-50 px-5 py-3">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}

.modal-enter-active > div:last-child,
.modal-leave-active > div:last-child {
  transition: transform 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from > div:last-child,
.modal-leave-to > div:last-child {
  transform: translateY(12px);
}
</style>
