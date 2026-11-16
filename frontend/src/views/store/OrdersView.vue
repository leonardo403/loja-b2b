<script setup lang="ts">
import { onMounted, ref } from 'vue'
import {
  ArrowRight,
  CalendarDays,
  ChevronRight,
  ClipboardList,
  CreditCard,
  Package,
  TriangleAlert,
} from '@lucide/vue'
import EmptyState from '@/components/EmptyState.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import { getErrorMessage } from '@/services/api'
import { getMyOrders } from '@/services/order.service'
import type { Order, PaginationMeta } from '@/types'
import { formatCurrency, formatDateTime } from '@/utils/format'

const orders = ref<Order[]>([])
const meta = ref<PaginationMeta>({ current_page: 1, last_page: 1, per_page: 10, total: 0, from: null, to: null })
const loading = ref(true)
const error = ref<string | null>(null)
const page = ref(1)

async function load() {
  loading.value = true
  error.value = null

  try {
    const response = await getMyOrders(page.value, 10)
    orders.value = response.data
    meta.value = response.meta
  } catch (loadError) {
    error.value = getErrorMessage(loadError)
  } finally {
    loading.value = false
  }
}

onMounted(load)

function changePage(next: number) {
  page.value = next
  window.scrollTo({ top: 0, behavior: 'smooth' })
  load()
}

function totalUnits(order: Order) {
  return order.items.reduce((total, item) => total + item.quantity, 0)
}
</script>

<template>
  <div class="container-page py-8">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
      <div>
        <p class="text-xs font-semibold tracking-widest text-emerald-700 uppercase">Histórico</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Meus pedidos</h1>
        <p class="mt-1 text-sm text-slate-500">
          {{ meta.total }} {{ meta.total === 1 ? 'pedido registrado' : 'pedidos registrados' }}
        </p>
      </div>
      <RouterLink :to="{ name: 'products' }" class="btn btn-outline btn-sm">
        <Package class="size-4" /> Novo pedido
      </RouterLink>
    </div>

    <LoadingSpinner v-if="loading" label="Carregando pedidos..." />

    <div v-else-if="error" class="card flex flex-col items-center gap-3 px-6 py-12 text-center">
      <TriangleAlert class="size-9 text-amber-500" />
      <p class="text-sm font-medium text-slate-700">{{ error }}</p>
      <button type="button" class="btn btn-primary btn-sm" @click="load">Tentar novamente</button>
    </div>

    <EmptyState
      v-else-if="!orders.length"
      title="Nenhum pedido ainda"
      description="Quando você finalizar sua primeira compra, ela aparecerá aqui com todos os detalhes."
    >
      <template #action>
        <RouterLink :to="{ name: 'products' }" class="btn btn-primary">
          Explorar catálogo <ArrowRight class="size-4" />
        </RouterLink>
      </template>
    </EmptyState>

    <template v-else>
      <div class="hidden overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm md:block">
        <table class="w-full">
          <thead class="border-b border-slate-200 bg-slate-50">
            <tr>
              <th class="table-th">Pedido</th>
              <th class="table-th">Data</th>
              <th class="table-th">Itens</th>
              <th class="table-th">Pagamento</th>
              <th class="table-th">Status</th>
              <th class="table-th text-right">Total</th>
              <th class="table-th" />
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="order in orders" :key="order.id" class="transition hover:bg-slate-50/70">
              <td class="table-td">
                <RouterLink :to="{ name: 'order', params: { id: order.id } }" class="font-semibold text-slate-900 hover:text-emerald-700">
                  #{{ order.number }}
                </RouterLink>
              </td>
              <td class="table-td whitespace-nowrap text-slate-500">{{ formatDateTime(order.created_at) }}</td>
              <td class="table-td">
                <span class="text-slate-700">{{ order.items.length }} itens</span>
                <span class="text-slate-400"> · {{ totalUnits(order) }} un.</span>
              </td>
              <td class="table-td">
                <span class="badge bg-slate-100 text-slate-600">{{ order.payment_condition?.code ?? '—' }}</span>
              </td>
              <td class="table-td"><StatusBadge :status="order.status" :label="order.status_label" /></td>
              <td class="table-td text-right font-semibold whitespace-nowrap text-slate-900">
                {{ formatCurrency(order.total) }}
              </td>
              <td class="table-td text-right">
                <RouterLink :to="{ name: 'order', params: { id: order.id } }" class="inline-flex text-slate-400 hover:text-emerald-700">
                  <ChevronRight class="size-4" />
                </RouterLink>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="space-y-3 md:hidden">
        <RouterLink
          v-for="order in orders"
          :key="order.id"
          :to="{ name: 'order', params: { id: order.id } }"
          class="card block p-4 transition hover:shadow-md"
        >
          <div class="flex items-start justify-between gap-3">
            <div>
              <p class="text-sm font-bold text-slate-900">#{{ order.number }}</p>
              <p class="mt-0.5 flex items-center gap-1 text-[11px] text-slate-400">
                <CalendarDays class="size-3" /> {{ formatDateTime(order.created_at) }}
              </p>
            </div>
            <StatusBadge :status="order.status" :label="order.status_label" />
          </div>

          <div class="mt-3 flex items-end justify-between border-t border-slate-100 pt-3">
            <div class="space-y-0.5 text-[11px] text-slate-500">
              <p class="flex items-center gap-1">
                <ClipboardList class="size-3" /> {{ order.items.length }} itens · {{ totalUnits(order) }} un.
              </p>
              <p class="flex items-center gap-1">
                <CreditCard class="size-3" /> {{ order.payment_condition?.code ?? '—' }}
              </p>
            </div>
            <p class="text-base font-bold text-slate-900">{{ formatCurrency(order.total) }}</p>
          </div>
        </RouterLink>
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
</template>
