<script setup lang="ts">
import { computed } from 'vue'
import type { OrderStatus } from '@/types'

const props = defineProps<{
  status: OrderStatus
  label?: string
}>()

const styles: Record<OrderStatus, string> = {
  pending: 'bg-amber-100 text-amber-800',
  confirmed: 'bg-emerald-100 text-emerald-800',
  shipped: 'bg-sky-100 text-sky-800',
  canceled: 'bg-slate-200 text-slate-600',
}

const labels: Record<OrderStatus, string> = {
  pending: 'Aguardando',
  confirmed: 'Confirmado',
  shipped: 'Enviado',
  canceled: 'Cancelado',
}

const text = computed(() => props.label ?? labels[props.status])
</script>

<template>
  <span class="badge" :class="styles[status]">{{ text }}</span>
</template>
