<script setup lang="ts">
import { onMounted, ref } from 'vue'
import {
  Loader2,
  Package,
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
  createProduct,
  deleteProduct,
  listProducts,
  updateProduct,
  type ProductPayload,
} from '@/services/admin.service'
import { useToastStore } from '@/stores/toast'
import type { PaginationMeta, Product } from '@/types'

interface ProductForm {
  sku: string
  ean: string
  name: string
  description: string
  brand: string
  category: string
  active: boolean
  stock_quantity: number
  price: number | ''
  wholesale_price: number | ''
  wholesale_min_quantity: number | ''
}

const toast = useToastStore()

const products = ref<Product[]>([])
const meta = ref<PaginationMeta>({ current_page: 1, last_page: 1, per_page: 15, total: 0, from: null, to: null })
const loading = ref(true)
const error = ref<string | null>(null)
const search = ref('')
const activeFilter = ref('')
const page = ref(1)

const modalOpen = ref(false)
const editing = ref<Product | null>(null)
const form = ref<ProductForm>(emptyForm())
const saving = ref(false)
const formError = ref<string | null>(null)

const deleteTarget = ref<Product | null>(null)
const deleting = ref(false)

function emptyForm(): ProductForm {
  return {
    sku: '',
    ean: '',
    name: '',
    description: '',
    brand: '',
    category: '',
    active: true,
    stock_quantity: 0,
    price: '',
    wholesale_price: '',
    wholesale_min_quantity: '',
  }
}

async function load() {
  loading.value = true
  error.value = null

  try {
    const response = await listProducts({
      search: search.value.trim() || undefined,
      active: activeFilter.value === '' ? undefined : activeFilter.value,
      page: page.value,
      per_page: 15,
      sort: 'name_asc',
    })
    products.value = response.data
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

function openCreate() {
  editing.value = null
  form.value = emptyForm()
  formError.value = null
  modalOpen.value = true
}

function openEdit(product: Product) {
  editing.value = product
  form.value = {
    sku: product.sku,
    ean: product.ean ?? '',
    name: product.name,
    description: product.description ?? '',
    brand: product.brand ?? '',
    category: product.category ?? '',
    active: product.active,
    stock_quantity: product.stock_quantity,
    price: '',
    wholesale_price: '',
    wholesale_min_quantity: '',
  }
  formError.value = null
  modalOpen.value = true
}

function payload(): ProductPayload {
  const data: ProductPayload = {
    sku: form.value.sku.trim(),
    ean: form.value.ean.trim() || null,
    name: form.value.name.trim(),
    description: form.value.description.trim() || null,
    brand: form.value.brand.trim() || null,
    category: form.value.category.trim() || null,
    active: Boolean(form.value.active),
    stock_quantity: Number(form.value.stock_quantity) || 0,
  }

  if (!editing.value) {
    data.price = Number(form.value.price) || 0
    data.wholesale_price =
      form.value.wholesale_price === '' ? null : Number(form.value.wholesale_price)
    data.wholesale_min_quantity =
      form.value.wholesale_min_quantity === ''
        ? null
        : Number(form.value.wholesale_min_quantity)
  }

  return data
}

async function save() {
  saving.value = true
  formError.value = null

  try {
    if (editing.value) {
      await updateProduct(editing.value.id, payload())
      toast.success('Produto atualizado com sucesso.')
    } else {
      await createProduct(payload())
      toast.success('Produto criado com sucesso.')
    }

    modalOpen.value = false
    await load()
  } catch (saveError) {
    formError.value = getErrorMessage(saveError)
  } finally {
    saving.value = false
  }
}

function askDelete(product: Product) {
  deleteTarget.value = product
}

async function confirmDelete() {
  if (!deleteTarget.value) return

  deleting.value = true

  try {
    await deleteProduct(deleteTarget.value.id)
    toast.success('Produto removido.')
    deleteTarget.value = null

    if (products.value.length === 1 && page.value > 1) {
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
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Produtos</h1>
        <p class="mt-1 text-sm text-slate-500">{{ meta.total }} produtos cadastrados no catálogo.</p>
      </div>
      <button type="button" class="btn btn-primary btn-sm" @click="openCreate">
        <Plus class="size-4" /> Novo produto
      </button>
    </div>

    <div class="card p-4">
      <form class="flex flex-col gap-2 sm:flex-row sm:items-center" @submit.prevent="submitFilters">
        <div class="relative flex-1">
          <Search class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-slate-400" />
          <input v-model="search" type="search" class="input pl-10" placeholder="Buscar por nome, SKU, marca ou EAN..." />
        </div>
        <select v-model="activeFilter" class="input sm:w-52" @change="submitFilters">
          <option value="">Todos os status</option>
          <option value="1">Somente ativos</option>
          <option value="0">Somente inativos</option>
        </select>
        <button type="submit" class="btn btn-dark shrink-0">Filtrar</button>
      </form>
    </div>

    <LoadingSpinner v-if="loading" label="Carregando produtos..." />

    <div v-else-if="error" class="card flex flex-col items-center gap-3 px-6 py-12 text-center">
      <TriangleAlert class="size-9 text-amber-500" />
      <p class="text-sm font-medium text-slate-700">{{ error }}</p>
      <button type="button" class="btn btn-primary btn-sm" @click="load">Tentar novamente</button>
    </div>

    <EmptyState
      v-else-if="!products.length"
      title="Nenhum produto encontrado"
      description="Ajuste os filtros ou cadastre um novo produto no catálogo."
    >
      <template #action>
        <button type="button" class="btn btn-primary" @click="openCreate">
          <Plus class="size-4" /> Novo produto
        </button>
      </template>
    </EmptyState>

    <template v-else>
      <div class="card overflow-x-auto">
        <table class="w-full min-w-[760px]">
          <thead class="border-b border-slate-200 bg-slate-50">
            <tr>
              <th class="table-th">Produto</th>
              <th class="table-th">Categoria</th>
              <th class="table-th">Marca</th>
              <th class="table-th text-center">Estoque</th>
              <th class="table-th">Status</th>
              <th class="table-th text-right">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="product in products" :key="product.id" class="hover:bg-slate-50/70">
              <td class="table-td">
                <div class="flex items-center gap-3">
                  <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-400">
                    <Package class="size-4" />
                  </div>
                  <div class="min-w-0">
                    <p class="max-w-xs truncate font-medium text-slate-900">{{ product.name }}</p>
                    <p class="text-[11px] text-slate-400">SKU {{ product.sku }}<span v-if="product.ean"> · EAN {{ product.ean }}</span></p>
                  </div>
                </div>
              </td>
              <td class="table-td">{{ product.category ?? '—' }}</td>
              <td class="table-td">{{ product.brand ?? '—' }}</td>
              <td class="table-td text-center">
                <span
                  class="badge"
                  :class="
                    product.stock_quantity <= 0
                      ? 'bg-red-100 text-red-700'
                      : product.stock_quantity <= 20
                        ? 'bg-amber-100 text-amber-800'
                        : 'bg-slate-100 text-slate-600'
                  "
                >
                  {{ product.stock_quantity }}
                </span>
              </td>
              <td class="table-td">
                <span class="badge" :class="product.active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'">
                  {{ product.active ? 'Ativo' : 'Inativo' }}
                </span>
              </td>
              <td class="table-td">
                <div class="flex items-center justify-end gap-1">
                  <button type="button" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" title="Editar" @click="openEdit(product)">
                    <Pencil class="size-4" />
                  </button>
                  <button type="button" class="rounded-lg p-2 text-slate-400 transition hover:bg-red-50 hover:text-red-600" title="Excluir" @click="askDelete(product)">
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
      :title="editing ? 'Editar produto' : 'Novo produto'"
      :description="editing ? `SKU ${editing.sku}` : 'Preencha os dados do produto para o catálogo.'"
      width="lg"
      @close="modalOpen = false"
    >
      <div v-if="formError" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
        {{ formError }}
      </div>

      <form id="product-form" class="grid gap-4 sm:grid-cols-2" @submit.prevent="save">
        <div class="sm:col-span-2">
          <label class="label" for="product-name">Nome *</label>
          <input id="product-name" v-model="form.name" required maxlength="255" class="input" />
        </div>

        <div>
          <label class="label" for="product-sku">SKU *</label>
          <input id="product-sku" v-model="form.sku" required maxlength="40" class="input" />
        </div>

        <div>
          <label class="label" for="product-ean">EAN</label>
          <input id="product-ean" v-model="form.ean" maxlength="20" class="input" />
        </div>

        <div>
          <label class="label" for="product-brand">Marca</label>
          <input id="product-brand" v-model="form.brand" maxlength="120" class="input" />
        </div>

        <div>
          <label class="label" for="product-category">Categoria</label>
          <input id="product-category" v-model="form.category" maxlength="120" class="input" />
        </div>

        <div>
          <label class="label" for="product-stock">Estoque *</label>
          <input id="product-stock" v-model.number="form.stock_quantity" type="number" min="0" max="1000000" required class="input" />
        </div>

        <div class="flex items-end">
          <label class="flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-700">
            <input v-model="form.active" type="checkbox" class="size-4 accent-emerald-600" />
            Produto ativo no catálogo
          </label>
        </div>

        <template v-if="!editing">
          <div>
            <label class="label" for="product-price">Preço padrão *</label>
            <input id="product-price" v-model.number="form.price" type="number" min="0.01" step="0.01" required class="input" />
          </div>

          <div>
            <label class="label" for="product-wholesale">Preço de atacado</label>
            <input id="product-wholesale" v-model.number="form.wholesale_price" type="number" min="0.01" step="0.01" class="input" placeholder="Opcional" />
          </div>

          <div>
            <label class="label" for="product-wholesale-min">Qtd. mínima de atacado</label>
            <input id="product-wholesale-min" v-model.number="form.wholesale_min_quantity" type="number" min="2" step="1" class="input" placeholder="Ex.: 12" />
          </div>

          <p class="text-[11px] leading-snug text-slate-500 sm:col-span-2">
            O preço será criado em todas as tabelas. Ajustes por tabela podem ser feitos depois em
            <strong class="font-semibold text-slate-700">Tabelas de preço</strong>.
          </p>
        </template>

        <div class="sm:col-span-2">
          <label class="label" for="product-description">Descrição</label>
          <textarea id="product-description" v-model="form.description" rows="3" maxlength="2000" class="input resize-none" />
        </div>

        <p v-if="editing" class="text-[11px] text-slate-500 sm:col-span-2">
          Para alterar os preços deste produto, use a página
          <RouterLink :to="{ name: 'admin-price-tables' }" class="font-semibold text-emerald-700 hover:underline">
            Tabelas de preço
          </RouterLink>.
        </p>
      </form>

      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="modalOpen = false">Cancelar</button>
        <button type="submit" form="product-form" class="btn btn-primary btn-sm" :disabled="saving">
          <Loader2 v-if="saving" class="size-4 animate-spin" />
          {{ editing ? 'Salvar alterações' : 'Criar produto' }}
        </button>
      </template>
    </AppModal>

    <AppModal
      :open="Boolean(deleteTarget)"
      title="Excluir produto"
      description="Esta ação não pode ser desfeita."
      @close="deleteTarget = null"
    >
      <p class="text-sm text-slate-600">
        Deseja realmente excluir
        <strong class="font-semibold text-slate-900">{{ deleteTarget?.name }}</strong>?
        O produto sairá do catálogo e das tabelas de preço.
      </p>

      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="deleteTarget = null">Cancelar</button>
        <button type="button" class="btn btn-danger btn-sm" :disabled="deleting" @click="confirmDelete">
          <Loader2 v-if="deleting" class="size-4 animate-spin" />
          Excluir produto
        </button>
      </template>
    </AppModal>
  </div>
</template>
