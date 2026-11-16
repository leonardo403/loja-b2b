<script setup lang="ts">
import { Building2, Mail, MapPin, Phone } from '@lucide/vue'
import AppLogo from '@/components/AppLogo.vue'
</script>

<template>
  <footer class="mt-auto border-t border-slate-800 bg-slate-900 text-slate-300">
    <div class="container-page grid gap-10 py-12 sm:grid-cols-2 lg:grid-cols-4">
      <div class="space-y-4">
        <AppLogo light />
        <p class="text-sm leading-relaxed text-slate-400">
          Distribuição e atacado para empresas. Preços por tabela, condições especiais e atendimento
          comercial dedicado.
        </p>
      </div>

      <div>
        <h3 class="mb-3 text-sm font-semibold text-white">Navegação</h3>
        <ul class="space-y-2 text-sm">
          <li><RouterLink :to="{ name: 'home' }" class="hover:text-emerald-400">Início</RouterLink></li>
          <li><RouterLink :to="{ name: 'products' }" class="hover:text-emerald-400">Catálogo</RouterLink></li>
          <li><RouterLink :to="{ name: 'orders' }" class="hover:text-emerald-400">Meus pedidos</RouterLink></li>
          <li><RouterLink :to="{ name: 'cart' }" class="hover:text-emerald-400">Carrinho</RouterLink></li>
        </ul>
      </div>

      <div>
        <h3 class="mb-3 text-sm font-semibold text-white">Atendimento</h3>
        <ul class="space-y-2.5 text-sm text-slate-400">
          <li class="flex items-start gap-2"><Phone class="mt-0.5 size-4 shrink-0 text-emerald-400" /> (51) 3000-0000</li>
          <li class="flex items-start gap-2"><Mail class="mt-0.5 size-4 shrink-0 text-emerald-400" /> comercial@atacadolima.com.br</li>
          <li class="flex items-start gap-2"><MapPin class="mt-0.5 size-4 shrink-0 text-emerald-400" /> Porto Alegre · RS</li>
        </ul>
      </div>

      <div>
        <h3 class="mb-3 text-sm font-semibold text-white">Horário comercial</h3>
        <ul class="space-y-1.5 text-sm text-slate-400">
          <li>Segunda a sexta: 8h às 18h</li>
          <li>Sábado: 8h às 12h</li>
        </ul>
        <div class="mt-5 flex items-center gap-2 rounded-lg bg-slate-800 px-3 py-2 text-xs text-slate-300">
          <Building2 class="size-4 text-emerald-400" />
          Venda exclusiva para CNPJ
        </div>
      </div>
    </div>

    <div class="border-t border-slate-800">
      <div class="container-page flex flex-col items-center justify-between gap-2 py-5 text-xs text-slate-500 sm:flex-row">
        <p>© {{ new Date().getFullYear() }} Atacado Prime · Desafio técnico B2B</p>
        <p>Feito com Laravel + Vue</p>
      </div>
    </div>
  </footer>
</template>
