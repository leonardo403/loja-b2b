export interface Price {
  unit_price: number
  list_price: number
  wholesale_price: number | null
  wholesale_min_quantity: number | null
  wholesale_applied: boolean
  discount_percentage: number
  on_promotion: boolean
  promotion_ends_at: string | null
}

export interface Product {
  id: number
  sku: string
  ean: string | null
  name: string
  description: string | null
  brand: string | null
  category: string | null
  active: boolean
  stock_quantity: number
  in_stock: boolean
  price: Price | null
}

export interface PriceTable {
  id: number
  code: string
  description: string
  items_count?: number | null
}

export interface Customer {
  id: number
  cnpj: string
  legal_name: string
  trade_name: string
  city: string
  state: string
  price_table: PriceTable
  credit_limit: number
  credit_used: number
  available_credit: number
  blocked: boolean
  block_reason: string | null
  email: string | null
}

export interface User {
  id: number
  name: string
  email: string
  role: 'admin' | 'customer'
  is_admin: boolean
  customer: Customer | null
}

export interface AuthPayload {
  token: string
  token_type: string
  user: User
}

export interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number | null
  to: number | null
}

export interface Paginated<T> {
  data: T[]
  meta: PaginationMeta
}

export interface PaymentCondition {
  id: number
  code: string
  description: string
  minimum_value: number
  surcharge_percentage: number
  is_term: boolean
}

export interface CartItem {
  product_id: number
  sku: string
  name: string
  brand: string | null
  category: string | null
  quantity: number
  stock_quantity: number
  list_price: number
  unit_price: number
  discount_percentage: number
  wholesale_applied: boolean
  line_subtotal: number
  line_list_total: number
  line_savings: number
}

export interface Cart {
  items: CartItem[]
  item_count: number
  total_units: number
  subtotal: number
  savings: number
  surcharge_percentage: number
  surcharge: number
  total: number
  payment_condition: PaymentCondition | null
  warnings: string[]
}

export interface CartLineInput {
  product_id: number
  quantity: number
}

export interface OrderItem {
  product_id: number
  product_name: string
  product_sku: string
  quantity: number
  list_price: number
  unit_price: number
  discount_percentage: number
  wholesale_applied: boolean
  subtotal: number
}

export type OrderStatus = 'pending' | 'confirmed' | 'shipped' | 'canceled'

export interface Order {
  id: number
  number: string
  status: OrderStatus
  status_label: string
  customer: Customer | null
  payment_condition: PaymentCondition | null
  subtotal: number
  savings: number
  surcharge: number
  total: number
  notes: string | null
  created_at: string
  items: OrderItem[]
}

export interface Promotion {
  id: number
  product_id: number
  product_name: string | null
  product_sku: string | null
  discount_percentage: number
  starts_at: string
  ends_at: string
  active: boolean
}

export interface PriceTableItem {
  id: number
  product_id: number
  sku: string
  name: string
  category: string | null
  price: number
  wholesale_price: number | null
  wholesale_min_quantity: number | null
}

export interface DashboardStats {
  totals: {
    products: number
    customers: number
    orders: number
    open_orders: number
    revenue: number
    low_stock: number
  }
  low_stock: Array<{
    id: number
    name: string
    sku: string
    category: string | null
    quantity: number
  }>
  recent_orders: Order[]
}

export interface CatalogFilters {
  categories: string[]
  brands: string[]
}

export interface ProductQuery {
  search?: string
  category?: string
  brand?: string
  sort?: string
  per_page?: number
  page?: number
}

export interface PaginatedProducts extends Paginated<Product> {
  filters: CatalogFilters
}
