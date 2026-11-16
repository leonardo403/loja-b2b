<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { Loader2, Pencil, Search, Tags, TriangleAlert } from '@lucide/vue'
import AppModal from '@/components/AppModal.vue'
import EmptyState from '@/components/EmptyState.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import { getErrorMessage } from '@/services/api'
import {
  listPriceTableItems,
  listPriceTables,
  updatePriceTableItem,
  type PriceTableItemPayload,
} from '@/services/admin.service'
import { useToastStore } from '@/stores/toast'
import type { PaginationMeta, PriceTable, PriceTableItem } from '@/types'
import { formatCurrency } from '@/utils/format'

interface ItemForm {
  price: number | ''
  wholesale_price: number | ''
  wholesale_min_quantity: number | ''
}

const toast = useToastStore()

const tables = ref<PriceTable[]>([])
const selectedTableId = ref<number | null>(null)
const items = ref<PriceTableItem[]>([])
const meta = ref<PaginationMeta>({ current_page: 1, last_page: 1, per_page: 20, total: 0, from: null, to: null })
const loadingTables = ref(true)
const loading = ref(false)
const error = ref<string | null>(null)
const search = ref('')
const page = ref(1)

const modalOpen = ref(false)
const editing = ref<PriceTableItem | null>(null)
const form = ref<ItemForm>({ price: '', wholesale_price: '', wholesale_min_quantity: '' })
const saving = ref(false)
const formError = ref<string | null>(null)

const selectedTable = ref<PriceTable | null>(null)

async function loadTables() {
  loadingTables.value = true

  try {
    tables.value = await listPriceTables()

    if (tables.value.length) {
      selectedTableId.value = tables.value[0].id
      selectedTable.value = tables.value[0]
      await loadItems()
    }
  } catch (loadError) {
    error.value = getErrorMessage(loadError)
  } finally {
    loadingTables.value = false
  }
}

async function loadItems() {
  if (!selectedTableId.value) return

  loading.value = true
  error.value = null

  try {
    const response = await listPriceTableItems(selectedTableId.value, {
      search: search.value.trim() || undefined,
      page: page.value,
      per_page: 20,
    })
    items.value = response.data
    meta.value = response.meta
  } catch (loadError) {
    error.value = getErrorMessage(loadError)
  } finally {
    loading.value = false
  }
}

onMounted(loadTables)

async function selectTable(table: PriceTable) {
  if (selectedTableId.value === table.id) return

  selectedTableId.value = table.id
  selectedTable.value = table
  page.value = 1
  search.value = ''
  await loadItems()
}

function submitSearch() {
  page.value = 1
  loadItems()
}

function changePage(next: number) {
  page.value = next
  loadItems()
}

function openEdit(item: PriceTableItem) {
  editing.value = item
  form.value = {
    price: item.price,
    wholesale_price: item.wholesale_price ?? '',
    wholesale_min_quantity: item.wholesale_min_quantity ?? '',
  }
  formError.value = null
  modalOpen.value = true
}

function payload(): PriceTableItemPayload {
  return {
    price: Number(form.value.price) || 0,
    wholesale_price: form.value.wholesale_price === '' ? null : Number(form.value.wholesale_price),
    wholesale_min_quantity:
      form.value.wholesale_min_quantity === '' ? null : Number(form.value.wholesale_min_quantity),
  }
}

async function save() {
  if (!editing.value || !selectedTableId.value) return

  saving.value = true
  formError.value = null

  try {
    await updatePriceTableItem(selectedTableId.value, editing.value.product_id, payload())
    toast.success('Preço atualizado com sucesso.')
    modalOpen.value = false
    await loadItems()
  } catch (saveError) {
    formError.value = getErrorMessage(saveError)
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="space-y-5">
    <div>
      <h1 class="text-2xl font-bold tracking-tight text-slate-900">Tabelas de preço</h1>
      <p class="mt-1 text-sm text-slate-500">
        Defina o preço padrão e a faixa de atacado de cada produto por tabela.
      </p>
    </div>

    <LoadingSpinner v-if="loadingTables" label="Carregando tabelas..." />

    <template v-else>
      <div class="flex flex-wrap gap-2">
        <button
          v-for="table in tables"
          :key="table.id"
          type="button"
          class="flex items-center gap-2 rounded-xl border px-4 py-2.5 text-left transition"
          :class="
            selectedTableId === table.id
              ? 'border-emerald-500 bg-emerald-50 text-emerald-800 ring-1 ring-emerald-500/30'
              : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'
          "
          @click="selectTable(table)"
        >
          <Tags class="size-4" />
          <span class="text-sm font-semibold">{{ table.code }}</span>
          <span class="text-xs text-slate-400">{{ table.items_count ?? 0 }} itens</span>
        </button>
      </div>

      <div v-if="selectedTable" class="card p-4">
        <p class="text-xs text-slate-500">{{ selectedTable.description }}</p>

        <form class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center" @submit.prevent="submitSearch">
          <div class="relative flex-1">
            <Search class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-slate-400" />
            <input v-model="search" type="search" class="input pl-10" placeholder="Buscar por produto, SKU ou categoria..." />
          </div>
          <button type="submit" class="btn btn-dark shrink-0">Buscar</button>
        </form>
      </div>

      <LoadingSpinner v-if="loading" label="Carregando preços..." />

      <div v-else-if="error" class="card flex flex-col items-center gap-3 px-6 py-12 text-center">
        <TriangleAlert class="size-9 text-amber-500" />
        <p class="text-sm font-medium text-slate-700">{{ error }}</p>
        <button type="button" class="btn btn-primary btn-sm" @click="loadItems">Tentar novamente</button>
      </div>

      <EmptyState
        v-else-if="!items.length"
        title="Nenhum item encontrado"
        description="Esta tabela ainda não possui produtos ou a busca não retornou resultados."
      />

      <template v-else>
        <div class="card overflow-x-auto">
          <table class="w-full min-w-[760px]">
            <thead class="border-b border-slate-200 bg-slate-50">
              <tr>
                <th class="table-th">Produto</th>
                <th class="table-th">Categoria</th>
                <th class="table-th text-right">Preço</th>
                <th class="table-th text-right">Atacado</th>
                <th class="table-th text-center">Qtd. mín.</th>
                <th class="table-th text-right">Ações</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in items" :key="item.id" class="hover:bg-slate-50/70">
                <td class="table-td">
                  <p class="max-w-sm truncate font-medium text-slate-900">{{ item.name }}</p>
                  <p class="text-[11px] text-slate-400">SKU {{ item.sku }}</p>
                </td>
                <td class="table-td">{{ item.category ?? '—' }}</td>
                <td class="table-td text-right font-semibold whitespace-nowrap text-slate-900">
                  {{ formatCurrency(item.price) }}
                </td>
                <td class="table-td text-right whitespace-nowrap">
                  <span v-if="item.wholesale_price !== null" class="font-semibold text-emerald-700">
                    {{ formatCurrency(item.wholesale_price) }}
                  </span>
                  <span v-else class="text-slate-300">—</span>
                </td>
                <td class="table-td text-center">
                  <span v-if="item.wholesale_min_quantity !== null" class="badge bg-slate-100 text-slate-600">
                    {{ item.wholesale_min_quantity }} un.
                  </span>
                  <span v-else class="text-slate-300">—</span>
                </td>
                <td class="table-td">
                  <div class="flex justify-end">
                    <button type="button" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" title="Editar preço" @click="openEdit(item)">
                      <Pencil class="size-4" />
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
    </template>

    <AppModal
      :open="modalOpen"
      title="Editar preço"
      :description="editing ? `${editing.name} · SKU ${editing.sku}` : ''"
      @close="modalOpen = false"
    >
      <div v-if="formError" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
        {{ formError }}
      </div>

      <form id="price-form" class="space-y-4" @submit.prevent="save">
        <div>
          <label class="label" for="price-default">Preço padrão *</label>
          <input id="price-default" v-model.number="form.price" type="number" min="0.01" step="0.01" required class="input" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="label" for="price-wholesale">Preço de atacado</label>
            <input id="price-wholesale" v-model.number="form.wholesale_price" type="number" min="0.01" step="0.01" class="input" placeholder="Deixe vazio se não houver" />
          </div>

          <div>
            <label class="label" for="price-min">Quantidade mínima</label>
            <input id="price-min" v-model.number="form.wholesale_min_quantity" type="number" min="2" step="1" class="input" placeholder="Ex.: 12" />
          </div>
        </div>

        <p class="rounded-lg bg-slate-50 px-3 py-2 text-[11px] text-slate-500">
          O preço de atacado deve ser menor que o preço padrão e a quantidade mínima é obrigatória
          quando há preço de atacado.
        </p>
      </form>

      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="modalOpen = false">Cancelar</button>
        <button type="submit" form="price-form" class="btn btn-primary btn-sm" :disabled="saving">
          <Loader2 v-if="saving" class="size-4 animate-spin" />
          Salvar preço
        </button>
      </template>
    </AppModal>
  </div>
</template>
