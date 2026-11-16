<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted } from 'vue'
import {
  ArrowRight,
  BadgePercent,
  ChevronLeft,
  Info,
  Package,
  ShieldCheck,
  ShoppingCart,
  Trash2,
  TriangleAlert,
  Warehouse,
} from '@lucide/vue'
import EmptyState from '@/components/EmptyState.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import ProductImage from '@/components/ProductImage.vue'
import QuantityStepper from '@/components/QuantityStepper.vue'
import { getErrorMessage } from '@/services/api'
import { useCartStore } from '@/stores/cart'
import { useToastStore } from '@/stores/toast'
import type { CartItem } from '@/types'
import { formatCurrency } from '@/utils/format'

const cart = useCartStore()
const toast = useToastStore()

let syncTimer: number | undefined

const preview = computed(() => cart.preview)
const items = computed(() => cart.preview?.items ?? [])
const hasItems = computed(() => cart.lines.length > 0)

onMounted(async () => {
  if (!hasItems.value) return

  try {
    await cart.sync()
  } catch (error) {
    toast.error(getErrorMessage(error))
  }
})

onBeforeUnmount(() => {
  if (syncTimer) window.clearTimeout(syncTimer)
})

function scheduleSync() {
  if (syncTimer) window.clearTimeout(syncTimer)

  syncTimer = window.setTimeout(async () => {
    try {
      await cart.sync()
    } catch (error) {
      toast.error(getErrorMessage(error))
    }
  }, 250)
}

function updateQuantity(item: CartItem, quantity: number) {
  if (quantity === item.quantity) return

  cart.setQuantity(item.product_id, quantity)
  scheduleSync()
}

function onQuantityChange(item: CartItem, quantity: number) {
  updateQuantity(item, quantity)
}

function removeItem(item: CartItem) {
  cart.remove(item.product_id)
  toast.info(`${item.name} removido do carrinho.`)
  scheduleSync()
}

function savingsLabel(item: CartItem) {
  return item.line_savings > 0
}
</script>

<template>
  <div class="container-page py-8">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
      <div>
        <p class="text-xs font-semibold tracking-widest text-emerald-700 uppercase">Seu pedido</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Carrinho</h1>
        <p class="mt-1 text-sm text-slate-500">
          {{ cart.itemCount }} {{ cart.itemCount === 1 ? 'unidade' : 'unidades' }} no carrinho
        </p>
      </div>
      <RouterLink :to="{ name: 'products' }" class="btn btn-outline btn-sm">
        <ChevronLeft class="size-4" /> Continuar comprando
      </RouterLink>
    </div>

    <EmptyState
      v-if="!hasItems"
      title="Seu carrinho está vazio"
      description="Explore o catálogo e adicione produtos para montar seu pedido."
    >
      <template #action>
        <RouterLink :to="{ name: 'products' }" class="btn btn-primary">
          Ver produtos <ArrowRight class="size-4" />
        </RouterLink>
      </template>
    </EmptyState>

    <div v-else class="grid gap-6 lg:grid-cols-[1fr_360px]">
      <div class="space-y-3">
        <div v-if="cart.error" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          {{ cart.error }}
        </div>

        <LoadingSpinner v-if="cart.loading && !preview" label="Calculando seu carrinho..." />

        <div v-else-if="!preview" class="card px-6 py-10 text-center">
          <TriangleAlert class="mx-auto size-9 text-amber-500" />
          <p class="mt-2 text-sm font-medium text-slate-700">Não foi possível calcular o carrinho.</p>
          <button type="button" class="btn btn-primary btn-sm mt-4" @click="cart.sync()">Tentar novamente</button>
        </div>

        <template v-else>
          <div
            v-for="item in items"
            :key="item.product_id"
            class="card p-4 sm:p-5"
            :class="cart.loading ? 'opacity-70 transition-opacity' : ''"
          >
            <div class="flex gap-3 sm:gap-4">
              <RouterLink
                :to="{ name: 'product', params: { id: item.product_id } }"
                class="w-20 shrink-0 sm:w-24"
              >
                <ProductImage :product="{ name: item.name, category: item.category }" size="sm" />
              </RouterLink>

              <div class="flex min-w-0 flex-1 flex-col gap-2">
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0">
                    <div class="mb-1 flex flex-wrap items-center gap-1.5">
                      <span class="badge bg-slate-100 text-slate-600">{{ item.category ?? 'Geral' }}</span>
                      <span v-if="item.wholesale_applied" class="badge bg-emerald-100 text-emerald-700">
                        Atacado
                      </span>
                      <span v-if="item.discount_percentage > 0" class="badge bg-rose-100 text-rose-700">
                        <BadgePercent class="size-3" /> {{ item.discount_percentage }}%
                      </span>
                    </div>

                    <RouterLink
                      :to="{ name: 'product', params: { id: item.product_id } }"
                      class="line-clamp-2 text-sm font-semibold text-slate-900 hover:text-emerald-700"
                    >
                      {{ item.name }}
                    </RouterLink>
                    <p class="mt-0.5 text-[11px] text-slate-400">SKU {{ item.sku }}</p>
                  </div>

                  <button
                    type="button"
                    class="shrink-0 rounded-lg p-2 text-slate-400 transition hover:bg-red-50 hover:text-red-600"
                    title="Remover item"
                    @click="removeItem(item)"
                  >
                    <Trash2 class="size-4" />
                  </button>
                </div>

                <div class="flex flex-wrap items-end justify-between gap-3">
                  <div class="flex items-center gap-3">
                    <QuantityStepper
                      :model-value="item.quantity"
                      :min="1"
                      :max="Math.max(item.stock_quantity, 1)"
                      size="sm"
                      @update:model-value="onQuantityChange(item, $event)"
                    />
                    <div class="text-xs">
                      <p class="font-semibold text-slate-800">{{ formatCurrency(item.unit_price) }} / un.</p>
                      <p v-if="item.unit_price < item.list_price" class="text-slate-400 line-through">
                        {{ formatCurrency(item.list_price) }}
                      </p>
                    </div>
                  </div>

                  <div class="text-right">
                    <p class="text-base font-bold text-slate-900">{{ formatCurrency(item.line_subtotal) }}</p>
                    <p v-if="savingsLabel(item)" class="text-[11px] font-medium text-emerald-700">
                      economia de {{ formatCurrency(item.line_savings) }}
                    </p>
                  </div>
                </div>

                <p class="flex items-center gap-1 text-[11px] text-slate-400">
                  <Warehouse class="size-3.5" /> {{ item.stock_quantity }} un. disponíveis
                </p>
              </div>
            </div>
          </div>

          <div v-if="preview.warnings.length" class="rounded-xl border border-amber-200 bg-amber-50 p-4">
            <p class="flex items-center gap-2 text-sm font-semibold text-amber-900">
              <TriangleAlert class="size-4" /> Atenção
            </p>
            <ul class="mt-2 space-y-1 text-sm text-amber-800">
              <li v-for="warning in preview.warnings" :key="warning">· {{ warning }}</li>
            </ul>
          </div>
        </template>
      </div>

      <aside v-if="preview" class="h-fit lg:sticky lg:top-28">
        <div class="card p-5">
          <h2 class="flex items-center gap-2 text-base font-semibold text-slate-900">
            <ShoppingCart class="size-4 text-slate-400" /> Resumo do pedido
          </h2>

          <dl class="mt-4 space-y-2.5 text-sm">
            <div class="flex items-center justify-between">
              <dt class="text-slate-500">Itens</dt>
              <dd class="font-medium text-slate-800">{{ preview.item_count }}</dd>
            </div>
            <div class="flex items-center justify-between">
              <dt class="text-slate-500">Unidades</dt>
              <dd class="font-medium text-slate-800">{{ preview.total_units }}</dd>
            </div>
            <div class="flex items-center justify-between">
              <dt class="text-slate-500">Subtotal</dt>
              <dd class="font-medium text-slate-800">{{ formatCurrency(preview.subtotal) }}</dd>
            </div>
            <div v-if="preview.savings > 0" class="flex items-center justify-between text-emerald-700">
              <dt>Economia</dt>
              <dd class="font-semibold">− {{ formatCurrency(preview.savings) }}</dd>
            </div>
            <div v-if="preview.surcharge > 0" class="flex items-center justify-between">
              <dt class="text-slate-500">Acréscimo ({{ preview.surcharge_percentage }}%)</dt>
              <dd class="font-medium text-slate-800">{{ formatCurrency(preview.surcharge) }}</dd>
            </div>
          </dl>

          <div class="mt-4 flex items-end justify-between border-t border-slate-200 pt-4">
            <span class="text-sm font-semibold text-slate-900">Total</span>
            <span class="text-2xl font-bold tracking-tight text-emerald-700">{{ formatCurrency(preview.total) }}</span>
          </div>

          <p class="mt-1 text-[11px] text-slate-400">Frete e condição de pagamento são definidos no checkout.</p>

          <RouterLink :to="{ name: 'checkout' }" class="btn btn-primary mt-5 w-full">
            Ir para o checkout <ArrowRight class="size-4" />
          </RouterLink>

          <div class="mt-4 space-y-2 border-t border-slate-100 pt-4 text-[11px] text-slate-500">
            <p class="flex items-start gap-1.5">
              <Info class="mt-0.5 size-3.5 shrink-0" /> O limite de crédito é validado ao finalizar.
            </p>
            <p class="flex items-start gap-1.5">
              <ShieldCheck class="mt-0.5 size-3.5 shrink-0" /> Preços calculados pela tabela da sua empresa.
            </p>
            <p class="flex items-start gap-1.5">
              <Package class="mt-0.5 size-3.5 shrink-0" /> Estoque reservado somente após a confirmação.
            </p>
          </div>
        </div>
      </aside>
    </div>
  </div>
</template>
