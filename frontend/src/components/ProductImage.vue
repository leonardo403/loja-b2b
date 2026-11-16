<script setup lang="ts">
import { computed } from 'vue'
import {
  CupSoda,
  Milk,
  Package,
  ShoppingBasket,
  Sparkles,
  SprayCan,
  type LucideIcon,
} from '@lucide/vue'
import { categoryTheme } from '@/utils/categories'
import type { Product } from '@/types'

const props = withDefaults(
  defineProps<{
    product: Pick<Product, 'name' | 'category'>
    size?: 'sm' | 'md' | 'lg'
  }>(),
  { size: 'md' },
)

const icons: Record<string, LucideIcon> = {
  CupSoda,
  Milk,
  Sparkles,
  SprayCan,
  ShoppingBasket,
  Package,
}

const theme = computed(() => categoryTheme(props.product.category))
const icon = computed(() => icons[theme.value.icon] ?? Package)
const initials = computed(() =>
  props.product.name
    .split(' ')
    .filter((word) => word.length > 2)
    .slice(0, 2)
    .map((word) => word[0])
    .join('')
    .toUpperCase(),
)

const padding = computed(() => {
  if (props.size === 'lg') return 'p-10'
  if (props.size === 'sm') return 'p-3'

  return 'p-5'
})
</script>

<template>
  <div
    class="relative flex aspect-square w-full flex-col items-center justify-center overflow-hidden rounded-lg bg-gradient-to-br"
    :class="[theme.gradient, padding]"
  >
    <div class="absolute -top-8 -right-8 size-24 rounded-full bg-white/40 blur-2xl" />
    <div class="absolute -bottom-10 -left-6 size-28 rounded-full bg-white/30 blur-2xl" />
    <component :is="icon" class="relative z-10 w-1/2 max-w-20" :class="theme.accent" stroke-width="1.5" />
    <span
      v-if="size !== 'sm'"
      class="relative z-10 mt-2 text-[11px] font-bold tracking-[0.25em] text-slate-500/80"
    >
      {{ initials }}
    </span>
  </div>
</template>
