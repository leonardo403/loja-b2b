import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { TOKEN_KEY } from '@/services/api'
import { loginRequest, logoutRequest, meRequest } from '@/services/auth.service'
import type { User } from '@/types'

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(localStorage.getItem(TOKEN_KEY))
  const user = ref<User | null>(null)
  const loading = ref(false)
  const initialized = ref(false)

  const isAuthenticated = computed(() => Boolean(token.value))
  const isAdmin = computed(() => user.value?.is_admin ?? false)
  const customer = computed(() => user.value?.customer ?? null)

  function setToken(value: string | null) {
    token.value = value

    if (value) {
      localStorage.setItem(TOKEN_KEY, value)
    } else {
      localStorage.removeItem(TOKEN_KEY)
    }
  }

  async function login(email: string, password: string) {
    loading.value = true

    try {
      const payload = await loginRequest(email, password)
      setToken(payload.token)
      user.value = payload.user
      initialized.value = true

      return payload.user
    } finally {
      loading.value = false
    }
  }

  async function fetchMe() {
    if (!token.value) {
      initialized.value = true
      return
    }

    try {
      user.value = await meRequest()
    } catch {
      setToken(null)
      user.value = null
    } finally {
      initialized.value = true
    }
  }

  async function ensureLoaded() {
    if (!initialized.value) {
      await fetchMe()
    }
  }

  async function logout() {
    try {
      if (token.value) {
        await logoutRequest()
      }
    } catch {
      // ignore network errors while logging out locally
    } finally {
      setToken(null)
      user.value = null
      initialized.value = true
    }
  }

  return {
    token,
    user,
    loading,
    initialized,
    isAuthenticated,
    isAdmin,
    customer,
    login,
    fetchMe,
    ensureLoaded,
    logout,
  }
})
