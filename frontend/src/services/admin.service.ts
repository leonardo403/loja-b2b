import { api } from '@/services/api'
import type {
  DashboardStats,
  Order,
  OrderStatus,
  Paginated,
  PaymentCondition,
  PriceTable,
  PriceTableItem,
  Product,
  Promotion,
  Customer,
} from '@/types'

export interface ProductPayload {
  sku: string
  ean?: string | null
  name: string
  description?: string | null
  brand?: string | null
  category?: string | null
  active: boolean
  stock_quantity: number
  price?: number | null
  wholesale_price?: number | null
  wholesale_min_quantity?: number | null
}

export interface CustomerPayload {
  cnpj: string
  legal_name: string
  trade_name: string
  city: string
  state: string
  price_table_id: number
  credit_limit: number
  credit_used: number
  blocked: boolean
  block_reason?: string | null
  email?: string | null
  password?: string | null
}

export interface PromotionPayload {
  product_id: number
  discount_percentage: number
  starts_at: string
  ends_at: string
}

export interface PaymentConditionPayload {
  code: string
  description: string
  minimum_value: number
  surcharge_percentage: number
  is_term: boolean
}

export interface PriceTableItemPayload {
  price: number
  wholesale_price?: number | null
  wholesale_min_quantity?: number | null
}

export async function getDashboard(): Promise<DashboardStats> {
  const { data } = await api.get<{ data: DashboardStats }>('/admin/dashboard')

  return data.data
}

export async function listProducts(params: Record<string, unknown>): Promise<Paginated<Product>> {
  const { data } = await api.get<Paginated<Product>>('/admin/products', { params })

  return data
}

export async function createProduct(payload: ProductPayload): Promise<Product> {
  const { data } = await api.post<{ data: Product }>('/admin/products', payload)

  return data.data
}

export async function updateProduct(id: number, payload: ProductPayload): Promise<Product> {
  const { data } = await api.put<{ data: Product }>(`/admin/products/${id}`, payload)

  return data.data
}

export async function deleteProduct(id: number): Promise<void> {
  await api.delete(`/admin/products/${id}`)
}

export async function listCustomers(params: Record<string, unknown>): Promise<Paginated<Customer>> {
  const { data } = await api.get<Paginated<Customer>>('/admin/customers', { params })

  return data
}

export async function createCustomer(payload: CustomerPayload): Promise<Customer> {
  const { data } = await api.post<{ data: Customer }>('/admin/customers', payload)

  return data.data
}

export async function updateCustomer(id: number, payload: CustomerPayload): Promise<Customer> {
  const { data } = await api.put<{ data: Customer }>(`/admin/customers/${id}`, payload)

  return data.data
}

export async function deleteCustomer(id: number): Promise<void> {
  await api.delete(`/admin/customers/${id}`)
}

export async function listPromotions(params: Record<string, unknown>): Promise<Paginated<Promotion>> {
  const { data } = await api.get<Paginated<Promotion>>('/admin/promotions', { params })

  return data
}

export async function createPromotion(payload: PromotionPayload): Promise<Promotion> {
  const { data } = await api.post<{ data: Promotion }>('/admin/promotions', payload)

  return data.data
}

export async function updatePromotion(id: number, payload: PromotionPayload): Promise<Promotion> {
  const { data } = await api.put<{ data: Promotion }>(`/admin/promotions/${id}`, payload)

  return data.data
}

export async function deletePromotion(id: number): Promise<void> {
  await api.delete(`/admin/promotions/${id}`)
}

export async function listPaymentConditions(
  params: Record<string, unknown> = {},
): Promise<Paginated<PaymentCondition>> {
  const { data } = await api.get<Paginated<PaymentCondition>>('/admin/payment-conditions', { params })

  return data
}

export async function createPaymentCondition(
  payload: PaymentConditionPayload,
): Promise<PaymentCondition> {
  const { data } = await api.post<{ data: PaymentCondition }>('/admin/payment-conditions', payload)

  return data.data
}

export async function updatePaymentCondition(
  id: number,
  payload: PaymentConditionPayload,
): Promise<PaymentCondition> {
  const { data } = await api.put<{ data: PaymentCondition }>(
    `/admin/payment-conditions/${id}`,
    payload,
  )

  return data.data
}

export async function deletePaymentCondition(id: number): Promise<void> {
  await api.delete(`/admin/payment-conditions/${id}`)
}

export async function listPriceTables(): Promise<PriceTable[]> {
  const { data } = await api.get<{ data: PriceTable[] }>('/admin/price-tables')

  return data.data
}

export async function listPriceTableItems(
  priceTableId: number,
  params: Record<string, unknown>,
): Promise<Paginated<PriceTableItem>> {
  const { data } = await api.get<Paginated<PriceTableItem>>(
    `/admin/price-tables/${priceTableId}/items`,
    { params },
  )

  return data
}

export async function updatePriceTableItem(
  priceTableId: number,
  productId: number,
  payload: PriceTableItemPayload,
): Promise<PriceTableItem> {
  const { data } = await api.put<{ data: PriceTableItem }>(
    `/admin/price-tables/${priceTableId}/items/${productId}`,
    payload,
  )

  return data.data
}

export async function listOrders(params: Record<string, unknown>): Promise<Paginated<Order>> {
  const { data } = await api.get<Paginated<Order>>('/admin/orders', { params })

  return data
}

export async function getOrder(id: number): Promise<Order> {
  const { data } = await api.get<{ data: Order }>(`/admin/orders/${id}`)

  return data.data
}

export async function updateOrderStatus(id: number, status: OrderStatus): Promise<Order> {
  const { data } = await api.patch<{ data: Order }>(`/admin/orders/${id}/status`, { status })

  return data.data
}
