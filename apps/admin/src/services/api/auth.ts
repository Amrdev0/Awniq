import { apiRequest } from './apiClient'

export type CurrentUser = {
  id: number
  name: string
  email: string
  status: string
  roles?: string[]
  permissions?: string[]
}

type LoginResponse = {
  data: {
    token: string
    token_type: 'Bearer'
    user: CurrentUser
  }
  message: string
}

type MeResponse = {
  data: CurrentUser
}

export function login(payload: { email: string; password: string }) {
  return apiRequest<LoginResponse>('/auth/login', {
    method: 'POST',
    body: JSON.stringify({ ...payload, device_name: 'admin-browser' }),
  })
}

export function logout() {
  return apiRequest('/auth/logout', { method: 'POST' })
}

export async function getMe() {
  const response = await apiRequest<MeResponse>('/auth/me')

  return response.data
}
