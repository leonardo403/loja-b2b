import { api } from '@/services/api'
import type { AuthPayload, User } from '@/types'

export async function loginRequest(
  email: string,
  password: string,
  deviceName = 'web',
): Promise<AuthPayload> {
  const { data } = await api.post<{ data: AuthPayload }>('/auth/login', {
    email,
    password,
    device_name: deviceName,
  })

  return data.data
}

export async function meRequest(): Promise<User> {
  const { data } = await api.get<{ data: User }>('/auth/me')

  return data.data
}

export async function logoutRequest(): Promise<void> {
  await api.post('/auth/logout')
}
