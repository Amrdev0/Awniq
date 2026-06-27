import { apiRequest } from './apiClient'

type CollectionResponse<T> = {
  data: T[]
}

export type AidBatch = {
  id: number
  batch_number: string
  title: string
  status: string
  scheduled_date: string | null
  distributions_count?: number
  reservations_count?: number
  warehouse?: {
    code: string
    name: string
  }
  campaign?: {
    title: string
  }
}

export async function getAidBatches() {
  const response = await apiRequest<CollectionResponse<AidBatch>>('/aid-batches')

  return response.data
}
