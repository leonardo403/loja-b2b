<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import {
  BadgePercent,
  ClipboardList,
  CreditCard,
  ExternalLink,
  LayoutDashboard,
  LogOut,
  Menu,
  Package,
  Tags,
  Users,
  X,
} from '@lucide/vue'
import AppLogo from '@/components/AppLogo.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const open = ref(false)

const navigation = [
  { name: 'admin-dashboard', label: 'Dashboard', icon: LayoutDashboard },
  { name: 'admin-products', label: 'Produtos', icon: Package },
  { name: 'admin-customers', label: 'Clientes', icon: Users },
  { name: 'admin-price-tables', label: 'Tabelas de preço', icon: Tags },
  { name: 'admin-promotions', label: 'Promoções', icon: BadgePercent },
  { name: 'admin-payment-conditions', label: 'Pagamentos', icon: CreditCard },
  { name: 'admin-orders', label: 'Pedidos', icon: ClipboardList },
]

async function logout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="min-h-screen bg-slate-100">
    <aside
      class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-slate-900 text-slate-300 transition-transform duration-200 lg:translate-x-0"
      :class="open ? 'translate-x-0' : '-translate-x-full'"
    >
      <div class="flex h-20 items-center justify-between border-b border-slate-800 px-5">
        <AppLogo light />
        <button type="button" class="text-slate-400 lg:hidden" @click="open = false">
          <X class="size-5" />
        </button>
      </div>

      <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-5">
        <p class="px-3 pb-2 text-[10px] font-semibold tracking-widest text-slate-500 uppercase">Gestão</p>
        <RouterLink
          v-for="item in navigation"
          :key="item.name"
          :to="{ name: item.name }"
          class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition"
          :class="
            $route.name === item.name
              ? 'bg-emerald-600 text-white shadow-sm'
              : 'text-slate-400 hover:bg-slate-800 hover:text-white'
          "
          @click="open = false"
        >
          <component :is="item.icon" class="size-4.5 shrink-0" />
          {{ item.label }}
        </RouterLink>
      </nav>

      <div class="border-t border-slate-800 p-3">
        <RouterLink :to="{ name: 'home' }" target="_blank" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-slate-400 transition hover:bg-slate-800 hover:text-white">
          <ExternalLink class="size-4.5" />
          Ver loja
        </RouterLink>
        <button type="button" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-slate-400 transition hover:bg-slate-800 hover:text-red-400" @click="logout">
          <LogOut class="size-4.5" />
          Sair
        </button>
      </div>
    </aside>

    <div v-if="open" class="fixed inset-0 z-40 bg-slate-900/50 lg:hidden" @click="open = false" />

    <div class="lg:pl-64">
      <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 sm:px-6">
        <div class="flex items-center gap-3">
          <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden" @click="open = true">
            <Menu class="size-5" />
          </button>
          <div>
            <p class="text-sm font-semibold text-slate-900">{{ $route.meta.title ?? 'Painel' }}</p>
            <p class="text-[11px] text-slate-400">Administração da loja</p>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <div class="text-right leading-tight">
            <p class="text-xs font-semibold text-slate-800">{{ auth.user?.name }}</p>
            <p class="text-[10px] text-slate-400">{{ auth.user?.email }}</p>
          </div>
          <div class="flex size-9 items-center justify-center rounded-full bg-slate-900 text-xs font-bold text-white">
            {{ auth.user?.name?.charAt(0).toUpperCase() }}
          </div>
        </div>
      </header>

      <main class="p-4 sm:p-6 lg:p-8">
        <RouterView v-slot="{ Component }">
          <Transition name="fade" mode="out-in">
            <component :is="Component" />
          </Transition>
        </RouterView>
      </main>
    </div>
  </div>
</template>
