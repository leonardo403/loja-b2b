<script setup lang="ts">
import { computed } from 'vue'
import { ShoppingCart } from '@lucide/vue'
import ProductImage from '@/components/ProductImage.vue'
import PriceTag from '@/components/PriceTag.vue'
import { categoryTheme } from '@/utils/categories'
import type { Product } from '@/types'

const props = defineProps<{
  product: Product
}>()

const emit = defineEmits<{
  add: [product: Product]
}>()

const theme = computed(() => categoryTheme(props.product.category))
const outOfStock = computed(() => props.product.stock_quantity <= 0)
</script>

<template>
  <article class="card group flex flex-col overflow-hidden transition hover:-translate-y-0.5 hover:shadow-md">
    <RouterLink :to="{ name: 'product', params: { id: product.id } }" class="relative block p-3">
      <ProductImage :product="product" />
      <span
        v-if="product.price?.on_promotion"
        class="badge absolute top-5 left-5 bg-rose-600 text-white shadow"
      >
        -{{ product.price.discount_percentage }}%
      </span>
      <span v-if="outOfStock" class="badge absolute top-5 right-5 bg-slate-900/90 text-white shadow">
        Sem estoque
      </span>
    </RouterLink>

    <div class="flex flex-1 flex-col gap-2 px-4 pt-1 pb-4">
      <div class="flex flex-wrap items-center gap-1.5 text-[11px] font-medium">
        <span class="badge" :class="theme.badge">{{ product.category ?? 'Geral' }}</span>
        <span class="text-slate-400">{{ product.brand }}</span>
      </div>

      <RouterLink
        :to="{ name: 'product', params: { id: product.id } }"
        class="line-clamp-2 text-sm leading-snug font-semibold text-slate-900 transition group-hover:text-emerald-700"
      >
        {{ product.name }}
      </RouterLink>

      <p class="text-[11px] text-slate-400">SKU {{ product.sku }}</p>

      <div class="mt-auto flex items-end justify-between gap-2 pt-2">
        <PriceTag :price="product.price" size="sm" />
        <button
          type="button"
          class="btn btn-primary size-10 shrink-0 rounded-lg p-0"
          :disabled="outOfStock"
          :title="outOfStock ? 'Produto sem estoque' : 'Adicionar ao carrinho'"
          @click="emit('add', product)"
        >
          <ShoppingCart class="size-4" />
        </button>
      </div>
    </div>
  </article>
</template>
