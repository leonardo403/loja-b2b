<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  LayoutDashboard,
  LogIn,
  LogOut,
  Menu,
  Search,
  ShoppingCart,
  User,
  X,
} from '@lucide/vue'
import AppLogo from '@/components/AppLogo.vue'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'

const auth = useAuthStore()
const cart = useCartStore()
const router = useRouter()
const route = useRoute()

const search = ref('')
const mobileOpen = ref(false)

const company = computed(() => auth.customer)

watch(
  () => route.query.q,
  (value) => {
    search.value = typeof value === 'string' ? value : ''
  },
  { immediate: true },
)

watch(
  () => route.fullPath,
  () => {
    mobileOpen.value = false
  },
)

function submitSearch() {
  const query = search.value.trim()

  router.push({
    name: 'products',
    query: query ? { q: query } : {},
  })
}

async function logout() {
  await auth.logout()
  await cart.sync()
  router.push({ name: 'home' })
}
</script>

<template>
  <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="bg-slate-900 text-white">
      <div class="container-page flex h-9 items-center justify-between text-[11px] sm:text-xs">
        <p class="truncate text-slate-300">
          Preços e condições exclusivas para empresas · Compra mínima por tabela
        </p>
        <RouterLink v-if="auth.isAdmin" :to="{ name: 'admin-dashboard' }" class="hidden items-center gap-1.5 font-semibold text-emerald-400 hover:text-emerald-300 sm:flex">
          <LayoutDashboard class="size-3.5" />
          Painel administrativo
        </RouterLink>
      </div>
    </div>

    <div class="container-page flex h-16 items-center gap-4 lg:h-20">
      <RouterLink :to="{ name: 'home' }" class="shrink-0">
        <AppLogo />
      </RouterLink>

      <form class="hidden flex-1 items-center md:flex" @submit.prevent="submitSearch">
        <div class="relative w-full max-w-xl">
          <Search class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-slate-400" />
          <input
            v-model="search"
            type="search"
            class="input h-11 pl-10"
            placeholder="Busque por produto, marca, SKU ou EAN..."
            aria-label="Buscar produtos"
          />
        </div>
      </form>

      <nav class="ml-auto hidden items-center gap-1 lg:flex">
        <RouterLink :to="{ name: 'home' }" class="btn btn-ghost btn-sm">Início</RouterLink>
        <RouterLink :to="{ name: 'products' }" class="btn btn-ghost btn-sm">Produtos</RouterLink>
        <RouterLink v-if="auth.isAuthenticated && !auth.isAdmin" :to="{ name: 'orders' }" class="btn btn-ghost btn-sm">
          Meus pedidos
        </RouterLink>

        <RouterLink
          v-if="auth.isAuthenticated && !auth.isAdmin"
          :to="{ name: 'cart' }"
          class="relative ml-1 flex size-10 items-center justify-center rounded-lg text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
          aria-label="Carrinho"
        >
          <ShoppingCart class="size-5" />
          <span
            v-if="cart.itemCount > 0"
            class="absolute -top-0.5 -right-0.5 flex min-w-5 items-center justify-center rounded-full bg-emerald-600 px-1 text-[10px] font-bold text-white"
          >
            {{ cart.itemCount > 99 ? '99+' : cart.itemCount }}
          </span>
        </RouterLink>

        <div v-if="auth.isAuthenticated" class="ml-1 flex items-center gap-2 border-l border-slate-200 pl-3">
          <div class="text-right leading-tight">
            <p class="max-w-36 truncate text-xs font-semibold text-slate-800">{{ company?.trade_name ?? auth.user?.name }}</p>
            <p class="text-[10px] text-slate-400">{{ company?.price_table.code ?? 'Administrador' }}</p>
          </div>
          <button
            type="button"
            class="flex size-9 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-red-600"
            title="Sair"
            @click="logout"
          >
            <LogOut class="size-4" />
          </button>
        </div>

        <RouterLink v-else :to="{ name: 'login' }" class="btn btn-dark btn-sm ml-1">
          <LogIn class="size-4" />
          Entrar
        </RouterLink>
      </nav>

      <div class="ml-auto flex items-center gap-2 lg:hidden">
        <RouterLink
          v-if="auth.isAuthenticated && !auth.isAdmin"
          :to="{ name: 'cart' }"
          class="relative flex size-10 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100"
          aria-label="Carrinho"
        >
          <ShoppingCart class="size-5" />
          <span
            v-if="cart.itemCount > 0"
            class="absolute -top-0.5 -right-0.5 flex min-w-5 items-center justify-center rounded-full bg-emerald-600 px-1 text-[10px] font-bold text-white"
          >
            {{ cart.itemCount > 99 ? '99+' : cart.itemCount }}
          </span>
        </RouterLink>
        <button
          type="button"
          class="flex size-10 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100"
          aria-label="Menu"
          @click="mobileOpen = !mobileOpen"
        >
          <X v-if="mobileOpen" class="size-5" />
          <Menu v-else class="size-5" />
        </button>
      </div>
    </div>

    <div v-if="mobileOpen" class="border-t border-slate-200 bg-white lg:hidden">
      <div class="container-page space-y-3 py-4">
        <form @submit.prevent="submitSearch">
          <div class="relative">
            <Search class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-slate-400" />
            <input v-model="search" type="search" class="input pl-10" placeholder="Buscar produtos..." />
          </div>
        </form>

        <div class="grid gap-1">
          <RouterLink :to="{ name: 'home' }" class="btn btn-ghost justify-start">Início</RouterLink>
          <RouterLink :to="{ name: 'products' }" class="btn btn-ghost justify-start">Produtos</RouterLink>
          <RouterLink v-if="auth.isAuthenticated && !auth.isAdmin" :to="{ name: 'orders' }" class="btn btn-ghost justify-start">Meus pedidos</RouterLink>
          <RouterLink v-if="auth.isAdmin" :to="{ name: 'admin-dashboard' }" class="btn btn-ghost justify-start">Painel administrativo</RouterLink>
        </div>

        <div v-if="auth.isAuthenticated" class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2.5">
          <div class="flex items-center gap-2">
            <User class="size-4 text-slate-400" />
            <div class="leading-tight">
              <p class="text-xs font-semibold text-slate-800">{{ company?.trade_name ?? auth.user?.name }}</p>
              <p class="text-[10px] text-slate-400">{{ auth.user?.email }}</p>
            </div>
          </div>
          <button type="button" class="btn btn-outline btn-sm" @click="logout">
            <LogOut class="size-3.5" /> Sair
          </button>
        </div>

        <RouterLink v-else :to="{ name: 'login' }" class="btn btn-primary w-full">
          <LogIn class="size-4" /> Entrar
        </RouterLink>
      </div>
    </div>
  </header>
</template>
