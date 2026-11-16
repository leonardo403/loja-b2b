import { api } from '@/services/api'
import type { Cart, CartLineInput, Order, Paginated } from '@/types'

export async function previewCart(
  items: CartLineInput[],
  paymentCondition?: string | null,
): Promise<Cart> {
  const { data } = await api.post<{ data: Cart }>('/cart/preview', {
    items,
    payment_condition: paymentCondition ?? null,
  })

  return data.data
}

export async function createOrder(payload: {
  items: CartLineInput[]
  payment_condition: string
  notes?: string | null
}): Promise<Order> {
  const { data } = await api.post<{ data: Order }>('/orders', payload)

  return data.data
}

export async function getMyOrders(page = 1, perPage = 10): Promise<Paginated<Order>> {
  const { data } = await api.get<Paginated<Order>>('/orders', {
    params: { page, per_page: perPage },
  })

  return data
}

export async function getMyOrder(id: number | string): Promise<Order> {
  const { data } = await api.get<{ data: Order }>(`/orders/${id}`)

  return data.data
}
