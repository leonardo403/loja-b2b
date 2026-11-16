<script setup lang="ts">
import { ArrowLeft, SearchX } from '@lucide/vue'
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-slate-50 px-4">
    <div class="text-center">
      <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-slate-100 text-slate-400">
        <SearchX class="size-8" />
      </div>
      <p class="mt-6 text-sm font-semibold tracking-widest text-emerald-700 uppercase">Erro 404</p>
      <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Página não encontrada</h1>
      <p class="mt-2 text-sm text-slate-500">O endereço que você acessou não existe ou foi movido.</p>
      <RouterLink :to="{ name: 'home' }" class="btn btn-primary mt-6">
        <ArrowLeft class="size-4" /> Voltar para a loja
      </RouterLink>
    </div>
  </div>
</template>
