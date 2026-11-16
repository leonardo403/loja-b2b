<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  ArrowLeft,
  BadgePercent,
  Check,
  ChevronRight,
  CreditCard,
  PackageCheck,
  ShieldCheck,
  ShoppingCart,
  Tag,
  TriangleAlert,
  Truck,
  Warehouse,
} from '@lucide/vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import ProductCard from '@/components/ProductCard.vue'
import ProductImage from '@/components/ProductImage.vue'
import QuantityStepper from '@/components/QuantityStepper.vue'
import { getErrorMessage } from '@/services/api'
import { getProduct, getProducts } from '@/services/catalog.service'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import { useToastStore } from '@/stores/toast'
import type { Product } from '@/types'
import { formatCurrency } from '@/utils/format'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const cart = useCartStore()
const toast = useToastStore()

const product = ref<Product | null>(null)
const related = ref<Product[]>([])
const loading = ref(true)
const error = ref<string | null>(null)
const quantity = ref(1)

const price = computed(() => product.value?.price ?? null)
const outOfStock = computed(() => (product.value?.stock_quantity ?? 0) <= 0)
const lowStock = computed(() => {
  const stock = product.value?.stock_quantity ?? 0

  return stock > 0 && stock <= 20
})
const canBuy = computed(() => !auth.isAdmin)
const discountPercentage = computed(() => price.value?.discount_percentage ?? 0)

const wholesaleActive = computed(() => {
  if (!price.value || price.value.wholesale_price === null) return false
  if (price.value.wholesale_min_quantity === null) return false

  return quantity.value >= price.value.wholesale_min_quantity
})

const unitPrice = computed(() => {
  if (!price.value) return 0

  if (wholesaleActive.value && price.value.wholesale_price !== null) {
    return Math.round(price.value.wholesale_price * (1 - discountPercentage.value / 100) * 100) / 100
  }

  return price.value.unit_price
})

const listPrice = computed(() => price.value?.list_price ?? 0)
const hasDiscount = computed(() => unitPrice.value < listPrice.value)
const lineTotal = computed(() => Math.round(unitPrice.value * quantity.value * 100) / 100)
const savings = computed(() => Math.round((listPrice.value - unitPrice.value) * quantity.value * 100) / 100)

const unitsToWholesale = computed(() => {
  if (!price.value || price.value.wholesale_price === null) return null
  if (price.value.wholesale_min_quantity === null) return null
  if (wholesaleActive.value) return null

  return price.value.wholesale_min_quantity - quantity.value
})

const wholesaleUnitPrice = computed(() => {
  if (!price.value || price.value.wholesale_price === null) return null

  return Math.round(price.value.wholesale_price * (1 - discountPercentage.value / 100) * 100) / 100
})

watch(
  () => route.params.id,
  async (id) => {
    loading.value = true
    error.value = null
    product.value = null
    related.value = []
    quantity.value = 1

    try {
      const loaded = await getProduct(String(id))
      product.value = loaded

      if (loaded.category) {
        const response = await getProducts({ category: loaded.category, per_page: 8 })
        related.value = response.data.filter((item) => item.id !== loaded.id).slice(0, 4)
      }
    } catch (loadError) {
      error.value = getErrorMessage(loadError)
    } finally {
      loading.value = false
    }
  },
  { immediate: true },
)

function addToCart() {
  if (!product.value) return

  if (!canBuy.value) {
    toast.info('Contas administrativas não realizam pedidos. Entre com uma conta de cliente.')
    return
  }

  cart.add(product.value, quantity.value)
  toast.success(`${quantity.value} un. de ${product.value.name} adicionadas ao carrinho.`)

  if (auth.isAuthenticated) {
    cart.sync().catch(() => undefined)
  } else {
    toast.info('Entre para ver os preços da sua tabela e finalizar o pedido.')
  }
}

function addRelated(item: Product) {
  if (auth.isAdmin) {
    toast.info('Contas administrativas não realizam pedidos. Entre com uma conta de cliente.')
    return
  }

  cart.add(item)
  toast.success('Produto adicionado ao carrinho.')

  if (auth.isAuthenticated) {
    cart.sync().catch(() => undefined)
  }
}
</script>

<template>
  <div class="container-page py-8">
    <div class="mb-5 flex flex-wrap items-center gap-1.5 text-xs text-slate-500">
      <RouterLink :to="{ name: 'home' }" class="hover:text-emerald-700">Início</RouterLink>
      <ChevronRight class="size-3.5 text-slate-300" />
      <RouterLink :to="{ name: 'products' }" class="hover:text-emerald-700">Produtos</RouterLink>
      <template v-if="product?.category">
        <ChevronRight class="size-3.5 text-slate-300" />
        <RouterLink
          :to="{ name: 'products', query: { category: product.category } }"
          class="hover:text-emerald-700"
        >
          {{ product.category }}
        </RouterLink>
      </template>
    </div>

    <LoadingSpinner v-if="loading" label="Carregando produto..." />

    <div v-else-if="error" class="card flex flex-col items-center gap-4 px-6 py-14 text-center">
      <TriangleAlert class="size-10 text-amber-500" />
      <div>
        <h2 class="text-lg font-semibold text-slate-900">Não foi possível carregar o produto</h2>
        <p class="mt-1 text-sm text-slate-500">{{ error }}</p>
      </div>
      <RouterLink :to="{ name: 'products' }" class="btn btn-primary">Voltar ao catálogo</RouterLink>
    </div>

    <template v-else-if="product">
      <button type="button" class="btn btn-ghost btn-sm mb-4 -ml-2" @click="router.back()">
        <ArrowLeft class="size-4" /> Voltar
      </button>

      <div class="grid gap-6 lg:grid-cols-[1.1fr_1fr] xl:grid-cols-[1.15fr_1fr]">
        <div class="space-y-4">
          <div class="card relative overflow-hidden p-4 sm:p-6">
            <ProductImage :product="product" size="lg" />
            <span
              v-if="product.price?.on_promotion"
              class="badge absolute top-7 left-7 bg-rose-600 text-white shadow"
            >
              <BadgePercent class="size-3.5" /> {{ discountPercentage }}% OFF
            </span>
            <span v-if="outOfStock" class="badge absolute top-7 right-7 bg-slate-900 text-white shadow">
              Sem estoque
            </span>
          </div>

          <div class="card grid grid-cols-2 gap-px overflow-hidden bg-slate-200 sm:grid-cols-4">
            <div class="bg-white p-4">
              <p class="text-[10px] font-semibold tracking-widest text-slate-400 uppercase">SKU</p>
              <p class="mt-1 truncate text-sm font-semibold text-slate-800" :title="product.sku">{{ product.sku }}</p>
            </div>
            <div class="bg-white p-4">
              <p class="text-[10px] font-semibold tracking-widest text-slate-400 uppercase">EAN</p>
              <p class="mt-1 truncate text-sm font-semibold text-slate-800">{{ product.ean ?? '—' }}</p>
            </div>
            <div class="bg-white p-4">
              <p class="text-[10px] font-semibold tracking-widest text-slate-400 uppercase">Marca</p>
              <p class="mt-1 truncate text-sm font-semibold text-slate-800">{{ product.brand ?? '—' }}</p>
            </div>
            <div class="bg-white p-4">
              <p class="text-[10px] font-semibold tracking-widest text-slate-400 uppercase">Categoria</p>
              <p class="mt-1 truncate text-sm font-semibold text-slate-800">{{ product.category ?? '—' }}</p>
            </div>
          </div>

          <div v-if="product.description" class="card p-5 sm:p-6">
            <h2 class="text-sm font-semibold text-slate-900">Descrição do produto</h2>
            <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ product.description }}</p>
          </div>
        </div>

        <div class="space-y-4">
          <div class="card p-5 sm:p-6">
            <div class="flex flex-wrap items-center gap-1.5">
              <span class="badge bg-slate-100 text-slate-600">{{ product.category ?? 'Geral' }}</span>
              <span v-if="product.brand" class="badge bg-emerald-50 text-emerald-700">{{ product.brand }}</span>
              <span v-if="product.price?.on_promotion" class="badge bg-rose-100 text-rose-700">Promoção ativa</span>
            </div>

            <h1 class="mt-3 text-2xl leading-tight font-bold tracking-tight text-slate-900">
              {{ product.name }}
            </h1>

            <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-slate-500">
              <span class="flex items-center gap-1.5">
                <PackageCheck class="size-4" :class="outOfStock ? 'text-red-500' : 'text-emerald-600'" />
                {{ outOfStock ? 'Indisponível' : `${product.stock_quantity} un. em estoque` }}
              </span>
              <span v-if="lowStock" class="badge bg-amber-100 text-amber-800">Estoque baixo</span>
            </div>

            <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4">
              <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                  <p class="text-[10px] font-semibold tracking-widest text-slate-400 uppercase">
                    {{ wholesaleActive ? 'Preço de atacado' : 'Seu preço' }}
                  </p>
                  <div class="mt-1 flex flex-wrap items-baseline gap-2">
                    <span class="text-3xl font-bold tracking-tight text-slate-900">
                      {{ formatCurrency(unitPrice) }}
                    </span>
                    <span v-if="hasDiscount" class="text-sm font-medium text-slate-400 line-through">
                      {{ formatCurrency(listPrice) }}
                    </span>
                    <span class="text-xs text-slate-500">/ un.</span>
                  </div>
                </div>

                <div class="text-right">
                  <p class="text-[10px] font-semibold tracking-widest text-slate-400 uppercase">Total</p>
                  <p class="mt-1 text-xl font-bold text-emerald-700">{{ formatCurrency(lineTotal) }}</p>
                  <p v-if="savings > 0" class="text-[11px] font-medium text-emerald-700">
                    Economia de {{ formatCurrency(savings) }}
                  </p>
                </div>
              </div>

              <p v-if="unitsToWholesale" class="mt-3 flex items-start gap-1.5 rounded-lg bg-emerald-50 px-3 py-2 text-xs text-emerald-800">
                <Tag class="mt-0.5 size-3.5 shrink-0" />
                Adicione mais {{ unitsToWholesale }} un. e pague
                <strong class="font-semibold">{{ formatCurrency(wholesaleUnitPrice) }}</strong>
                por unidade.
              </p>
              <p v-else-if="wholesaleActive" class="mt-3 flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-800">
                <Check class="size-3.5" /> Faixa de atacado aplicada automaticamente.
              </p>
            </div>

            <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-end">
              <div>
                <p class="label">Quantidade</p>
                <QuantityStepper
                  v-model="quantity"
                  :min="1"
                  :max="Math.max(product.stock_quantity, 1)"
                  :disabled="outOfStock"
                />
              </div>
              <button
                type="button"
                class="btn btn-primary h-11 flex-1"
                :disabled="outOfStock || !canBuy"
                @click="addToCart"
              >
                <ShoppingCart class="size-4" />
                {{ outOfStock ? 'Sem estoque' : 'Adicionar ao carrinho' }}
              </button>
            </div>

            <p v-if="!canBuy" class="mt-3 text-xs text-amber-700">
              Você está logado como administrador. Use uma conta de cliente para comprar.
            </p>

            <div class="mt-5 space-y-2.5 border-t border-slate-100 pt-4 text-xs text-slate-600">
              <p class="flex items-start gap-2">
                <Truck class="mt-0.5 size-4 shrink-0 text-emerald-600" />
                Entrega para todo o Brasil com frete calculado no fechamento.
              </p>
              <p class="flex items-start gap-2">
                <CreditCard class="mt-0.5 size-4 shrink-0 text-emerald-600" />
                PIX ou boleto com prazos e acréscimos por condição.
              </p>
              <p class="flex items-start gap-2">
                <ShieldCheck class="mt-0.5 size-4 shrink-0 text-emerald-600" />
                Compra protegida pela análise de crédito da sua empresa.
              </p>
              <p class="flex items-start gap-2">
                <Warehouse class="mt-0.5 size-4 shrink-0 text-emerald-600" />
                Preço calculado pela tabela da sua empresa.
              </p>
            </div>
          </div>

          <div v-if="product.price?.wholesale_price !== null" class="card p-5">
            <h2 class="text-sm font-semibold text-slate-900">Faixas de preço</h2>
            <div class="mt-3 overflow-hidden rounded-lg border border-slate-200">
              <div class="flex items-center justify-between px-3 py-2.5 text-xs">
                <span class="text-slate-500">
                  {{ product.price?.wholesale_min_quantity }} un. ou mais
                </span>
                <span class="font-semibold text-emerald-700">{{ formatCurrency(wholesaleUnitPrice) }}</span>
              </div>
              <div class="flex items-center justify-between border-t border-slate-100 bg-slate-50 px-3 py-2.5 text-xs">
                <span class="text-slate-500">Preço unitário padrão</span>
                <span class="font-semibold text-slate-700">{{ formatCurrency(price?.unit_price) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <section v-if="related.length" class="mt-12">
        <div class="mb-5 flex items-end justify-between">
          <div>
            <p class="text-xs font-semibold tracking-widest text-emerald-700 uppercase">Você também pode gostar</p>
            <h2 class="mt-1 text-xl font-bold tracking-tight text-slate-900">Similares em {{ product.category }}</h2>
          </div>
          <RouterLink
            :to="{ name: 'products', query: { category: product.category ?? undefined } }"
            class="text-sm font-semibold text-emerald-700 hover:underline"
          >
            Ver categoria
          </RouterLink>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <ProductCard v-for="item in related" :key="item.id" :product="item" @add="addRelated" />
        </div>
      </section>
    </template>
  </div>
</template>
