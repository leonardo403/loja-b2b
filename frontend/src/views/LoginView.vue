<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowRight, CheckCircle2, Lock, Mail, Shield, Store } from '@lucide/vue'
import AppLogo from '@/components/AppLogo.vue'
import { getErrorMessage } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import { useToastStore } from '@/stores/toast'

const auth = useAuthStore()
const cart = useCartStore()
const toast = useToastStore()
const router = useRouter()
const route = useRoute()

const email = ref('')
const password = ref('')
const error = ref<string | null>(null)
const showPassword = ref(false)

const demoAccounts = [
  { label: 'Administrador', role: 'Acesso ao painel · sem conta corporativa', email: 'admin@loja.test', password: 'admin123' },
  { label: 'Bom Preço', role: 'Tabela PADRAO', email: 'compras@bompreco.com.br', password: 'senha123' },
  {
    label: 'Atacadão Central',
    role: 'Tabela ATACADISTA · crédito no limite',
    email: 'compras@atacadocentral.com.br',
    password: 'senha123',
  },
  {
    label: 'Empório São Jorge',
    role: 'Cliente bloqueado',
    email: 'compras@emporiosaojorge.com.br',
    password: 'senha123',
  },
]

const submitting = computed(() => auth.loading)

function fillDemo(account: (typeof demoAccounts)[number]) {
  email.value = account.email
  password.value = account.password
  error.value = null
}

async function submit() {
  error.value = null

  try {
    const user = await auth.login(email.value, password.value)

    if (!user.is_admin) {
      await cart.sync()
    }

    toast.success(`Bem-vindo, ${user.customer?.trade_name ?? user.name}!`)

    const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : null

    if (redirect) {
      router.push(redirect)
    } else {
      router.push(user.is_admin ? { name: 'admin-dashboard' } : { name: 'home' })
    }
  } catch (loginError) {
    error.value = getErrorMessage(loginError)
  }
}
</script>

<template>
  <div class="grid min-h-screen bg-slate-50 lg:grid-cols-2">
    <section class="relative hidden overflow-hidden bg-slate-900 px-12 py-14 text-white lg:flex lg:flex-col">
      <div class="absolute -top-24 -right-24 size-96 rounded-full bg-emerald-500/10 blur-3xl" />
      <div class="absolute -bottom-32 -left-20 size-96 rounded-full bg-emerald-500/10 blur-3xl" />

      <AppLogo light />

      <div class="relative mt-auto max-w-lg">
        <span class="badge bg-emerald-500/15 text-emerald-300">Plataforma B2B</span>
        <h1 class="mt-5 text-4xl leading-tight font-bold tracking-tight">
          O preço certo para o seu negócio, sempre.
        </h1>
        <p class="mt-4 text-base leading-relaxed text-slate-400">
          Acesse sua tabela de preços exclusiva, compre no atacado e acompanhe suas condições de
          crédito e pagamento em um só lugar.
        </p>

        <ul class="mt-8 space-y-4 text-sm text-slate-300">
          <li class="flex items-start gap-3">
            <CheckCircle2 class="mt-0.5 size-5 shrink-0 text-emerald-400" />
            Preços diferenciados por tabela e faixa de atacado
          </li>
          <li class="flex items-start gap-3">
            <CheckCircle2 class="mt-0.5 size-5 shrink-0 text-emerald-400" />
            Limite de crédito e condições de pagamento integrados
          </li>
          <li class="flex items-start gap-3">
            <CheckCircle2 class="mt-0.5 size-5 shrink-0 text-emerald-400" />
            Pedidos recorrentes com histórico completo
          </li>
        </ul>
      </div>

      <div class="relative mt-auto flex items-center gap-2 pt-10 text-xs text-slate-500">
        <Store class="size-4" />
        Venda exclusiva para CNPJ
      </div>
    </section>

    <section class="flex items-center justify-center px-4 py-10 sm:px-8">
      <div class="w-full max-w-md">
        <div class="mb-8 flex justify-center lg:hidden">
          <AppLogo />
        </div>

        <div class="card p-6 sm:p-8">
          <div class="mb-6">
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">Entrar na conta</h2>
            <p class="mt-1 text-sm text-slate-500">
              Empresas compram com a tabela de preços da sua conta. Administradores acessam o
              painel sem vínculo corporativo.
            </p>
          </div>

          <div
            v-if="error"
            class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3.5 py-2.5 text-sm font-medium text-red-700"
          >
            {{ error }}
          </div>

          <form class="space-y-4" @submit.prevent="submit">
            <div>
              <label for="email" class="label">E-mail</label>
              <div class="relative">
                <Mail class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-slate-400" />
                <input
                  id="email"
                  v-model="email"
                  type="email"
                  required
                  autocomplete="email"
                  class="input pl-10"
                  placeholder="seu@email.com"
                />
              </div>
            </div>

            <div>
              <label for="password" class="label">Senha</label>
              <div class="relative">
                <Lock class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-slate-400" />
                <input
                  id="password"
                  v-model="password"
                  :type="showPassword ? 'text' : 'password'"
                  required
                  autocomplete="current-password"
                  class="input pr-16 pl-10"
                  placeholder="••••••••"
                />
                <button
                  type="button"
                  class="absolute top-1/2 right-3 -translate-y-1/2 text-xs font-semibold text-slate-500 hover:text-slate-800"
                  @click="showPassword = !showPassword"
                >
                  {{ showPassword ? 'Ocultar' : 'Mostrar' }}
                </button>
              </div>
            </div>

            <button type="submit" class="btn btn-primary w-full" :disabled="submitting">
              <span v-if="submitting">Entrando...</span>
              <template v-else>
                Entrar
                <ArrowRight class="size-4" />
              </template>
            </button>
          </form>

          <div class="mt-6 border-t border-slate-200 pt-5">
            <p class="mb-2.5 flex items-center gap-1.5 text-xs font-semibold tracking-wide text-slate-500 uppercase">
              <Shield class="size-3.5" /> Contas de demonstração
            </p>
            <div class="space-y-2">
              <button
                v-for="account in demoAccounts"
                :key="account.email"
                type="button"
                class="flex w-full items-center justify-between gap-3 rounded-lg border border-slate-200 px-3 py-2 text-left transition hover:border-emerald-400 hover:bg-emerald-50/50"
                @click="fillDemo(account)"
              >
                <div>
                  <p class="text-xs font-semibold text-slate-800">{{ account.label }}</p>
                  <p class="text-[11px] text-slate-400">{{ account.role }}</p>
                </div>
                <ArrowRight class="size-3.5 shrink-0 text-slate-400" />
              </button>
            </div>
          </div>
        </div>

        <p class="mt-5 text-center text-xs text-slate-400">
          Ao entrar você concorda com os termos de uso comercial.
        </p>
      </div>
    </section>
  </div>
</template>
