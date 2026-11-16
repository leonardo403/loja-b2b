<script setup lang="ts">
import { onMounted, ref } from 'vue'
import {
  Building2,
  CalendarDays,
  CreditCard,
  Eye,
  Loader2,
  Search,
  TriangleAlert,
} from '@lucide/vue'
import AppModal from '@/components/AppModal.vue'
import EmptyState from '@/components/EmptyState.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import { getErrorMessage } from '@/services/api'
import { getOrder, listOrders, updateOrderStatus } from '@/services/admin.service'
import { useToastStore } from '@/stores/toast'
import type { Order, OrderStatus, PaginationMeta } from '@/types'
import { formatCnpj, formatCurrency, formatDateTime } from '@/utils/format'

const toast = useToastStore()

const orders = ref<Order[]>([])
const meta = ref<PaginationMeta>({ current_page: 1, last_page: 1, per_page: 15, total: 0, from: null, to: null })
const loading = ref(true)
const error = ref<string | null>(null)
const search = ref('')
const statusFilter = ref('')
const page = ref(1)

const modalOpen = ref(false)
const selected = ref<Order | null>(null)
const loadingOrder = ref(false)
const selectedStatus = ref<OrderStatus>('pending')
const updating = ref(false)
const modalError = ref<string | null>(null)

const statusOptions: Array<{ value: OrderStatus; label: string }> = [
  { value: 'pending', label: 'Aguardando' },
  { value: 'confirmed', label: 'Confirmado' },
  { value: 'shipped', label: 'Enviado' },
  { value: 'canceled', label: 'Cancelado' },
]

async function load() {
  loading.value = true
  error.value = null

  try {
    const response = await listOrders({
      search: search.value.trim() || undefined,
      status: statusFilter.value || undefined,
      page: page.value,
      per_page: 15,
    })
    orders.value = response.data
    meta.value = response.meta
  } catch (loadError) {
    error.value = getErrorMessage(loadError)
  } finally {
    loading.value = false
  }
}

onMounted(load)

function submitFilters() {
  page.value = 1
  load()
}

function changePage(next: number) {
  page.value = next
  load()
}

async function openOrder(order: Order) {
  modalOpen.value = true
  loadingOrder.value = true
  modalError.value = null
  selected.value = order
  selectedStatus.value = order.status

  try {
    selected.value = await getOrder(order.id)
    selectedStatus.value = selected.value.status
  } catch (loadError) {
    modalError.value = getErrorMessage(loadError)
  } finally {
    loadingOrder.value = false
  }
}

async function applyStatus() {
  if (!selected.value) return

  updating.value = true
  modalError.value = null

  try {
    selected.value = await updateOrderStatus(selected.value.id, selectedStatus.value)
    toast.success(`Pedido #${selected.value.number} atualizado para ${selected.value.status_label}.`)
    await load()
  } catch (updateError) {
    modalError.value = getErrorMessage(updateError)
  } finally {
    updating.value = false
  }
}

function totalUnits(order: Order) {
  return order.items.reduce((total, item) => total + item.quantity, 0)
}
</script>

<template>
  <div class="space-y-5">
    <div>
      <h1 class="text-2xl font-bold tracking-tight text-slate-900">Pedidos</h1>
      <p class="mt-1 text-sm text-slate-500">{{ meta.total }} pedidos registrados na loja.</p>
    </div>

    <div class="card p-4">
      <form class="flex flex-col gap-2 sm:flex-row sm:items-center" @submit.prevent="submitFilters">
        <div class="relative flex-1">
          <Search class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-slate-400" />
          <input v-model="search" type="search" class="input pl-10" placeholder="Buscar por número do pedido ou cliente..." />
        </div>
        <select v-model="statusFilter" class="input sm:w-52" @change="submitFilters">
          <option value="">Todos os status</option>
          <option v-for="option in statusOptions" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </select>
        <button type="submit" class="btn btn-dark shrink-0">Filtrar</button>
      </form>
    </div>

    <LoadingSpinner v-if="loading" label="Carregando pedidos..." />

    <div v-else-if="error" class="card flex flex-col items-center gap-3 px-6 py-12 text-center">
      <TriangleAlert class="size-9 text-amber-500" />
      <p class="text-sm font-medium text-slate-700">{{ error }}</p>
      <button type="button" class="btn btn-primary btn-sm" @click="load">Tentar novamente</button>
    </div>

    <EmptyState
      v-else-if="!orders.length"
      title="Nenhum pedido encontrado"
      description="Ajuste os filtros ou aguarde novos pedidos dos clientes."
    />

    <template v-else>
      <div class="card overflow-x-auto">
        <table class="w-full min-w-[880px]">
          <thead class="border-b border-slate-200 bg-slate-50">
            <tr>
              <th class="table-th">Pedido</th>
              <th class="table-th">Cliente</th>
              <th class="table-th">Data</th>
              <th class="table-th">Itens</th>
              <th class="table-th">Pagamento</th>
              <th class="table-th">Status</th>
              <th class="table-th text-right">Total</th>
              <th class="table-th text-right">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="order in orders" :key="order.id" class="hover:bg-slate-50/70">
              <td class="table-td">
                <p class="font-semibold text-slate-900">#{{ order.number }}</p>
              </td>
              <td class="table-td">
                <p class="max-w-48 truncate font-medium text-slate-800">{{ order.customer?.trade_name ?? '—' }}</p>
                <p class="text-[11px] text-slate-400">{{ order.customer ? formatCnpj(order.customer.cnpj) : '—' }}</p>
              </td>
              <td class="table-td whitespace-nowrap text-slate-500">{{ formatDateTime(order.created_at) }}</td>
              <td class="table-td text-slate-600">{{ order.items.length }} · {{ totalUnits(order) }} un.</td>
              <td class="table-td">
                <span class="badge bg-slate-100 text-slate-600">{{ order.payment_condition?.code ?? '—' }}</span>
              </td>
              <td class="table-td"><StatusBadge :status="order.status" :label="order.status_label" /></td>
              <td class="table-td text-right font-semibold whitespace-nowrap text-slate-900">
                {{ formatCurrency(order.total) }}
              </td>
              <td class="table-td">
                <div class="flex justify-end">
                  <button type="button" class="btn btn-outline btn-sm" @click="openOrder(order)">
                    <Eye class="size-3.5" /> Detalhes
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <PaginationBar
        :current-page="meta.current_page"
        :last-page="meta.last_page"
        :total="meta.total"
        @change="changePage"
      />
    </template>

    <AppModal
      :open="modalOpen"
      :title="selected ? `Pedido #${selected.number}` : 'Pedido'"
      :description="selected ? formatDateTime(selected.created_at) : ''"
      width="xl"
      @close="modalOpen = false"
    >
      <LoadingSpinner v-if="loadingOrder" label="Carregando pedido..." />

      <div v-else-if="selected">
        <div v-if="modalError" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
          {{ modalError }}
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-slate-50 p-4">
          <div class="flex items-center gap-3">
            <StatusBadge :status="selected.status" :label="selected.status_label" />
            <p class="text-xs text-slate-500">
              {{ selected.items.length }} itens · {{ totalUnits(selected) }} unidades
            </p>
          </div>

          <div class="flex items-center gap-2">
            <select v-model="selectedStatus" class="input h-10 w-40 py-1.5" :disabled="updating">
              <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </select>
            <button
              type="button"
              class="btn btn-dark btn-sm"
              :disabled="updating || selectedStatus === selected.status"
              @click="applyStatus"
            >
              <Loader2 v-if="updating" class="size-3.5 animate-spin" />
              Atualizar
            </button>
          </div>
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-2">
          <div class="rounded-xl border border-slate-200 p-4">
            <p class="flex items-center gap-1.5 text-[10px] font-semibold tracking-widest text-slate-400 uppercase">
              <Building2 class="size-3" /> Cliente
            </p>
            <p class="mt-1.5 text-sm font-semibold text-slate-900">{{ selected.customer?.trade_name ?? '—' }}</p>
            <p class="text-xs text-slate-500">{{ selected.customer?.legal_name ?? '' }}</p>
            <div class="mt-1.5 space-y-1 text-[11px] text-slate-500">
              <p>{{ selected.customer ? formatCnpj(selected.customer.cnpj) : '' }}</p>
              <p>{{ selected.customer?.city }} · {{ selected.customer?.state }}</p>
              <p>Tabela {{ selected.customer?.price_table.code ?? '—' }}</p>
            </div>
          </div>

          <div class="rounded-xl border border-slate-200 p-4">
            <p class="flex items-center gap-1.5 text-[10px] font-semibold tracking-widest text-slate-400 uppercase">
              <CreditCard class="size-3" /> Pagamento
            </p>
            <p class="mt-1.5 text-sm font-semibold text-slate-900">{{ selected.payment_condition?.code ?? '—' }}</p>
            <p class="text-xs text-slate-500">{{ selected.payment_condition?.description ?? '' }}</p>
            <div class="mt-1.5 space-y-1 text-[11px] text-slate-500">
              <p>Mínimo {{ formatCurrency(selected.payment_condition?.minimum_value) }}</p>
              <p v-if="(selected.payment_condition?.surcharge_percentage ?? 0) > 0">
                Acréscimo {{ selected.payment_condition?.surcharge_percentage }}%
              </p>
              <p>{{ selected.payment_condition?.is_term ? 'A prazo' : 'À vista' }}</p>
            </div>
          </div>
        </div>

        <div class="mt-4 overflow-hidden rounded-xl border border-slate-200">
          <table class="w-full">
            <thead class="border-b border-slate-200 bg-slate-50">
              <tr>
                <th class="table-th">Produto</th>
                <th class="table-th text-center">Qtd.</th>
                <th class="table-th text-right">Unitário</th>
                <th class="table-th text-right">Subtotal</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in selected.items" :key="item.product_id">
                <td class="table-td">
                  <p class="font-medium text-slate-900">{{ item.product_name }}</p>
                  <p class="text-[11px] text-slate-400">SKU {{ item.product_sku }}</p>
                </td>
                <td class="table-td text-center">{{ item.quantity }}</td>
                <td class="table-td text-right whitespace-nowrap">{{ formatCurrency(item.unit_price) }}</td>
                <td class="table-td text-right font-semibold whitespace-nowrap">{{ formatCurrency(item.subtotal) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-4 flex flex-col items-end gap-1 text-sm">
          <p class="text-slate-500">Subtotal: <span class="font-medium text-slate-800">{{ formatCurrency(selected.subtotal) }}</span></p>
          <p v-if="selected.savings > 0" class="text-emerald-700">Economia: − {{ formatCurrency(selected.savings) }}</p>
          <p v-if="selected.surcharge > 0" class="text-slate-500">Acréscimo: {{ formatCurrency(selected.surcharge) }}</p>
          <p class="text-lg font-bold text-slate-900">Total: {{ formatCurrency(selected.total) }}</p>
        </div>

        <div v-if="selected.notes" class="mt-4 rounded-xl bg-slate-50 p-4">
          <p class="flex items-center gap-1.5 text-[10px] font-semibold tracking-widest text-slate-400 uppercase">
            <CalendarDays class="size-3" /> Observações do cliente
          </p>
          <p class="mt-1.5 text-sm text-slate-600">{{ selected.notes }}</p>
        </div>

        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-[11px] text-amber-800">
          Cancelar um pedido devolve o estoque e libera o crédito utilizado pelo cliente.
        </div>
      </div>

      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="modalOpen = false">Fechar</button>
      </template>
    </AppModal>
  </div>
</template>
