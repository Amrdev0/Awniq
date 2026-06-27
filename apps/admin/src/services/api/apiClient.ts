import { appConfig } from '../../app/config'

export class ApiError extends Error {
  public readonly status: number

  public readonly payload: unknown

  constructor(message: string, status: number, payload: unknown) {
    super(message)
    this.status = status
    this.payload = payload
  }
}

export async function apiRequest<T>(path: string, init: RequestInit = {}): Promise<T> {
  const response = await fetch(`${appConfig.apiBaseUrl}${path}`, {
    ...init,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...init.headers,
    },
  })

  const payload: unknown = await response.json().catch(() => null)

  if (!response.ok) {
    throw new ApiError('API request failed', response.status, payload)
  }

  return payload as T
}
