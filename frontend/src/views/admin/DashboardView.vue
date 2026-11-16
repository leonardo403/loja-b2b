<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import {
  ArrowRight,
  Boxes,
  ClipboardList,
  Clock,
  Package,
  RefreshCw,
  TriangleAlert,
  Users,
  Wallet,
} from '@lucide/vue'
import EmptyState from '@/components/EmptyState.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import { getErrorMessage } from '@/services/api'
import { getDashboard } from '@/services/admin.service'
import type { DashboardStats } from '@/types'
import { formatCurrency, formatDateTime } from '@/utils/format'

const stats = ref<DashboardStats | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)

const cards = computed(() => {
  if (!stats.value) return []

  const totals = stats.value.totals

  return [
    { label: 'Produtos ativos', value: totals.products, icon: Boxes, tone: 'bg-sky-50 text-sky-600' },
    { label: 'Clientes', value: totals.customers, icon: Users, tone: 'bg-violet-50 text-violet-600' },
    { label: 'Pedidos', value: totals.orders, icon: ClipboardList, tone: 'bg-emerald-50 text-emerald-600' },
    { label: 'Pedidos abertos', value: totals.open_orders, icon: Clock, tone: 'bg-amber-50 text-amber-600' },
    { label: 'Faturamento', value: formatCurrency(totals.revenue), icon: Wallet, tone: 'bg-emerald-50 text-emerald-600' },
    { label: 'Estoque baixo', value: totals.low_stock, icon: TriangleAlert, tone: 'bg-rose-50 text-rose-600' },
  ]
})

async function load() {
  loading.value = true
  error.value = null

  try {
    stats.value = await getDashboard()
  } catch (loadError) {
    error.value = getErrorMessage(loadError)
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Visão geral</h1>
        <p class="mt-1 text-sm text-slate-500">Resumo da operação, pedidos recentes e alertas de estoque.</p>
      </div>
      <button type="button" class="btn btn-outline btn-sm" :disabled="loading" @click="load">
        <RefreshCw class="size-4" :class="loading ? 'animate-spin' : ''" /> Atualizar
      </button>
    </div>

    <LoadingSpinner v-if="loading && !stats" label="Carregando indicadores..." />

    <div v-else-if="error" class="card flex flex-col items-center gap-3 px-6 py-12 text-center">
      <TriangleAlert class="size-9 text-amber-500" />
      <p class="text-sm font-medium text-slate-700">{{ error }}</p>
      <button type="button" class="btn btn-primary btn-sm" @click="load">Tentar novamente</button>
    </div>

    <template v-else-if="stats">
      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <div v-for="card in cards" :key="card.label" class="card flex items-center gap-4 p-5">
          <div class="flex size-11 shrink-0 items-center justify-center rounded-xl" :class="card.tone">
            <component :is="card.icon" class="size-5" />
          </div>
          <div class="min-w-0">
            <p class="text-[11px] font-semibold tracking-widest text-slate-400 uppercase">{{ card.label }}</p>
            <p class="mt-0.5 truncate text-xl font-bold text-slate-900">{{ card.value }}</p>
          </div>
        </div>
      </div>

      <div class="grid gap-5 xl:grid-cols-[1.4fr_1fr]">
        <div class="card overflow-hidden">
          <div class="flex items-center justify-between border-b border-slate-200 px-5 py-3.5">
            <h2 class="text-sm font-semibold text-slate-900">Pedidos recentes</h2>
            <RouterLink :to="{ name: 'admin-orders' }" class="flex items-center gap-1 text-xs font-semibold text-emerald-700 hover:underline">
              Ver todos <ArrowRight class="size-3.5" />
            </RouterLink>
          </div>

          <EmptyState
            v-if="!stats.recent_orders.length"
            title="Nenhum pedido ainda"
            description="Os pedidos aparecerão aqui assim que os clientes comprarem."
          />

          <div v-else class="overflow-x-auto">
            <table class="w-full min-w-[520px]">
              <thead class="border-b border-slate-100 bg-slate-50">
                <tr>
                  <th class="table-th">Pedido</th>
                  <th class="table-th">Cliente</th>
                  <th class="table-th">Status</th>
                  <th class="table-th text-right">Total</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="order in stats.recent_orders" :key="order.id" class="hover:bg-slate-50/70">
                  <td class="table-td">
                    <p class="font-semibold text-slate-900">#{{ order.number }}</p>
                    <p class="text-[11px] text-slate-400">{{ formatDateTime(order.created_at) }}</p>
                  </td>
                  <td class="table-td">
                    <p class="font-medium text-slate-800">{{ order.customer?.trade_name ?? '—' }}</p>
                    <p class="text-[11px] text-slate-400">{{ order.customer?.price_table.code ?? '—' }}</p>
                  </td>
                  <td class="table-td"><StatusBadge :status="order.status" :label="order.status_label" /></td>
                  <td class="table-td text-right font-semibold whitespace-nowrap text-slate-900">
                    {{ formatCurrency(order.total) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="card overflow-hidden">
          <div class="flex items-center justify-between border-b border-slate-200 px-5 py-3.5">
            <h2 class="text-sm font-semibold text-slate-900">Estoque baixo</h2>
            <RouterLink :to="{ name: 'admin-products' }" class="flex items-center gap-1 text-xs font-semibold text-emerald-700 hover:underline">
              Gerenciar <ArrowRight class="size-3.5" />
            </RouterLink>
          </div>

          <div v-if="!stats.low_stock.length" class="flex flex-col items-center gap-2 px-5 py-10 text-center">
            <Package class="size-8 text-emerald-500" />
            <p class="text-sm font-medium text-slate-700">Todos os estoques estão saudáveis.</p>
          </div>

          <ul v-else class="divide-y divide-slate-100">
            <li v-for="item in stats.low_stock" :key="item.id" class="flex items-center justify-between gap-3 px-5 py-3">
              <div class="min-w-0">
                <p class="truncate text-sm font-medium text-slate-800">{{ item.name }}</p>
                <p class="text-[11px] text-slate-400">{{ item.sku }} · {{ item.category ?? 'Geral' }}</p>
              </div>
              <span
                class="badge shrink-0"
                :class="item.quantity <= 5 ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-800'"
              >
                {{ item.quantity }} un.
              </span>
            </li>
          </ul>
        </div>
      </div>
    </template>
  </div>
</template>
