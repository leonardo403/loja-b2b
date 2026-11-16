<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import {
  ArrowLeft,
  ArrowRight,
  Building2,
  CalendarClock,
  CheckCircle2,
  CreditCard,
  Info,
  Landmark,
  Loader2,
  MapPin,
  ShieldCheck,
  TriangleAlert,
  Wallet,
} from '@lucide/vue'
import EmptyState from '@/components/EmptyState.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import { getErrorMessage } from '@/services/api'
import { getPaymentConditions } from '@/services/catalog.service'
import { createOrder } from '@/services/order.service'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import { useToastStore } from '@/stores/toast'
import type { PaymentCondition } from '@/types'
import { formatCnpj, formatCurrency } from '@/utils/format'

const router = useRouter()
const auth = useAuthStore()
const cart = useCartStore()
const toast = useToastStore()

const conditions = ref<PaymentCondition[]>([])
const loadingConditions = ref(true)
const selectedPayment = ref<string>('')
const notes = ref('')
const submitting = ref(false)
const submitError = ref<string | null>(null)

const customer = computed(() => auth.customer)
const preview = computed(() => cart.preview)
const items = computed(() => cart.preview?.items ?? [])
const hasItems = computed(() => cart.lines.length > 0)
const subtotal = computed(() => preview.value?.subtotal ?? 0)

const availableCredit = computed(() => customer.value?.available_credit ?? 0)
const creditEnough = computed(() => availableCredit.value >= (preview.value?.total ?? 0))
const blocked = computed(() => customer.value?.blocked ?? false)
const warnings = computed(() => preview.value?.warnings ?? [])
const canSubmit = computed(
  () =>
    hasItems.value &&
    Boolean(selectedPayment.value) &&
    !blocked.value &&
    !submitting.value &&
    !cart.loading &&
    warnings.value.length === 0,
)

function isEligible(condition: PaymentCondition) {
  return subtotal.value >= condition.minimum_value
}

onMounted(async () => {
  if (!hasItems.value) {
    loadingConditions.value = false
    return
  }

  try {
    const loaded = await getPaymentConditions()
    conditions.value = loaded

    const firstEligible = loaded.find((condition) => subtotal.value >= condition.minimum_value)
    selectedPayment.value = firstEligible?.code ?? loaded[0]?.code ?? ''

    await cart.sync(selectedPayment.value || undefined)
  } catch (error) {
    submitError.value = getErrorMessage(error)
  } finally {
    loadingConditions.value = false
  }
})

watch(selectedPayment, async (value) => {
  if (!value) return

  try {
    await cart.sync(value)
  } catch (error) {
    submitError.value = getErrorMessage(error)
  }
})

async function submit() {
  if (!canSubmit.value) return

  submitting.value = true
  submitError.value = null

  try {
    const order = await createOrder({
      items: cart.lines.map((line) => ({ product_id: line.productId, quantity: line.quantity })),
      payment_condition: selectedPayment.value,
      notes: notes.value.trim() || null,
    })

    cart.clear()
    toast.success(`Pedido ${order.number} realizado com sucesso!`)
    router.push({ name: 'order', params: { id: order.id }, query: { novo: '1' } })
  } catch (error) {
    submitError.value = getErrorMessage(error)
    window.scrollTo({ top: 0, behavior: 'smooth' })
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="container-page py-8">
    <div class="mb-6">
      <RouterLink :to="{ name: 'cart' }" class="btn btn-ghost btn-sm mb-2 -ml-2">
        <ArrowLeft class="size-4" /> Voltar ao carrinho
      </RouterLink>
      <p class="text-xs font-semibold tracking-widest text-emerald-700 uppercase">Finalizar compra</p>
      <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Checkout</h1>
      <p class="mt-1 text-sm text-slate-500">Revise os dados da empresa, escolha o pagamento e confirme o pedido.</p>
    </div>

    <EmptyState
      v-if="!hasItems"
      title="Nada para finalizar"
      description="Seu carrinho está vazio. Adicione produtos antes de ir para o checkout."
    >
      <template #action>
        <RouterLink :to="{ name: 'products' }" class="btn btn-primary">
          Ver produtos <ArrowRight class="size-4" />
        </RouterLink>
      </template>
    </EmptyState>

    <div v-else class="grid gap-6 lg:grid-cols-[1fr_380px]">
      <div class="space-y-5">
        <div
          v-if="submitError"
          class="flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
        >
          <TriangleAlert class="mt-0.5 size-4 shrink-0" />
          {{ submitError }}
        </div>

        <div v-if="blocked" class="flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          <TriangleAlert class="mt-0.5 size-4 shrink-0" />
          <div>
            <p class="font-semibold">Empresa bloqueada para compras.</p>
            <p v-if="customer?.block_reason" class="mt-0.5">{{ customer.block_reason }}</p>
            <p class="mt-0.5">Entre em contato com o atendimento comercial para regularizar.</p>
          </div>
        </div>

        <div v-if="customer" class="card p-5">
          <div class="flex items-start justify-between gap-3">
            <div class="flex items-start gap-3">
              <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                <Building2 class="size-5" />
              </div>
              <div>
                <h2 class="text-sm font-semibold text-slate-900">{{ customer.trade_name }}</h2>
                <p class="text-xs text-slate-500">{{ customer.legal_name }}</p>
                <p class="mt-1 text-[11px] text-slate-400">CNPJ {{ formatCnpj(customer.cnpj) }}</p>
              </div>
            </div>
            <span class="badge bg-slate-100 text-slate-600">{{ customer.price_table.code }}</span>
          </div>

          <div class="mt-4 grid gap-3 border-t border-slate-100 pt-4 sm:grid-cols-3">
            <div>
              <p class="flex items-center gap-1 text-[10px] font-semibold tracking-widest text-slate-400 uppercase">
                <MapPin class="size-3" /> Localização
              </p>
              <p class="mt-1 text-sm font-medium text-slate-800">{{ customer.city }} · {{ customer.state }}</p>
            </div>
            <div>
              <p class="flex items-center gap-1 text-[10px] font-semibold tracking-widest text-slate-400 uppercase">
                <Wallet class="size-3" /> Limite de crédito
              </p>
              <p class="mt-1 text-sm font-medium text-slate-800">{{ formatCurrency(customer.credit_limit) }}</p>
            </div>
            <div>
              <p class="flex items-center gap-1 text-[10px] font-semibold tracking-widest text-slate-400 uppercase">
                <ShieldCheck class="size-3" /> Crédito disponível
              </p>
              <p class="mt-1 text-sm font-semibold" :class="creditEnough ? 'text-emerald-700' : 'text-red-600'">
                {{ formatCurrency(customer.available_credit) }}
              </p>
            </div>
          </div>
        </div>

        <div class="card p-5">
          <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900">
            <CreditCard class="size-4 text-slate-400" /> Condição de pagamento
          </h2>

          <LoadingSpinner v-if="loadingConditions" label="Carregando condições..." />

          <div v-else class="mt-3 grid gap-2 sm:grid-cols-2">
            <label
              v-for="condition in conditions"
              :key="condition.id"
              class="flex cursor-pointer items-start gap-3 rounded-xl border p-3.5 transition"
              :class="
                selectedPayment === condition.code
                  ? 'border-emerald-500 bg-emerald-50/60 ring-1 ring-emerald-500/30'
                  : 'border-slate-200 hover:border-slate-300'
              "
            >
              <input
                v-model="selectedPayment"
                type="radio"
                name="payment"
                :value="condition.code"
                class="mt-1 size-4 accent-emerald-600"
              />
              <div class="min-w-0 flex-1">
                <div class="flex items-center justify-between gap-2">
                  <p class="text-sm font-semibold text-slate-900">{{ condition.code }}</p>
                  <span v-if="condition.surcharge_percentage > 0" class="badge bg-amber-100 text-amber-800">
                    +{{ condition.surcharge_percentage }}%
                  </span>
                  <span v-else class="badge bg-emerald-100 text-emerald-700">sem acréscimo</span>
                </div>
                <p class="mt-0.5 text-xs leading-snug text-slate-500">{{ condition.description }}</p>
                <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-400">
                  <span class="flex items-center gap-1">
                    <Landmark class="size-3" /> Mín. {{ formatCurrency(condition.minimum_value) }}
                  </span>
                  <span v-if="condition.is_term" class="flex items-center gap-1">
                    <CalendarClock class="size-3" /> A prazo
                  </span>
                  <span v-if="!isEligible(condition)" class="font-semibold text-amber-600">
                    Valor abaixo do mínimo
                  </span>
                </div>
              </div>
            </label>
          </div>
        </div>

        <div class="card p-5">
          <h2 class="text-sm font-semibold text-slate-900">Observações do pedido</h2>
          <textarea
            v-model="notes"
            rows="3"
            maxlength="500"
            class="input mt-3 resize-none"
            placeholder="Instruções de entrega, horário de recebimento, referência..."
          />
          <p class="mt-1 text-right text-[11px] text-slate-400">{{ notes.length }}/500</p>
        </div>

        <div class="card p-5">
          <h2 class="text-sm font-semibold text-slate-900">Itens do pedido</h2>
          <div class="mt-3 divide-y divide-slate-100">
            <div v-for="item in items" :key="item.product_id" class="flex items-center justify-between gap-3 py-2.5">
              <div class="min-w-0">
                <p class="truncate text-sm font-medium text-slate-800">{{ item.name }}</p>
                <p class="text-[11px] text-slate-400">
                  {{ item.quantity }} un. × {{ formatCurrency(item.unit_price) }}
                </p>
              </div>
              <span class="shrink-0 text-sm font-semibold text-slate-900">{{ formatCurrency(item.line_subtotal) }}</span>
            </div>
          </div>
        </div>
      </div>

      <aside class="h-fit lg:sticky lg:top-28">
        <div class="card p-5">
          <h2 class="text-base font-semibold text-slate-900">Resumo do pedido</h2>

          <LoadingSpinner v-if="!preview && cart.loading" label="Calculando..." />

          <template v-else-if="preview">
            <dl class="mt-4 space-y-2.5 text-sm">
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
              <div class="flex items-center justify-between">
                <dt class="text-slate-500">Total do pedido</dt>
                <dd class="font-semibold text-slate-900">{{ formatCurrency(preview.total) }}</dd>
              </div>
            </dl>

            <div class="mt-4 flex items-end justify-between border-t border-slate-200 pt-4">
              <span class="text-sm font-semibold text-slate-900">Total</span>
              <span class="text-2xl font-bold tracking-tight text-emerald-700">{{ formatCurrency(preview.total) }}</span>
            </div>

            <div
              v-if="preview.warnings.length"
              class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800"
            >
              <p class="flex items-center gap-1.5 font-semibold">
                <TriangleAlert class="size-3.5" /> Ajuste antes de finalizar
              </p>
              <ul class="mt-1.5 space-y-1">
                <li v-for="warning in preview.warnings" :key="warning">· {{ warning }}</li>
              </ul>
            </div>

            <p v-if="!creditEnough && !blocked" class="mt-3 flex items-start gap-1.5 text-xs text-red-600">
              <Info class="mt-0.5 size-3.5 shrink-0" />
              O total ultrapassa o crédito disponível de {{ formatCurrency(availableCredit) }}.
            </p>
          </template>

          <button type="button" class="btn btn-primary mt-5 w-full" :disabled="!canSubmit" @click="submit">
            <Loader2 v-if="submitting" class="size-4 animate-spin" />
            <CheckCircle2 v-else class="size-4" />
            {{ submitting ? 'Finalizando pedido...' : 'Confirmar pedido' }}
          </button>

          <p class="mt-3 flex items-start gap-1.5 text-[11px] text-slate-400">
            <ShieldCheck class="mt-0.5 size-3.5 shrink-0" />
            Ao confirmar, o estoque é reservado e o valor entra no seu crédito utilizado.
          </p>
        </div>
      </aside>
    </div>
  </div>
</template>
