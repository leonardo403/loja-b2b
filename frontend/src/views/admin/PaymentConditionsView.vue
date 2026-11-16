<script setup lang="ts">
import { onMounted, ref } from 'vue'
import {
  CalendarClock,
  CreditCard,
  Landmark,
  Loader2,
  Pencil,
  Plus,
  Trash2,
  TriangleAlert,
} from '@lucide/vue'
import AppModal from '@/components/AppModal.vue'
import EmptyState from '@/components/EmptyState.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import { getErrorMessage } from '@/services/api'
import {
  createPaymentCondition,
  deletePaymentCondition,
  listPaymentConditions,
  updatePaymentCondition,
  type PaymentConditionPayload,
} from '@/services/admin.service'
import { useToastStore } from '@/stores/toast'
import type { PaginationMeta, PaymentCondition } from '@/types'
import { formatCurrency } from '@/utils/format'

interface PaymentForm {
  code: string
  description: string
  minimum_value: number | ''
  surcharge_percentage: number | ''
  is_term: boolean
}

const toast = useToastStore()

const conditions = ref<PaymentCondition[]>([])
const meta = ref<PaginationMeta>({ current_page: 1, last_page: 1, per_page: 20, total: 0, from: null, to: null })
const loading = ref(true)
const error = ref<string | null>(null)
const page = ref(1)

const modalOpen = ref(false)
const editing = ref<PaymentCondition | null>(null)
const form = ref<PaymentForm>(emptyForm())
const saving = ref(false)
const formError = ref<string | null>(null)

const deleteTarget = ref<PaymentCondition | null>(null)
const deleting = ref(false)

function emptyForm(): PaymentForm {
  return {
    code: '',
    description: '',
    minimum_value: 0,
    surcharge_percentage: 0,
    is_term: false,
  }
}

async function load() {
  loading.value = true
  error.value = null

  try {
    const response = await listPaymentConditions({ page: page.value, per_page: 20 })
    conditions.value = response.data
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
  load()
}

function openCreate() {
  editing.value = null
  form.value = emptyForm()
  formError.value = null
  modalOpen.value = true
}

function openEdit(condition: PaymentCondition) {
  editing.value = condition
  form.value = {
    code: condition.code,
    description: condition.description,
    minimum_value: condition.minimum_value,
    surcharge_percentage: condition.surcharge_percentage,
    is_term: condition.is_term,
  }
  formError.value = null
  modalOpen.value = true
}

function payload(): PaymentConditionPayload {
  return {
    code: form.value.code.trim().toUpperCase(),
    description: form.value.description.trim(),
    minimum_value: Number(form.value.minimum_value) || 0,
    surcharge_percentage: Number(form.value.surcharge_percentage) || 0,
    is_term: Boolean(form.value.is_term),
  }
}

async function save() {
  saving.value = true
  formError.value = null

  try {
    if (editing.value) {
      await updatePaymentCondition(editing.value.id, payload())
      toast.success('Condição atualizada com sucesso.')
    } else {
      await createPaymentCondition(payload())
      toast.success('Condição criada com sucesso.')
    }

    modalOpen.value = false
    await load()
  } catch (saveError) {
    formError.value = getErrorMessage(saveError)
  } finally {
    saving.value = false
  }
}

function askDelete(condition: PaymentCondition) {
  deleteTarget.value = condition
}

async function confirmDelete() {
  if (!deleteTarget.value) return

  deleting.value = true

  try {
    await deletePaymentCondition(deleteTarget.value.id)
    toast.success('Condição removida.')
    deleteTarget.value = null

    if (conditions.value.length === 1 && page.value > 1) {
      page.value -= 1
    }

    await load()
  } catch (deleteError) {
    toast.error(getErrorMessage(deleteError))
  } finally {
    deleting.value = false
  }
}
</script>

<template>
  <div class="space-y-5">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Condições de pagamento</h1>
        <p class="mt-1 text-sm text-slate-500">
          Prazos, valores mínimos e acréscimos aplicados no fechamento do pedido.
        </p>
      </div>
      <button type="button" class="btn btn-primary btn-sm" @click="openCreate">
        <Plus class="size-4" /> Nova condição
      </button>
    </div>

    <LoadingSpinner v-if="loading" label="Carregando condições..." />

    <div v-else-if="error" class="card flex flex-col items-center gap-3 px-6 py-12 text-center">
      <TriangleAlert class="size-9 text-amber-500" />
      <p class="text-sm font-medium text-slate-700">{{ error }}</p>
      <button type="button" class="btn btn-primary btn-sm" @click="load">Tentar novamente</button>
    </div>

    <EmptyState
      v-else-if="!conditions.length"
      title="Nenhuma condição cadastrada"
      description="Cadastre ao menos uma condição para permitir o fechamento de pedidos."
    >
      <template #action>
        <button type="button" class="btn btn-primary" @click="openCreate">
          <Plus class="size-4" /> Nova condição
        </button>
      </template>
    </EmptyState>

    <template v-else>
      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <div v-for="condition in conditions" :key="condition.id" class="card p-5">
          <div class="flex items-start justify-between gap-3">
            <div class="flex items-start gap-3">
              <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                <CreditCard class="size-5" />
              </div>
              <div>
                <p class="text-sm font-bold text-slate-900">{{ condition.code }}</p>
                <p class="text-xs leading-snug text-slate-500">{{ condition.description }}</p>
              </div>
            </div>
            <div class="flex shrink-0 gap-1">
              <button type="button" class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" title="Editar" @click="openEdit(condition)">
                <Pencil class="size-4" />
              </button>
              <button type="button" class="rounded-lg p-1.5 text-slate-400 transition hover:bg-red-50 hover:text-red-600" title="Excluir" @click="askDelete(condition)">
                <Trash2 class="size-4" />
              </button>
            </div>
          </div>

          <div class="mt-4 grid grid-cols-2 gap-3 border-t border-slate-100 pt-3 text-xs">
            <div>
              <p class="flex items-center gap-1 text-[10px] font-semibold tracking-widest text-slate-400 uppercase">
                <Landmark class="size-3" /> Mínimo
              </p>
              <p class="mt-1 font-semibold text-slate-800">{{ formatCurrency(condition.minimum_value) }}</p>
            </div>
            <div>
              <p class="flex items-center gap-1 text-[10px] font-semibold tracking-widest text-slate-400 uppercase">
                <CalendarClock class="size-3" /> Prazo
              </p>
              <p class="mt-1 font-semibold text-slate-800">{{ condition.is_term ? 'A prazo' : 'À vista' }}</p>
            </div>
          </div>

          <span
            class="badge mt-3"
            :class="condition.surcharge_percentage > 0 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-700'"
          >
            {{ condition.surcharge_percentage > 0 ? `+${condition.surcharge_percentage}% de acréscimo` : 'Sem acréscimo' }}
          </span>
        </div>
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
      :title="editing ? 'Editar condição' : 'Nova condição'"
      description="Essas regras aparecem para o cliente no checkout."
      @close="modalOpen = false"
    >
      <div v-if="formError" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
        {{ formError }}
      </div>

      <form id="payment-form" class="grid gap-4 sm:grid-cols-2" @submit.prevent="save">
        <div>
          <label class="label" for="payment-code">Código *</label>
          <input id="payment-code" v-model="form.code" required maxlength="20" class="input uppercase" placeholder="PIX" />
        </div>

        <div>
          <label class="label" for="payment-surcharge">Acréscimo (%) *</label>
          <input id="payment-surcharge" v-model.number="form.surcharge_percentage" type="number" min="0" max="100" step="0.01" required class="input" />
        </div>

        <div class="sm:col-span-2">
          <label class="label" for="payment-description">Descrição *</label>
          <input id="payment-description" v-model="form.description" required maxlength="120" class="input" placeholder="PIX à vista" />
        </div>

        <div>
          <label class="label" for="payment-minimum">Valor mínimo *</label>
          <input id="payment-minimum" v-model.number="form.minimum_value" type="number" min="0" step="0.01" required class="input" />
        </div>

        <div class="flex items-end">
          <label class="flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-700">
            <input v-model="form.is_term" type="checkbox" class="size-4 accent-emerald-600" />
            Pagamento a prazo
          </label>
        </div>
      </form>

      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="modalOpen = false">Cancelar</button>
        <button type="submit" form="payment-form" class="btn btn-primary btn-sm" :disabled="saving">
          <Loader2 v-if="saving" class="size-4 animate-spin" />
          {{ editing ? 'Salvar alterações' : 'Criar condição' }}
        </button>
      </template>
    </AppModal>

    <AppModal
      :open="Boolean(deleteTarget)"
      title="Excluir condição"
      description="Esta ação não pode ser desfeita."
      @close="deleteTarget = null"
    >
      <p class="text-sm text-slate-600">
        Deseja realmente excluir a condição
        <strong class="font-semibold text-slate-900">{{ deleteTarget?.code }}</strong>?
        Pedidos existentes que a utilizam não serão alterados.
      </p>

      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="deleteTarget = null">Cancelar</button>
        <button type="button" class="btn btn-danger btn-sm" :disabled="deleting" @click="confirmDelete">
          <Loader2 v-if="deleting" class="size-4 animate-spin" />
          Excluir condição
        </button>
      </template>
    </AppModal>
  </div>
</template>
