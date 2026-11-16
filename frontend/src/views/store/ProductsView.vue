<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ChevronDown, Filter, Search, SlidersHorizontal, X } from '@lucide/vue'
import EmptyState from '@/components/EmptyState.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import ProductCard from '@/components/ProductCard.vue'
import { getErrorMessage } from '@/services/api'
import { getCatalogFilters, getProducts } from '@/services/catalog.service'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import { useToastStore } from '@/stores/toast'
import type { CatalogFilters, PaginationMeta, Product } from '@/types'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const cart = useCartStore()
const toast = useToastStore()

const products = ref<Product[]>([])
const filters = ref<CatalogFilters>({ categories: [], brands: [] })
const meta = ref<PaginationMeta>({ current_page: 1, last_page: 1, per_page: 12, total: 0, from: null, to: null })
const loading = ref(true)
const error = ref<string | null>(null)
const searchTerm = ref('')
const mobileFilters = ref(false)

const selectedCategory = computed(() => (typeof route.query.category === 'string' ? route.query.category : ''))
const selectedBrand = computed(() => (typeof route.query.brand === 'string' ? route.query.brand : ''))
const selectedSort = computed(() => (typeof route.query.sort === 'string' ? route.query.sort : 'name_asc'))
const currentPage = computed(() => Number(route.query.page ?? 1))
const activeFilterCount = computed(
  () => [selectedCategory.value, selectedBrand.value].filter(Boolean).length,
)

const heading = computed(() => {
  const query = typeof route.query.q === 'string' ? route.query.q : ''

  if (query) return `Resultados para “${query}”`
  if (selectedCategory.value) return selectedCategory.value

  return 'Catálogo completo'
})

watch(
  () => route.query.q,
  (value) => {
    searchTerm.value = typeof value === 'string' ? value : ''
  },
  { immediate: true },
)

watch(
  () => route.query,
  async () => {
    loading.value = true
    error.value = null

    try {
      const response = await getProducts({
        search: typeof route.query.q === 'string' ? route.query.q : undefined,
        category: selectedCategory.value || undefined,
        brand: selectedBrand.value || undefined,
        sort: selectedSort.value,
        page: currentPage.value,
        per_page: 12,
      })
      products.value = response.data
      meta.value = response.meta
    } catch (loadError) {
      error.value = getErrorMessage(loadError)
    } finally {
      loading.value = false
    }
  },
  { immediate: true, deep: true },
)

onMounted(async () => {
  try {
    filters.value = await getCatalogFilters()
  } catch {
    // filters are non-critical
  }
})

function applyFilter(key: 'category' | 'brand' | 'sort', value: string) {
  router.push({
    name: 'products',
    query: {
      ...route.query,
      [key]: value || undefined,
      page: undefined,
    },
  })
}

function submitSearch() {
  router.push({
    name: 'products',
    query: {
      ...route.query,
      q: searchTerm.value.trim() || undefined,
      page: undefined,
    },
  })
}

function clearFilters() {
  router.push({ name: 'products' })
}

function removeSearch() {
  const query = { ...route.query }
  delete query.q
  delete query.page
  router.push({ name: 'products', query })
}

function changePage(page: number) {
  router.push({ name: 'products', query: { ...route.query, page: String(page) } })
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function onBrandChange(event: Event) {
  applyFilter('brand', (event.target as HTMLSelectElement).value)
}

function onSortChange(event: Event) {
  applyFilter('sort', (event.target as HTMLSelectElement).value)
}

function addToCart(product: Product) {
  if (auth.isAdmin) {
    toast.info('Contas administrativas não realizam pedidos. Entre com uma conta de cliente.')
    return
  }

  cart.add(product)
  toast.success('Produto adicionado ao carrinho.')

  if (auth.isAuthenticated) {
    cart.sync().catch(() => undefined)
  }
}
</script>

<template>
  <div class="container-page py-8">
    <div class="mb-6 flex flex-col gap-4">
      <div>
        <p class="text-xs font-semibold tracking-widest text-emerald-700 uppercase">Catálogo</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{{ heading }}</h1>
        <p class="mt-1 text-sm text-slate-500">
          {{ meta.total }} produtos encontrados
          <span v-if="auth.isAuthenticated && auth.customer"> · preços da tabela {{ auth.customer.price_table.code }}</span>
        </p>
      </div>

      <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <form class="flex flex-1 gap-2" @submit.prevent="submitSearch">
          <div class="relative flex-1">
            <Search class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-slate-400" />
            <input v-model="searchTerm" type="search" class="input pl-10" placeholder="Buscar por nome, marca, SKU ou EAN..." />
          </div>
          <button type="submit" class="btn btn-dark shrink-0">Buscar</button>
        </form>

        <div class="flex items-center gap-2">
          <button type="button" class="btn btn-outline lg:hidden" @click="mobileFilters = !mobileFilters">
            <Filter class="size-4" /> Filtros
            <span v-if="activeFilterCount" class="badge bg-emerald-100 text-emerald-700">{{ activeFilterCount }}</span>
          </button>

          <div class="relative">
            <select class="input h-11 appearance-none pr-9" :value="selectedSort" @change="onSortChange">
              <option value="name_asc">Nome A-Z</option>
              <option value="name_desc">Nome Z-A</option>
              <option value="recent">Mais recentes</option>
            </select>
            <ChevronDown class="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-slate-400" />
          </div>
        </div>
      </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[240px_1fr]">
      <aside class="card h-fit p-5" :class="mobileFilters ? 'block' : 'hidden lg:block'">
        <div class="mb-4 flex items-center justify-between">
          <p class="flex items-center gap-2 text-sm font-semibold text-slate-900">
            <SlidersHorizontal class="size-4 text-slate-400" /> Filtros
          </p>
          <button v-if="activeFilterCount || route.query.q" type="button" class="text-xs font-semibold text-emerald-700 hover:underline" @click="clearFilters">
            Limpar
          </button>
        </div>

        <div class="border-t border-slate-100 py-4">
          <p class="mb-2 text-[11px] font-semibold tracking-widest text-slate-400 uppercase">Categorias</p>
          <div class="space-y-0.5">
            <button
              type="button"
              class="flex w-full items-center justify-between rounded-lg px-2.5 py-2 text-left text-sm transition"
              :class="!selectedCategory ? 'bg-emerald-50 font-semibold text-emerald-800' : 'text-slate-600 hover:bg-slate-50'"
              @click="applyFilter('category', '')"
            >
              Todas
            </button>
            <button
              v-for="category in filters.categories"
              :key="category"
              type="button"
              class="flex w-full items-center justify-between rounded-lg px-2.5 py-2 text-left text-sm transition"
              :class="selectedCategory === category ? 'bg-emerald-50 font-semibold text-emerald-800' : 'text-slate-600 hover:bg-slate-50'"
              @click="applyFilter('category', category)"
            >
              {{ category }}
            </button>
          </div>
        </div>

        <div class="border-t border-slate-100 py-4">
          <p class="mb-2 text-[11px] font-semibold tracking-widest text-slate-400 uppercase">Marca</p>
          <div class="relative">
            <select class="input appearance-none pr-9" :value="selectedBrand" @change="onBrandChange">
              <option value="">Todas as marcas</option>
              <option v-for="brand in filters.brands" :key="brand" :value="brand">{{ brand }}</option>
            </select>
            <ChevronDown class="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-slate-400" />
          </div>
        </div>

        <div v-if="selectedCategory || selectedBrand || route.query.q" class="border-t border-slate-100 pt-4">
          <p class="mb-2 text-[11px] font-semibold tracking-widest text-slate-400 uppercase">Filtros ativos</p>
          <div class="flex flex-wrap gap-1.5">
            <button
              v-if="route.query.q"
              type="button"
              class="badge bg-slate-100 text-slate-600 hover:bg-slate-200"
              @click="removeSearch"
            >
              “{{ route.query.q }}” <X class="size-3" />
            </button>
            <button v-if="selectedCategory" type="button" class="badge bg-slate-100 text-slate-600 hover:bg-slate-200" @click="applyFilter('category', '')">
              {{ selectedCategory }} <X class="size-3" />
            </button>
            <button v-if="selectedBrand" type="button" class="badge bg-slate-100 text-slate-600 hover:bg-slate-200" @click="applyFilter('brand', '')">
              {{ selectedBrand }} <X class="size-3" />
            </button>
          </div>
        </div>
      </aside>

      <div>
        <LoadingSpinner v-if="loading" label="Buscando produtos..." />

        <div v-else-if="error" class="card px-6 py-10 text-center text-sm font-medium text-red-600">{{ error }}</div>

        <EmptyState
          v-else-if="!products.length"
          title="Nenhum produto encontrado"
          description="Tente ajustar a busca ou remover alguns filtros."
        >
          <template #action>
            <button type="button" class="btn btn-primary" @click="clearFilters">Limpar filtros</button>
          </template>
        </EmptyState>

        <template v-else>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <ProductCard v-for="product in products" :key="product.id" :product="product" @add="addToCart" />
          </div>

          <div class="mt-8">
            <PaginationBar
              :current-page="meta.current_page"
              :last-page="meta.last_page"
              :total="meta.total"
              @change="changePage"
            />
          </div>
        </template>
      </div>
    </div>
  </div>
</template>
