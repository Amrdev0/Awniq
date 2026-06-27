import { apiRequest } from './apiClient'

type HealthResponse = {
  data: {
    status: string
    service: string
    environment: string
  }
  message: string
}

export async function getHealth() {
  const response = await apiRequest<HealthResponse>('/health')

  return response.data
}
