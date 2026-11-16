import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { previewCart } from '@/services/order.service'
import { useAuthStore } from '@/stores/auth'
import type { Cart, CartLineInput, Product } from '@/types'

const STORAGE_KEY = 'loja_b2b_cart'

interface CartLine {
  productId: number
  quantity: number
}

function loadLines(): CartLine[] {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)

    return raw ? (JSON.parse(raw) as CartLine[]) : []
  } catch {
    return []
  }
}

export const useCartStore = defineStore('cart', () => {
  const lines = ref<CartLine[]>(loadLines())
  const preview = ref<Cart | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  const itemCount = computed(() => lines.value.reduce((total, line) => total + line.quantity, 0))
  const isEmpty = computed(() => lines.value.length === 0)

  function persist() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(lines.value))
  }

  function payload(): CartLineInput[] {
    return lines.value.map((line) => ({ product_id: line.productId, quantity: line.quantity }))
  }

  function add(product: Product, quantity = 1) {
    const line = lines.value.find((item) => item.productId === product.id)

    if (line) {
      line.quantity = Math.min(line.quantity + quantity, product.stock_quantity || line.quantity)
    } else {
      lines.value.push({
        productId: product.id,
        quantity: Math.min(quantity, Math.max(product.stock_quantity, 1)),
      })
    }

    persist()
  }

  function setQuantity(productId: number, quantity: number) {
    const line = lines.value.find((item) => item.productId === productId)

    if (!line) return

    if (quantity <= 0) {
      remove(productId)
      return
    }

    line.quantity = quantity
    persist()
  }

  function remove(productId: number) {
    lines.value = lines.value.filter((item) => item.productId !== productId)
    persist()
  }

  function clear() {
    lines.value = []
    preview.value = null
    persist()
  }

  async function sync(paymentCondition?: string | null) {
    const auth = useAuthStore()

    if (!lines.value.length) {
      preview.value = null
      error.value = null
      return null
    }

    if (!auth.isAuthenticated) {
      preview.value = null
      return null
    }

    loading.value = true
    error.value = null

    try {
      preview.value = await previewCart(payload(), paymentCondition)
      return preview.value
    } catch (syncError) {
      error.value = syncError instanceof Error ? syncError.message : 'Não foi possível atualizar o carrinho.'
      throw syncError
    } finally {
      loading.value = false
    }
  }

  return {
    lines,
    preview,
    loading,
    error,
    itemCount,
    isEmpty,
    add,
    setQuantity,
    remove,
    clear,
    sync,
  }
})
