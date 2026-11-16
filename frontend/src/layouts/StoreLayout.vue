<script setup lang="ts">
import StoreFooter from '@/components/StoreFooter.vue'
import StoreHeader from '@/components/StoreHeader.vue'
</script>

<template>
  <div class="flex min-h-screen flex-col bg-slate-50">
    <StoreHeader />
    <main class="flex-1">
      <RouterView v-slot="{ Component }">
        <Transition name="fade" mode="out-in">
          <component :is="Component" />
        </Transition>
      </RouterView>
    </main>
    <StoreFooter />
  </div>
</template>
