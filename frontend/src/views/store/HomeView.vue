<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import {
  ArrowRight,
  BadgePercent,
  Building2,
  CheckCircle2,
  ChevronRight,
  CupSoda,
  Milk,
  Package,
  Search,
  ShoppingBasket,
  Sparkles,
  SprayCan,
  Tags,
  Truck,
  Wallet,
  type LucideIcon,
} from '@lucide/vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import PriceTag from '@/components/PriceTag.vue'
import ProductCard from '@/components/ProductCard.vue'
import { getErrorMessage } from '@/services/api'
import { getCatalogFilters, getFeaturedProducts } from '@/services/catalog.service'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import { useToastStore } from '@/stores/toast'
import type { CatalogFilters, Product } from '@/types'
import { categoryTheme } from '@/utils/categories'
import { formatCurrency } from '@/utils/format'

const auth = useAuthStore()
const cart = useCartStore()
const toast = useToastStore()
const router = useRouter()

const products = ref<Product[]>([])
const filters = ref<CatalogFilters>({ categories: [], brands: [] })
const loading = ref(true)
const loadError = ref<string | null>(null)
const search = ref('')

const categoryIcons: Record<string, LucideIcon> = {
  CupSoda,
  Milk,
  Sparkles,
  SprayCan,
  ShoppingBasket,
  Package,
}

const categoryDescriptions: Record<string, string> = {
  Bebidas: 'Refrigerantes, águas e sucos',
  'Frios e Laticínios': 'Leites, queijos e frios',
  Higiene: 'Cuidados pessoais',
  Limpeza: 'Produtos de limpeza',
  Mercearia: 'Alimentos e mantimentos',
}

const categories = computed(() =>
  filters.value.categories.map((category) => ({
    name: category,
    theme: categoryTheme(category),
    icon: categoryIcons[categoryTheme(category).icon] ?? Package,
    description: categoryDescriptions[category] ?? 'Confira os produtos',
  })),
)

const spotlight = computed(() => products.value.find((product) => product.price?.on_promotion) ?? products.value[0])

onMounted(async () => {
  try {
    const [featured, catalogFilters] = await Promise.all([
      getFeaturedProducts(8),
      getCatalogFilters(),
    ])
    products.value = featured
    filters.value = catalogFilters
  } catch (error) {
    loadError.value = getErrorMessage(error)
  } finally {
    loading.value = false
  }
})

function submitSearch() {
  router.push({ name: 'products', query: search.value.trim() ? { q: search.value.trim() } : {} })
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
  } else {
    toast.info('Entre para ver os preços da sua tabela e finalizar o pedido.')
  }
}

const benefits = [
  { icon: Tags, title: 'Preço por tabela', text: 'Cada empresa vê sua tabela de preços negociada.' },
  { icon: BadgePercent, title: 'Desconto progressivo', text: 'Preço de atacado ao atingir a quantidade mínima.' },
  { icon: Wallet, title: 'Crédito integrado', text: 'Limite disponível validado no fechamento do pedido.' },
  { icon: Truck, title: 'Condições flexíveis', text: 'PIX ou boleto com prazos e acréscimos definidos.' },
]

const steps = [
  { number: '01', title: 'Entre com seu CNPJ', text: 'Acesse a conta da sua empresa e veja sua tabela de preços.' },
  { number: '02', title: 'Monte seu pedido', text: 'Adicione produtos e aproveite as faixas de atacado.' },
  { number: '03', title: 'Escolha o pagamento', text: 'Selecione a condição e confirme dentro do seu limite de crédito.' },
]
</script>

<template>
  <div>
    <section class="relative overflow-hidden bg-slate-900 text-white">
      <div class="absolute -top-32 right-0 size-[32rem] rounded-full bg-emerald-500/10 blur-3xl" />
      <div class="absolute -bottom-40 -left-24 size-[30rem] rounded-full bg-emerald-500/5 blur-3xl" />

      <div class="container-page relative grid items-center gap-12 py-14 lg:grid-cols-[1.1fr_0.9fr] lg:py-20">
        <div>
          <span class="badge border border-emerald-500/30 bg-emerald-500/10 text-emerald-300">
            <Building2 class="size-3.5" />
            Comércio atacadista B2B
          </span>

          <h1 class="mt-5 max-w-2xl text-4xl leading-[1.08] font-bold tracking-tight sm:text-5xl">
            Abasteça sua empresa com
            <span class="text-emerald-400">preços exclusivos</span> por tabela.
          </h1>
          <p class="mt-5 max-w-xl text-base leading-relaxed text-slate-300">
            Mais de 500 itens em bebidas, mercearia, limpeza, higiene e frios. Faça login e compre com
            as condições negociadas para o seu CNPJ.
          </p>

          <form class="mt-8 flex max-w-xl flex-col gap-2 sm:flex-row" @submit.prevent="submitSearch">
            <div class="relative flex-1">
              <Search class="pointer-events-none absolute top-1/2 left-4 size-4 -translate-y-1/2 text-slate-400" />
              <input
                v-model="search"
                type="search"
                class="input h-12 border-transparent bg-white pl-11 text-slate-900"
                placeholder="O que sua empresa precisa hoje?"
              />
            </div>
            <button type="submit" class="btn btn-primary h-12 px-6">
              Buscar produtos
              <ArrowRight class="size-4" />
            </button>
          </form>

          <div class="mt-8 flex flex-wrap gap-x-8 gap-y-3 text-sm text-slate-400">
            <span class="flex items-center gap-2"><CheckCircle2 class="size-4 text-emerald-400" /> 500+ produtos</span>
            <span class="flex items-center gap-2"><CheckCircle2 class="size-4 text-emerald-400" /> 2 tabelas de preço</span>
            <span class="flex items-center gap-2"><CheckCircle2 class="size-4 text-emerald-400" /> Pedido 100% online</span>
          </div>
        </div>

        <div v-if="spotlight" class="relative">
          <div class="absolute inset-0 translate-x-3 translate-y-3 rounded-2xl border border-emerald-400/20" />
          <div class="relative rounded-2xl border border-white/10 bg-white/5 p-5 backdrop-blur">
            <div class="flex items-start justify-between gap-4">
              <div>
                <p class="text-[10px] font-semibold tracking-widest text-emerald-400 uppercase">
                  Oferta em destaque
                </p>
                <h2 class="mt-1 text-lg leading-snug font-semibold">{{ spotlight.name }}</h2>
                <p class="mt-1 text-xs text-slate-400">{{ spotlight.brand }} · {{ spotlight.category }}</p>
              </div>
              <span v-if="spotlight.price?.on_promotion" class="badge bg-rose-500 text-white">
                -{{ spotlight.price.discount_percentage }}%
              </span>
            </div>

            <div class="mt-5 rounded-xl bg-white p-4">
              <PriceTag :price="spotlight.price" size="lg" />
              <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3">
                <span class="text-xs text-slate-500">Estoque disponível</span>
                <span class="text-xs font-semibold text-slate-700">{{ spotlight.stock_quantity }} un.</span>
              </div>
            </div>

            <RouterLink
              :to="{ name: 'product', params: { id: spotlight.id } }"
              class="btn btn-primary mt-4 w-full"
            >
              Ver produto
              <ChevronRight class="size-4" />
            </RouterLink>
          </div>
        </div>
      </div>
    </section>

    <section v-if="auth.isAuthenticated && auth.customer" class="border-b border-slate-200 bg-white">
      <div class="container-page flex flex-col gap-4 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
          <div>
            <p class="text-[10px] font-semibold tracking-widest text-slate-400 uppercase">Sua empresa</p>
            <p class="font-semibold text-slate-900">{{ auth.customer.trade_name }}</p>
          </div>
          <div class="hidden h-8 w-px bg-slate-200 sm:block" />
          <div>
            <p class="text-[10px] font-semibold tracking-widest text-slate-400 uppercase">Tabela</p>
            <p class="font-semibold text-slate-900">{{ auth.customer.price_table.description }}</p>
          </div>
          <div class="hidden h-8 w-px bg-slate-200 sm:block" />
          <div>
            <p class="text-[10px] font-semibold tracking-widest text-slate-400 uppercase">Crédito disponível</p>
            <p class="font-semibold text-emerald-700">{{ formatCurrency(auth.customer.available_credit) }}</p>
          </div>
        </div>
        <RouterLink :to="{ name: 'orders' }" class="btn btn-outline btn-sm">
          Meus pedidos <ArrowRight class="size-3.5" />
        </RouterLink>
      </div>
    </section>

    <section class="container-page py-12">
      <div class="mb-6 flex items-end justify-between">
        <div>
          <p class="text-xs font-semibold tracking-widest text-emerald-700 uppercase">Categorias</p>
          <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Navegue por segmento</h2>
        </div>
        <RouterLink :to="{ name: 'products' }" class="hidden text-sm font-semibold text-emerald-700 hover:underline sm:flex sm:items-center sm:gap-1">
          Ver catálogo completo <ArrowRight class="size-4" />
        </RouterLink>
      </div>

      <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
        <RouterLink
          v-for="category in categories"
          :key="category.name"
          :to="{ name: 'products', query: { category: category.name } }"
          class="card group flex flex-col gap-3 p-4 transition hover:-translate-y-0.5 hover:shadow-md"
        >
          <div class="flex size-11 items-center justify-center rounded-lg bg-gradient-to-br" :class="category.theme.gradient">
            <component :is="category.icon" class="size-5" :class="category.theme.accent" />
          </div>
          <div>
            <p class="text-sm font-semibold text-slate-900 group-hover:text-emerald-700">{{ category.name }}</p>
            <p class="mt-0.5 text-[11px] leading-snug text-slate-500">{{ category.description }}</p>
          </div>
        </RouterLink>
      </div>
    </section>

    <section class="container-page pb-12">
      <div class="mb-6 flex items-end justify-between">
        <div>
          <p class="text-xs font-semibold tracking-widest text-emerald-700 uppercase">Selecionados</p>
          <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Destaques da semana</h2>
        </div>
        <RouterLink :to="{ name: 'products' }" class="flex items-center gap-1 text-sm font-semibold text-emerald-700 hover:underline">
          Ver todos <ArrowRight class="size-4" />
        </RouterLink>
      </div>

      <LoadingSpinner v-if="loading" label="Carregando catálogo..." />
      <div v-else-if="loadError" class="card px-6 py-10 text-center">
        <p class="text-sm font-medium text-red-600">{{ loadError }}</p>
      </div>
      <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <ProductCard v-for="product in products" :key="product.id" :product="product" @add="addToCart" />
      </div>
    </section>

    <section class="border-y border-slate-200 bg-white py-14">
      <div class="container-page">
        <div class="mb-8 max-w-2xl">
          <p class="text-xs font-semibold tracking-widest text-emerald-700 uppercase">Por que comprar aqui</p>
          <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Uma operação B2B de verdade</h2>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div v-for="benefit in benefits" :key="benefit.title" class="rounded-xl border border-slate-200 p-5">
            <div class="flex size-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
              <component :is="benefit.icon" class="size-5" />
            </div>
            <h3 class="mt-4 text-sm font-semibold text-slate-900">{{ benefit.title }}</h3>
            <p class="mt-1 text-sm leading-relaxed text-slate-500">{{ benefit.text }}</p>
          </div>
        </div>
      </div>
    </section>

    <section class="container-page py-14">
      <div class="grid gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:items-center">
        <div>
          <p class="text-xs font-semibold tracking-widest text-emerald-700 uppercase">Como funciona</p>
          <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Do login ao pedido em 3 passos</h2>
          <p class="mt-3 text-sm leading-relaxed text-slate-500">
            O catálogo e o carrinho calculam tudo automaticamente com a tabela da sua empresa, faixas
            de atacado, promoções e condições de pagamento.
          </p>

          <RouterLink v-if="!auth.isAuthenticated" :to="{ name: 'login' }" class="btn btn-dark mt-6">
            Acessar minha conta <ArrowRight class="size-4" />
          </RouterLink>
          <RouterLink v-else :to="{ name: 'products' }" class="btn btn-dark mt-6">
            Continuar comprando <ArrowRight class="size-4" />
          </RouterLink>
        </div>

        <div class="space-y-3">
          <div
            v-for="step in steps"
            :key="step.number"
            class="flex gap-4 rounded-xl border border-slate-200 bg-white p-5"
          >
            <span class="text-2xl font-bold text-emerald-200">{{ step.number }}</span>
            <div>
              <h3 class="text-sm font-semibold text-slate-900">{{ step.title }}</h3>
              <p class="mt-1 text-sm leading-relaxed text-slate-500">{{ step.text }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>
