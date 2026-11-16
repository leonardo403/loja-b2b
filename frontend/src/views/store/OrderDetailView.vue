<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import {
  ArrowLeft,
  Building2,
  CalendarDays,
  Check,
  CheckCircle2,
  Clock,
  CreditCard,
  FileText,
  Hash,
  MapPin,
  Package,
  Printer,
  TriangleAlert,
  Truck,
} from '@lucide/vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import { getErrorMessage } from '@/services/api'
import { getMyOrder } from '@/services/order.service'
import type { Order, OrderStatus } from '@/types'
import { formatCnpj, formatCurrency, formatDateTime } from '@/utils/format'

const route = useRoute()

const order = ref<Order | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)

const isNew = computed(() => route.query.novo === '1')
const totalUnits = computed(
  () => order.value?.items.reduce((total, item) => total + item.quantity, 0) ?? 0,
)

const steps: Array<{ status: OrderStatus; label: string; icon: typeof Clock }> = [
  { status: 'pending', label: 'Pedido recebido', icon: Clock },
  { status: 'confirmed', label: 'Pedido confirmado', icon: CheckCircle2 },
  { status: 'shipped', label: 'Enviado', icon: Truck },
]

const currentStep = computed(() => {
  if (!order.value) return -1

  return steps.findIndex((step) => step.status === order.value?.status)
})

const canceled = computed(() => order.value?.status === 'canceled')

watch(
  () => route.params.id,
  async (id) => {
    loading.value = true
    error.value = null
    order.value = null

    try {
      order.value = await getMyOrder(String(id))
    } catch (loadError) {
      error.value = getErrorMessage(loadError)
    } finally {
      loading.value = false
    }
  },
  { immediate: true },
)

function stepState(index: number) {
  if (canceled.value) return 'canceled'
  if (index < currentStep.value) return 'done'
  if (index === currentStep.value) return 'current'

  return 'upcoming'
}

function printOrder() {
  window.print()
}
</script>

<template>
  <div class="container-page py-8">
    <RouterLink :to="{ name: 'orders' }" class="btn btn-ghost btn-sm mb-4 -ml-2">
      <ArrowLeft class="size-4" /> Meus pedidos
    </RouterLink>

    <LoadingSpinner v-if="loading" label="Carregando pedido..." />

    <div v-else-if="error" class="card flex flex-col items-center gap-3 px-6 py-14 text-center">
      <TriangleAlert class="size-10 text-amber-500" />
      <div>
        <h1 class="text-lg font-semibold text-slate-900">Pedido não encontrado</h1>
        <p class="mt-1 text-sm text-slate-500">{{ error }}</p>
      </div>
      <RouterLink :to="{ name: 'orders' }" class="btn btn-primary">Voltar aos pedidos</RouterLink>
    </div>

    <template v-else-if="order">
      <div
        v-if="isNew"
        class="mb-5 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3.5"
      >
        <CheckCircle2 class="mt-0.5 size-5 shrink-0 text-emerald-600" />
        <div>
          <p class="text-sm font-semibold text-emerald-900">Pedido realizado com sucesso!</p>
          <p class="mt-0.5 text-xs text-emerald-800">
            Recebemos seu pedido e ele já está disponível no histórico. A equipe comercial fará a
            confirmação e o faturamento.
          </p>
        </div>
      </div>

      <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
          <div class="flex flex-wrap items-center gap-2">
            <p class="text-xs font-semibold tracking-widest text-emerald-700 uppercase">Pedido</p>
            <StatusBadge :status="order.status" :label="order.status_label" />
          </div>
          <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">#{{ order.number }}</h1>
          <p class="mt-1 flex items-center gap-1.5 text-sm text-slate-500">
            <CalendarDays class="size-4" /> {{ formatDateTime(order.created_at) }}
          </p>
        </div>

        <div class="flex gap-2 print:hidden">
          <button type="button" class="btn btn-outline btn-sm" @click="printOrder">
            <Printer class="size-4" /> Imprimir
          </button>
          <RouterLink :to="{ name: 'products' }" class="btn btn-primary btn-sm">
            <Package class="size-4" /> Comprar novamente
          </RouterLink>
        </div>
      </div>

      <div class="grid gap-5 lg:grid-cols-[1fr_340px]">
        <div class="space-y-5">
          <div class="card p-5">
            <h2 class="text-sm font-semibold text-slate-900">Acompanhamento</h2>

            <div v-if="canceled" class="mt-3 flex items-center gap-2 rounded-lg bg-slate-100 px-3 py-2.5 text-sm text-slate-600">
              <TriangleAlert class="size-4 text-slate-500" />
              Este pedido foi cancelado. O estoque e o crédito foram devolvidos.
            </div>

            <div v-else class="mt-4 grid gap-3 sm:grid-cols-3">
              <div
                v-for="(step, index) in steps"
                :key="step.status"
                class="flex items-center gap-2.5 rounded-lg border p-3"
                :class="
                  stepState(index) === 'current'
                    ? 'border-emerald-500 bg-emerald-50'
                    : stepState(index) === 'done'
                      ? 'border-emerald-200 bg-white'
                      : 'border-slate-200 bg-slate-50'
                "
              >
                <div
                  class="flex size-8 shrink-0 items-center justify-center rounded-full"
                  :class="
                    stepState(index) === 'current'
                      ? 'bg-emerald-600 text-white'
                      : stepState(index) === 'done'
                        ? 'bg-emerald-100 text-emerald-700'
                        : 'bg-slate-200 text-slate-400'
                  "
                >
                  <Check v-if="stepState(index) === 'done'" class="size-4" />
                  <component :is="step.icon" v-else class="size-4" />
                </div>
                <p
                  class="text-xs font-medium"
                  :class="stepState(index) === 'upcoming' ? 'text-slate-400' : 'text-slate-800'"
                >
                  {{ step.label }}
                </p>
              </div>
            </div>
          </div>

          <div class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-3.5">
              <h2 class="text-sm font-semibold text-slate-900">Itens do pedido</h2>
              <span class="text-xs text-slate-500">{{ order.items.length }} itens · {{ totalUnits }} un.</span>
            </div>

            <div class="overflow-x-auto">
              <table class="w-full min-w-[560px]">
                <thead class="border-b border-slate-100 bg-slate-50">
                  <tr>
                    <th class="table-th">Produto</th>
                    <th class="table-th text-center">Qtd.</th>
                    <th class="table-th text-right">Unitário</th>
                    <th class="table-th text-right">Subtotal</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="item in order.items" :key="item.product_id">
                    <td class="table-td">
                      <p class="font-medium text-slate-900">{{ item.product_name }}</p>
                      <div class="mt-1 flex flex-wrap items-center gap-1.5">
                        <span class="text-[11px] text-slate-400">SKU {{ item.product_sku }}</span>
                        <span v-if="item.wholesale_applied" class="badge bg-emerald-100 text-emerald-700">Atacado</span>
                        <span v-if="item.discount_percentage > 0" class="badge bg-rose-100 text-rose-700">
                          −{{ item.discount_percentage }}%
                        </span>
                      </div>
                    </td>
                    <td class="table-td text-center font-medium">{{ item.quantity }}</td>
                    <td class="table-td text-right whitespace-nowrap">
                      <span class="font-medium text-slate-800">{{ formatCurrency(item.unit_price) }}</span>
                      <span v-if="item.unit_price < item.list_price" class="ml-1 text-[11px] text-slate-400 line-through">
                        {{ formatCurrency(item.list_price) }}
                      </span>
                    </td>
                    <td class="table-td text-right font-semibold whitespace-nowrap text-slate-900">
                      {{ formatCurrency(item.subtotal) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div v-if="order.notes" class="card p-5">
            <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900">
              <FileText class="size-4 text-slate-400" /> Observações
            </h2>
            <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ order.notes }}</p>
          </div>
        </div>

        <aside class="space-y-4">
          <div class="card p-5">
            <h2 class="text-sm font-semibold text-slate-900">Resumo</h2>
            <dl class="mt-3 space-y-2.5 text-sm">
              <div class="flex items-center justify-between">
                <dt class="text-slate-500">Subtotal</dt>
                <dd class="font-medium text-slate-800">{{ formatCurrency(order.subtotal) }}</dd>
              </div>
              <div v-if="order.savings > 0" class="flex items-center justify-between text-emerald-700">
                <dt>Economia</dt>
                <dd class="font-semibold">− {{ formatCurrency(order.savings) }}</dd>
              </div>
              <div v-if="order.surcharge > 0" class="flex items-center justify-between">
                <dt class="text-slate-500">Acréscimo</dt>
                <dd class="font-medium text-slate-800">{{ formatCurrency(order.surcharge) }}</dd>
              </div>
            </dl>
            <div class="mt-3 flex items-end justify-between border-t border-slate-200 pt-3">
              <span class="text-sm font-semibold text-slate-900">Total</span>
              <span class="text-xl font-bold tracking-tight text-emerald-700">{{ formatCurrency(order.total) }}</span>
            </div>
          </div>

          <div v-if="order.payment_condition" class="card p-5">
            <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900">
              <CreditCard class="size-4 text-slate-400" /> Pagamento
            </h2>
            <p class="mt-2 text-sm font-semibold text-slate-800">{{ order.payment_condition.code }}</p>
            <p class="mt-0.5 text-xs leading-snug text-slate-500">{{ order.payment_condition.description }}</p>
            <div class="mt-3 space-y-1 text-[11px] text-slate-500">
              <p>Mínimo: {{ formatCurrency(order.payment_condition.minimum_value) }}</p>
              <p v-if="order.payment_condition.surcharge_percentage > 0">
                Acréscimo: {{ order.payment_condition.surcharge_percentage }}%
              </p>
              <p>{{ order.payment_condition.is_term ? 'Pagamento a prazo' : 'Pagamento à vista' }}</p>
            </div>
          </div>

          <div v-if="order.customer" class="card p-5">
            <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900">
              <Building2 class="size-4 text-slate-400" /> Empresa
            </h2>
            <p class="mt-2 text-sm font-semibold text-slate-800">{{ order.customer.trade_name }}</p>
            <p class="text-xs text-slate-500">{{ order.customer.legal_name }}</p>
            <div class="mt-2 space-y-1 text-[11px] text-slate-500">
              <p class="flex items-center gap-1.5"><Hash class="size-3" /> {{ formatCnpj(order.customer.cnpj) }}</p>
              <p class="flex items-center gap-1.5">
                <MapPin class="size-3" /> {{ order.customer.city }} · {{ order.customer.state }}
              </p>
            </div>
          </div>
        </aside>
      </div>
    </template>
  </div>
</template>
