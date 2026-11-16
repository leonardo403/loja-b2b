<script setup lang="ts">
import { computed } from 'vue'
import { Minus, Plus } from '@lucide/vue'

const props = withDefaults(
  defineProps<{
    modelValue: number
    min?: number
    max?: number
    disabled?: boolean
    size?: 'sm' | 'md'
  }>(),
  { min: 1, max: 9999, disabled: false, size: 'md' },
)

const emit = defineEmits<{
  'update:modelValue': [value: number]
}>()

const canDecrease = computed(() => props.modelValue > props.min && !props.disabled)
const canIncrease = computed(() => props.modelValue < props.max && !props.disabled)

function update(value: number) {
  const clamped = Math.min(props.max, Math.max(props.min, value))
  emit('update:modelValue', clamped)
}

function onInput(event: Event) {
  const value = Number((event.target as HTMLInputElement).value)
  update(Number.isNaN(value) ? props.min : value)
}
</script>

<template>
  <div
    class="inline-flex items-center rounded-lg border border-slate-300 bg-white"
    :class="size === 'sm' ? 'h-9' : 'h-11'"
  >
    <button
      type="button"
      class="flex h-full items-center justify-center px-2.5 text-slate-500 transition hover:text-slate-900 disabled:opacity-30"
      :disabled="!canDecrease"
      aria-label="Diminuir quantidade"
      @click="update(modelValue - 1)"
    >
      <Minus class="size-4" />
    </button>
    <input
      :value="modelValue"
      type="number"
      class="h-full w-12 border-x border-slate-200 text-center text-sm font-semibold text-slate-900 focus:outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
      :disabled="disabled"
      @input="onInput"
    />
    <button
      type="button"
      class="flex h-full items-center justify-center px-2.5 text-slate-500 transition hover:text-slate-900 disabled:opacity-30"
      :disabled="!canIncrease"
      aria-label="Aumentar quantidade"
      @click="update(modelValue + 1)"
    >
      <Plus class="size-4" />
    </button>
  </div>
</template>
