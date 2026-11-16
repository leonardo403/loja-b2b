<script setup lang="ts">
import { computed } from 'vue'
import { BadgePercent } from '@lucide/vue'
import { formatCurrency } from '@/utils/format'
import type { Price } from '@/types'

const props = withDefaults(
  defineProps<{
    price: Price | null
    size?: 'sm' | 'md' | 'lg'
  }>(),
  { size: 'md' },
)

const unitPrice = computed(() => props.price?.unit_price ?? 0)
const listPrice = computed(() => props.price?.list_price ?? 0)
const hasDiscount = computed(() => {
  if (!props.price) return false

  return props.price.unit_price < props.price.list_price
})

const priceClass = computed(() => {
  if (props.size === 'lg') return 'text-3xl'
  if (props.size === 'sm') return 'text-base'

  return 'text-xl'
})

const showWholesale = computed(
  () => props.price?.wholesale_price !== null && props.price?.wholesale_price !== undefined,
)
</script>

<template>
  <div>
    <div class="flex flex-wrap items-baseline gap-2">
      <span class="font-bold tracking-tight text-slate-900" :class="priceClass">
        {{ formatCurrency(unitPrice) }}
      </span>
      <span v-if="hasDiscount" class="text-xs font-medium text-slate-400 line-through">
        {{ formatCurrency(listPrice) }}
      </span>
    </div>

    <div class="mt-1 flex flex-wrap items-center gap-1.5">
      <span
        v-if="price?.on_promotion"
        class="badge bg-rose-100 text-rose-700"
      >
        <BadgePercent class="size-3" />
        {{ price.discount_percentage }}% OFF
      </span>
      <span v-else-if="price?.wholesale_applied" class="badge bg-emerald-100 text-emerald-700">
        Preço de atacado
      </span>
      <span v-else-if="price" class="text-[11px] text-slate-500">/ un.</span>
    </div>

    <p v-if="showWholesale && !price?.wholesale_applied" class="mt-1 text-[11px] text-slate-500">
      A partir de {{ price?.wholesale_min_quantity }} un.:
      <span class="font-semibold text-emerald-700">{{ formatCurrency(price?.wholesale_price) }}</span>
    </p>
  </div>
</template>
