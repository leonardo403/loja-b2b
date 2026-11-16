<script setup lang="ts">
import { onMounted, ref } from 'vue'
import {
  BadgePercent,
  CalendarDays,
  Loader2,
  Pencil,
  Plus,
  Search,
  Trash2,
  TriangleAlert,
} from '@lucide/vue'
import AppModal from '@/components/AppModal.vue'
import EmptyState from '@/components/EmptyState.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import { getErrorMessage } from '@/services/api'
import {
  createPromotion,
  deletePromotion,
  listProducts,
  listPromotions,
  updatePromotion,
  type PromotionPayload,
} from '@/services/admin.service'
import { useToastStore } from '@/stores/toast'
import type { PaginationMeta, Promotion } from '@/types'
import { formatDate } from '@/utils/format'

interface ProductOption {
  id: number
  name: string
  sku: string
}

interface PromotionForm {
  product_id: number | ''
  discount_percentage: number | ''
  starts_at: string
  ends_at: string
}

const toast = useToastStore()

const promotions = ref<Promotion[]>([])
const meta = ref<PaginationMeta>({ current_page: 1, last_page: 1, per_page: 15, total: 0, from: null, to: null })
const loading = ref(true)
const error = ref<string | null>(null)
const search = ref('')
const page = ref(1)

const modalOpen = ref(false)
const editing = ref<Promotion | null>(null)
const form = ref<PromotionForm>(emptyForm())
const productOptions = ref<ProductOption[]>([])
const productSearch = ref('')
const loadingProducts = ref(false)
const saving = ref(false)
const formError = ref<string | null>(null)

const deleteTarget = ref<Promotion | null>(null)
const deleting = ref(false)

function emptyForm(): PromotionForm {
  return {
    product_id: '',
    discount_percentage: '',
    starts_at: new Date().toISOString().slice(0, 10),
    ends_at: '',
  }
}

async function load() {
  loading.value = true
  error.value = null

  try {
    const response = await listPromotions({
      search: search.value.trim() || undefined,
      page: page.value,
      per_page: 15,
    })
    promotions.value = response.data
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

async function searchProducts() {
  loadingProducts.value = true

  try {
    const response = await listProducts({
      search: productSearch.value.trim() || undefined,
      per_page: 50,
      sort: 'name_asc',
    })
    const options = response.data.map((product) => ({
      id: product.id,
      name: product.name,
      sku: product.sku,
    }))

    const current = form.value.product_id
    const currentOption = productOptions.value.find((option) => option.id === current)

    if (currentOption && !options.some((option) => option.id === currentOption.id)) {
      options.unshift(currentOption)
    }

    productOptions.value = options
  } catch (loadError) {
    toast.error(getErrorMessage(loadError))
  } finally {
    loadingProducts.value = false
  }
}

async function openCreate() {
  editing.value = null
  form.value = emptyForm()
  productSearch.value = ''
  productOptions.value = []
  formError.value = null
  modalOpen.value = true
  await searchProducts()
}

async function openEdit(promotion: Promotion) {
  editing.value = promotion
  form.value = {
    product_id: promotion.product_id,
    discount_percentage: promotion.discount_percentage,
    starts_at: promotion.starts_at,
    ends_at: promotion.ends_at,
  }
  productSearch.value = ''
  productOptions.value = promotion.product_name
    ? [{ id: promotion.product_id, name: promotion.product_name, sku: promotion.product_sku ?? '' }]
    : []
  formError.value = null
  modalOpen.value = true
  await searchProducts()
}

function payload(): PromotionPayload {
  return {
    product_id: Number(form.value.product_id),
    discount_percentage: Number(form.value.discount_percentage),
    starts_at: form.value.starts_at,
    ends_at: form.value.ends_at,
  }
}

async function save() {
  saving.value = true
  formError.value = null

  try {
    if (editing.value) {
      await updatePromotion(editing.value.id, payload())
      toast.success('Promoção atualizada com sucesso.')
    } else {
      await createPromotion(payload())
      toast.success('Promoção criada com sucesso.')
    }

    modalOpen.value = false
    await load()
  } catch (saveError) {
    formError.value = getErrorMessage(saveError)
  } finally {
    saving.value = false
  }
}

function askDelete(promotion: Promotion) {
  deleteTarget.value = promotion
}

async function confirmDelete() {
  if (!deleteTarget.value) return

  deleting.value = true

  try {
    await deletePromotion(deleteTarget.value.id)
    toast.success('Promoção removida.')
    deleteTarget.value = null

    if (promotions.value.length === 1 && page.value > 1) {
      page.value -= 1
    }

    await load()
  } catch (deleteError) {
    toast.error(getErrorMessage(deleteError))
  } finally {
    deleting.value = false
  }
}

function isExpired(promotion: Promotion) {
  return !promotion.active && new Date(promotion.ends_at) < new Date()
}
</script>

<template>
  <div class="space-y-5">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Promoções</h1>
        <p class="mt-1 text-sm text-slate-500">{{ meta.total }} promoções cadastradas.</p>
      </div>
      <button type="button" class="btn btn-primary btn-sm" @click="openCreate">
        <Plus class="size-4" /> Nova promoção
      </button>
    </div>

    <div class="card p-4">
      <form class="flex flex-col gap-2 sm:flex-row sm:items-center" @submit.prevent="submitFilters">
        <div class="relative flex-1">
          <Search class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-slate-400" />
          <input v-model="search" type="search" class="input pl-10" placeholder="Buscar por produto ou SKU..." />
        </div>
        <button type="submit" class="btn btn-dark shrink-0">Buscar</button>
      </form>
    </div>

    <LoadingSpinner v-if="loading" label="Carregando promoções..." />

    <div v-else-if="error" class="card flex flex-col items-center gap-3 px-6 py-12 text-center">
      <TriangleAlert class="size-9 text-amber-500" />
      <p class="text-sm font-medium text-slate-700">{{ error }}</p>
      <button type="button" class="btn btn-primary btn-sm" @click="load">Tentar novamente</button>
    </div>

    <EmptyState
      v-else-if="!promotions.length"
      title="Nenhuma promoção cadastrada"
      description="Crie descontos por período para impulsionar as vendas do catálogo."
    >
      <template #action>
        <button type="button" class="btn btn-primary" @click="openCreate">
          <Plus class="size-4" /> Nova promoção
        </button>
      </template>
    </EmptyState>

    <template v-else>
      <div class="card overflow-x-auto">
        <table class="w-full min-w-[800px]">
          <thead class="border-b border-slate-200 bg-slate-50">
            <tr>
              <th class="table-th">Produto</th>
              <th class="table-th text-center">Desconto</th>
              <th class="table-th">Período</th>
              <th class="table-th">Status</th>
              <th class="table-th text-right">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="promotion in promotions" :key="promotion.id" class="hover:bg-slate-50/70">
              <td class="table-td">
                <p class="max-w-sm truncate font-medium text-slate-900">{{ promotion.product_name ?? 'Produto removido' }}</p>
                <p class="text-[11px] text-slate-400">SKU {{ promotion.product_sku ?? '—' }}</p>
              </td>
              <td class="table-td text-center">
                <span class="badge bg-rose-100 text-rose-700">
                  <BadgePercent class="size-3" /> {{ promotion.discount_percentage }}%
                </span>
              </td>
              <td class="table-td whitespace-nowrap">
                <span class="flex items-center gap-1.5 text-xs text-slate-600">
                  <CalendarDays class="size-3.5 text-slate-400" />
                  {{ formatDate(promotion.starts_at) }} — {{ formatDate(promotion.ends_at) }}
                </span>
              </td>
              <td class="table-td">
                <span
                  class="badge"
                  :class="
                    promotion.active
                      ? 'bg-emerald-100 text-emerald-700'
                      : isExpired(promotion)
                        ? 'bg-slate-100 text-slate-500'
                        : 'bg-amber-100 text-amber-800'
                  "
                >
                  {{ promotion.active ? 'Ativa' : isExpired(promotion) ? 'Encerrada' : 'Agendada' }}
                </span>
              </td>
              <td class="table-td">
                <div class="flex items-center justify-end gap-1">
                  <button type="button" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" title="Editar" @click="openEdit(promotion)">
                    <Pencil class="size-4" />
                  </button>
                  <button type="button" class="rounded-lg p-2 text-slate-400 transition hover:bg-red-50 hover:text-red-600" title="Excluir" @click="askDelete(promotion)">
                    <Trash2 class="size-4" />
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
      :title="editing ? 'Editar promoção' : 'Nova promoção'"
      description="O desconto é aplicado sobre o preço vigente do produto."
      width="lg"
      @close="modalOpen = false"
    >
      <div v-if="formError" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
        {{ formError }}
      </div>

      <form id="promotion-form" class="space-y-4" @submit.prevent="save">
        <div>
          <label class="label" for="promotion-search">Buscar produto</label>
          <div class="flex gap-2">
            <input
              id="promotion-search"
              v-model="productSearch"
              type="search"
              class="input"
              placeholder="Nome ou SKU do produto..."
              @keyup.enter.prevent="searchProducts"
            />
            <button type="button" class="btn btn-outline shrink-0" :disabled="loadingProducts" @click="searchProducts">
              <Loader2 v-if="loadingProducts" class="size-4 animate-spin" />
              <Search v-else class="size-4" />
            </button>
          </div>
        </div>

        <div>
          <label class="label" for="promotion-product">Produto *</label>
          <select id="promotion-product" v-model.number="form.product_id" required class="input">
            <option value="" disabled>Selecione um produto</option>
            <option v-for="product in productOptions" :key="product.id" :value="product.id">
              {{ product.name }} · {{ product.sku }}
            </option>
          </select>
        </div>

        <div>
          <label class="label" for="promotion-discount">Desconto (%) *</label>
          <input
            id="promotion-discount"
            v-model.number="form.discount_percentage"
            type="number"
            min="0.01"
            max="100"
            step="0.01"
            required
            class="input"
          />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="label" for="promotion-start">Início *</label>
            <input id="promotion-start" v-model="form.starts_at" type="date" required class="input" />
          </div>
          <div>
            <label class="label" for="promotion-end">Fim *</label>
            <input id="promotion-end" v-model="form.ends_at" type="date" required class="input" />
          </div>
        </div>
      </form>

      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="modalOpen = false">Cancelar</button>
        <button type="submit" form="promotion-form" class="btn btn-primary btn-sm" :disabled="saving">
          <Loader2 v-if="saving" class="size-4 animate-spin" />
          {{ editing ? 'Salvar alterações' : 'Criar promoção' }}
        </button>
      </template>
    </AppModal>

    <AppModal
      :open="Boolean(deleteTarget)"
      title="Excluir promoção"
      description="Esta ação não pode ser desfeita."
      @close="deleteTarget = null"
    >
      <p class="text-sm text-slate-600">
        Deseja realmente excluir a promoção de
        <strong class="font-semibold text-slate-900">{{ deleteTarget?.discount_percentage }}%</strong>
        para <strong class="font-semibold text-slate-900">{{ deleteTarget?.product_name }}</strong>?
      </p>

      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="deleteTarget = null">Cancelar</button>
        <button type="button" class="btn btn-danger btn-sm" :disabled="deleting" @click="confirmDelete">
          <Loader2 v-if="deleting" class="size-4 animate-spin" />
          Excluir promoção
        </button>
      </template>
    </AppModal>
  </div>
</template>
