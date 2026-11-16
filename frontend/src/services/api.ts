import axios, { type AxiosError } from 'axios'

export const TOKEN_KEY = 'loja_b2b_token'

export const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? '/api',
  headers: {
    Accept: 'application/json',
  },
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem(TOKEN_KEY)

  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  return config
})

api.interceptors.response.use(
  (response) => response,
  (error: AxiosError) => {
    const isLogin = error.config?.url?.includes('/auth/login')

    if (error.response?.status === 401 && !isLogin) {
      localStorage.removeItem(TOKEN_KEY)

      if (window.location.pathname !== '/login') {
        window.location.assign('/login')
      }
    }

    return Promise.reject(error)
  },
)

interface ApiErrorBody {
  message?: string
  errors?: Record<string, string[]>
}

export function getErrorMessage(error: unknown): string {
  if (axios.isAxiosError<ApiErrorBody>(error)) {
    const body = error.response?.data

    if (body?.errors) {
      const firstError = Object.values(body.errors)[0]

      if (firstError?.length) {
        return firstError[0]
      }
    }

    if (body?.message) {
      return body.message
    }

    if (!error.response) {
      return 'Não foi possível conectar ao servidor. Verifique se a API está rodando.'
    }
  }

  return 'Ocorreu um erro inesperado. Tente novamente.'
}
