<script setup lang="ts">
import { onMounted, ref } from 'vue'
import {
  Ban,
  Building2,
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
  createCustomer,
  deleteCustomer,
  listCustomers,
  listPriceTables,
  updateCustomer,
  type CustomerPayload,
} from '@/services/admin.service'
import { useToastStore } from '@/stores/toast'
import type { Customer, PaginationMeta, PriceTable } from '@/types'
import { formatCnpj, formatCurrency } from '@/utils/format'

const toast = useToastStore()

const customers = ref<Customer[]>([])
const priceTables = ref<PriceTable[]>([])
const meta = ref<PaginationMeta>({ current_page: 1, last_page: 1, per_page: 15, total: 0, from: null, to: null })
const loading = ref(true)
const error = ref<string | null>(null)
const search = ref('')
const blockedFilter = ref('')
const page = ref(1)

const modalOpen = ref(false)
const editing = ref<Customer | null>(null)
const form = ref<CustomerPayload>(emptyForm())
const saving = ref(false)
const formError = ref<string | null>(null)

const deleteTarget = ref<Customer | null>(null)
const deleting = ref(false)

function emptyForm(): CustomerPayload {
  return {
    cnpj: '',
    legal_name: '',
    trade_name: '',
    city: '',
    state: '',
    price_table_id: 0,
    credit_limit: 0,
    credit_used: 0,
    blocked: false,
    block_reason: '',
    email: '',
    password: '',
  }
}

async function load() {
  loading.value = true
  error.value = null

  try {
    const response = await listCustomers({
      search: search.value.trim() || undefined,
      blocked: blockedFilter.value === '' ? undefined : blockedFilter.value,
      page: page.value,
      per_page: 15,
    })
    customers.value = response.data
    meta.value = response.meta
  } catch (loadError) {
    error.value = getErrorMessage(loadError)
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  try {
    priceTables.value = await listPriceTables()
  } catch {
    // handled by the list request
  }

  await load()
})

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
  form.value.price_table_id = priceTables.value[0]?.id ?? 0
  formError.value = null
  modalOpen.value = true
}

function openEdit(customer: Customer) {
  editing.value = customer
  form.value = {
    cnpj: customer.cnpj,
    legal_name: customer.legal_name,
    trade_name: customer.trade_name,
    city: customer.city,
    state: customer.state,
    price_table_id: customer.price_table.id,
    credit_limit: customer.credit_limit,
    credit_used: customer.credit_used,
    blocked: customer.blocked,
    block_reason: customer.block_reason ?? '',
    email: customer.email ?? '',
    password: '',
  }
  formError.value = null
  modalOpen.value = true
}

function payload(): CustomerPayload {
  return {
    cnpj: form.value.cnpj.trim(),
    legal_name: form.value.legal_name.trim(),
    trade_name: form.value.trade_name.trim(),
    city: form.value.city.trim(),
    state: form.value.state.trim().toUpperCase(),
    price_table_id: Number(form.value.price_table_id),
    credit_limit: Number(form.value.credit_limit) || 0,
    credit_used: Number(form.value.credit_used) || 0,
    blocked: Boolean(form.value.blocked),
    block_reason: form.value.blocked ? form.value.block_reason?.trim() || null : null,
    email: form.value.email?.trim() || null,
    password: form.value.password?.trim() || null,
  }
}

async function save() {
  saving.value = true
  formError.value = null

  try {
    if (editing.value) {
      await updateCustomer(editing.value.id, payload())
      toast.success('Cliente atualizado com sucesso.')
    } else {
      await createCustomer(payload())
      toast.success('Cliente criado com sucesso.')
    }

    modalOpen.value = false
    await load()
  } catch (saveError) {
    formError.value = getErrorMessage(saveError)
  } finally {
    saving.value = false
  }
}

function askDelete(customer: Customer) {
  deleteTarget.value = customer
}

async function confirmDelete() {
  if (!deleteTarget.value) return

  deleting.value = true

  try {
    await deleteCustomer(deleteTarget.value.id)
    toast.success('Cliente removido.')
    deleteTarget.value = null

    if (customers.value.length === 1 && page.value > 1) {
      page.value -= 1
    }

    await load()
  } catch (deleteError) {
    toast.error(getErrorMessage(deleteError))
  } finally {
    deleting.value = false
  }
}

function creditPercentage(customer: Customer) {
  if (customer.credit_limit <= 0) return 0

  return Math.min(100, Math.round((customer.credit_used / customer.credit_limit) * 100))
}
</script>

<template>
  <div class="space-y-5">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Clientes</h1>
        <p class="mt-1 text-sm text-slate-500">{{ meta.total }} empresas cadastradas.</p>
      </div>
      <button type="button" class="btn btn-primary btn-sm" @click="openCreate">
        <Plus class="size-4" /> Novo cliente
      </button>
    </div>

    <div class="card p-4">
      <form class="flex flex-col gap-2 sm:flex-row sm:items-center" @submit.prevent="submitFilters">
        <div class="relative flex-1">
          <Search class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-slate-400" />
          <input v-model="search" type="search" class="input pl-10" placeholder="Buscar por razão social, nome fantasia ou CNPJ..." />
        </div>
        <select v-model="blockedFilter" class="input sm:w-52" @change="submitFilters">
          <option value="">Todos os clientes</option>
          <option value="0">Somente liberados</option>
          <option value="1">Somente bloqueados</option>
        </select>
        <button type="submit" class="btn btn-dark shrink-0">Filtrar</button>
      </form>
    </div>

    <LoadingSpinner v-if="loading" label="Carregando clientes..." />

    <div v-else-if="error" class="card flex flex-col items-center gap-3 px-6 py-12 text-center">
      <TriangleAlert class="size-9 text-amber-500" />
      <p class="text-sm font-medium text-slate-700">{{ error }}</p>
      <button type="button" class="btn btn-primary btn-sm" @click="load">Tentar novamente</button>
    </div>

    <EmptyState
      v-else-if="!customers.length"
      title="Nenhum cliente encontrado"
      description="Ajuste os filtros ou cadastre uma nova empresa."
    >
      <template #action>
        <button type="button" class="btn btn-primary" @click="openCreate">
          <Plus class="size-4" /> Novo cliente
        </button>
      </template>
    </EmptyState>

    <template v-else>
      <div class="card overflow-x-auto">
        <table class="w-full min-w-[920px]">
          <thead class="border-b border-slate-200 bg-slate-50">
            <tr>
              <th class="table-th">Empresa</th>
              <th class="table-th">CNPJ</th>
              <th class="table-th">Local</th>
              <th class="table-th">Tabela</th>
              <th class="table-th">Crédito</th>
              <th class="table-th">Status</th>
              <th class="table-th text-right">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="customer in customers" :key="customer.id" class="hover:bg-slate-50/70">
              <td class="table-td">
                <div class="flex items-center gap-3">
                  <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-400">
                    <Building2 class="size-4" />
                  </div>
                  <div class="min-w-0">
                    <p class="max-w-56 truncate font-medium text-slate-900">{{ customer.trade_name }}</p>
                    <p class="max-w-56 truncate text-[11px] text-slate-400">{{ customer.legal_name }}</p>
                  </div>
                </div>
              </td>
              <td class="table-td whitespace-nowrap">{{ formatCnpj(customer.cnpj) }}</td>
              <td class="table-td whitespace-nowrap">{{ customer.city }} · {{ customer.state }}</td>
              <td class="table-td">
                <span class="badge bg-slate-100 text-slate-600">{{ customer.price_table.code }}</span>
              </td>
              <td class="table-td">
                <p class="text-xs font-medium text-slate-800">
                  {{ formatCurrency(customer.credit_used) }} / {{ formatCurrency(customer.credit_limit) }}
                </p>
                <div class="mt-1 h-1.5 w-32 overflow-hidden rounded-full bg-slate-100">
                  <div
                    class="h-full rounded-full"
                    :class="creditPercentage(customer) >= 90 ? 'bg-red-500' : 'bg-emerald-500'"
                    :style="{ width: `${creditPercentage(customer)}%` }"
                  />
                </div>
              </td>
              <td class="table-td">
                <span
                  class="badge"
                  :class="customer.blocked ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700'"
                >
                  <Ban v-if="customer.blocked" class="size-3" />
                  {{ customer.blocked ? 'Bloqueado' : 'Liberado' }}
                </span>
              </td>
              <td class="table-td">
                <div class="flex items-center justify-end gap-1">
                  <button type="button" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" title="Editar" @click="openEdit(customer)">
                    <Pencil class="size-4" />
                  </button>
                  <button type="button" class="rounded-lg p-2 text-slate-400 transition hover:bg-red-50 hover:text-red-600" title="Excluir" @click="askDelete(customer)">
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
      :title="editing ? 'Editar cliente' : 'Novo cliente'"
      :description="editing ? formatCnpj(editing.cnpj) : 'Cadastre a empresa e defina a tabela e o crédito.'"
      width="xl"
      @close="modalOpen = false"
    >
      <div v-if="formError" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
        {{ formError }}
      </div>

      <form id="customer-form" class="grid gap-4 sm:grid-cols-2" @submit.prevent="save">
        <div>
          <label class="label" for="customer-cnpj">CNPJ *</label>
          <input id="customer-cnpj" v-model="form.cnpj" required maxlength="20" class="input" placeholder="00.000.000/0000-00" />
        </div>

        <div>
          <label class="label" for="customer-email">E-mail de acesso</label>
          <input id="customer-email" v-model="form.email" type="email" maxlength="255" class="input" placeholder="compras@empresa.com.br" />
        </div>

        <div>
          <label class="label" for="customer-legal">Razão social *</label>
          <input id="customer-legal" v-model="form.legal_name" required maxlength="255" class="input" />
        </div>

        <div>
          <label class="label" for="customer-trade">Nome fantasia *</label>
          <input id="customer-trade" v-model="form.trade_name" required maxlength="255" class="input" />
        </div>

        <div>
          <label class="label" for="customer-city">Cidade *</label>
          <input id="customer-city" v-model="form.city" required maxlength="120" class="input" />
        </div>

        <div>
          <label class="label" for="customer-state">UF *</label>
          <input id="customer-state" v-model="form.state" required maxlength="2" class="input uppercase" placeholder="PR" />
        </div>

        <div>
          <label class="label" for="customer-table">Tabela de preço *</label>
          <select id="customer-table" v-model.number="form.price_table_id" required class="input">
            <option v-for="table in priceTables" :key="table.id" :value="table.id">
              {{ table.code }} · {{ table.description }}
            </option>
          </select>
        </div>

        <div>
          <label class="label" for="customer-password">
            Senha {{ editing ? '(deixe em branco para manter)' : '' }}
          </label>
          <input id="customer-password" v-model="form.password" type="password" minlength="6" maxlength="72" class="input" placeholder="Mínimo de 6 caracteres" />
        </div>

        <div>
          <label class="label" for="customer-limit">Limite de crédito *</label>
          <input id="customer-limit" v-model.number="form.credit_limit" type="number" min="0" step="0.01" required class="input" />
        </div>

        <div>
          <label class="label" for="customer-used">Crédito utilizado</label>
          <input id="customer-used" v-model.number="form.credit_used" type="number" min="0" step="0.01" class="input" />
        </div>

        <div class="sm:col-span-2">
          <label class="flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-700">
            <input v-model="form.blocked" type="checkbox" class="size-4 accent-red-600" />
            Bloquear este cliente para novos pedidos
          </label>
        </div>

        <div v-if="form.blocked" class="sm:col-span-2">
          <label class="label" for="customer-block-reason">Motivo do bloqueio *</label>
          <input id="customer-block-reason" v-model="form.block_reason" required maxlength="255" class="input" placeholder="Ex.: inadimplência na fatura 123" />
        </div>
      </form>

      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="modalOpen = false">Cancelar</button>
        <button type="submit" form="customer-form" class="btn btn-primary btn-sm" :disabled="saving">
          <Loader2 v-if="saving" class="size-4 animate-spin" />
          {{ editing ? 'Salvar alterações' : 'Criar cliente' }}
        </button>
      </template>
    </AppModal>

    <AppModal
      :open="Boolean(deleteTarget)"
      title="Excluir cliente"
      description="Esta ação não pode ser desfeita."
      @close="deleteTarget = null"
    >
      <p class="text-sm text-slate-600">
        Deseja realmente excluir
        <strong class="font-semibold text-slate-900">{{ deleteTarget?.trade_name }}</strong>?
        O acesso e o histórico do cliente serão removidos.
      </p>

      <template #footer>
        <button type="button" class="btn btn-outline btn-sm" @click="deleteTarget = null">Cancelar</button>
        <button type="button" class="btn btn-danger btn-sm" :disabled="deleting" @click="confirmDelete">
          <Loader2 v-if="deleting" class="size-4 animate-spin" />
          Excluir cliente
        </button>
      </template>
    </AppModal>
  </div>
</template>
