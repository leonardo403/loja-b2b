import { api } from '@/services/api'
import type {
  CatalogFilters,
  PaginatedProducts,
  PaymentCondition,
  Product,
  ProductQuery,
} from '@/types'

export async function getFeaturedProducts(limit = 8): Promise<Product[]> {
  const { data } = await api.get<{ data: Product[] }>('/catalog/featured', { params: { limit } })

  return data.data
}

export async function getCatalogFilters(): Promise<CatalogFilters> {
  const { data } = await api.get<{ data: CatalogFilters }>('/catalog/filters')

  return data.data
}

export async function getProducts(query: ProductQuery): Promise<PaginatedProducts> {
  const { data } = await api.get<PaginatedProducts>('/products', { params: query })

  return data
}

export async function getProduct(id: number | string): Promise<Product> {
  const { data } = await api.get<{ data: Product }>(`/products/${id}`)

  return data.data
}

export async function getPaymentConditions(): Promise<PaymentCondition[]> {
  const { data } = await api.get<{ data: PaymentCondition[] }>('/payment-conditions')

  return data.data
}
