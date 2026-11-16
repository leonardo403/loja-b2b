import { ref } from 'vue'
import { defineStore } from 'pinia'
import type { ToastMessage } from '@/types/ui'

let sequence = 0

export const useToastStore = defineStore('toast', () => {
  const messages = ref<ToastMessage[]>([])

  function push(type: ToastMessage['type'], message: string) {
    const id = ++sequence
    messages.value.push({ id, type, message })

    window.setTimeout(() => dismiss(id), 4200)
  }

  function success(message: string) {
    push('success', message)
  }

  function error(message: string) {
    push('error', message)
  }

  function info(message: string) {
    push('info', message)
  }

  function dismiss(id: number) {
    messages.value = messages.value.filter((message) => message.id !== id)
  }

  return { messages, push, success, error, info, dismiss }
})
