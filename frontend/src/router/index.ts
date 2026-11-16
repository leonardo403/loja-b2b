import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

declare module 'vue-router' {
  interface RouteMeta {
    requiresAuth?: boolean
    requiresAdmin?: boolean
    guestOnly?: boolean
    title?: string
  }
}

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/LoginView.vue'),
      meta: { guestOnly: true, title: 'Entrar' },
    },
    {
      path: '/',
      component: () => import('@/layouts/StoreLayout.vue'),
      children: [
        {
          path: '',
          name: 'home',
          component: () => import('@/views/store/HomeView.vue'),
          meta: { title: 'Início' },
        },
        {
          path: 'produtos',
          name: 'products',
          component: () => import('@/views/store/ProductsView.vue'),
          meta: { title: 'Produtos' },
        },
        {
          path: 'produtos/:id',
          name: 'product',
          component: () => import('@/views/store/ProductDetailView.vue'),
          meta: { title: 'Produto' },
        },
        {
          path: 'carrinho',
          name: 'cart',
          component: () => import('@/views/store/CartView.vue'),
          meta: { requiresAuth: true, title: 'Carrinho' },
        },
        {
          path: 'checkout',
          name: 'checkout',
          component: () => import('@/views/store/CheckoutView.vue'),
          meta: { requiresAuth: true, title: 'Checkout' },
        },
        {
          path: 'pedidos',
          name: 'orders',
          component: () => import('@/views/store/OrdersView.vue'),
          meta: { requiresAuth: true, title: 'Meus pedidos' },
        },
        {
          path: 'pedidos/:id',
          name: 'order',
          component: () => import('@/views/store/OrderDetailView.vue'),
          meta: { requiresAuth: true, title: 'Detalhe do pedido' },
        },
      ],
    },
    {
      path: '/admin',
      component: () => import('@/layouts/AdminLayout.vue'),
      meta: { requiresAuth: true, requiresAdmin: true },
      children: [
        {
          path: '',
          name: 'admin-dashboard',
          component: () => import('@/views/admin/DashboardView.vue'),
          meta: { title: 'Dashboard' },
        },
        {
          path: 'produtos',
          name: 'admin-products',
          component: () => import('@/views/admin/ProductsView.vue'),
          meta: { title: 'Produtos' },
        },
        {
          path: 'clientes',
          name: 'admin-customers',
          component: () => import('@/views/admin/CustomersView.vue'),
          meta: { title: 'Clientes' },
        },
        {
          path: 'tabelas-de-preco',
          name: 'admin-price-tables',
          component: () => import('@/views/admin/PriceTablesView.vue'),
          meta: { title: 'Tabelas de preço' },
        },
        {
          path: 'promocoes',
          name: 'admin-promotions',
          component: () => import('@/views/admin/PromotionsView.vue'),
          meta: { title: 'Promoções' },
        },
        {
          path: 'condicoes-de-pagamento',
          name: 'admin-payment-conditions',
          component: () => import('@/views/admin/PaymentConditionsView.vue'),
          meta: { title: 'Condições de pagamento' },
        },
        {
          path: 'pedidos',
          name: 'admin-orders',
          component: () => import('@/views/admin/OrdersView.vue'),
          meta: { title: 'Pedidos' },
        },
      ],
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/views/NotFoundView.vue'),
      meta: { title: 'Página não encontrada' },
    },
  ],
  scrollBehavior(_to, _from, savedPosition) {
    return savedPosition ?? { top: 0 }
  },
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()
  await auth.ensureLoaded()

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.requiresAdmin && !auth.isAdmin) {
    return auth.isAuthenticated ? { name: 'home' } : { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.guestOnly && auth.isAuthenticated) {
    return auth.isAdmin ? { name: 'admin-dashboard' } : { name: 'home' }
  }

  return true
})

router.afterEach((to) => {
  const title = to.meta.title ? `${to.meta.title} · Atacado Prime` : 'Atacado Prime'
  document.title = title
})

export default router
