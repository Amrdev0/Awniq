import { apiRequest } from './apiClient'
import { paginatedPath, type PaginatedResponse, type PaginationParams } from './pagination'

type CollectionResponse<T> = {
  data: T[]
}

type SingleResponse<T> = { data: T }

export type AidBatch = {
  id: number
  batch_number: string
  title: string
  status: string
  scheduled_date: string | null
  description?: string | null
  branch_id?: number | null
  warehouse_id?: number | null
  campaign_id?: number | null
  distributions?: AidDistribution[]
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

export type DistributionItem = {
  id: number
  inventory_item_id: number | null
  stock_lot_id: number | null
  quantity: string | null
  cash_amount: string | null
  currency: string | null
  notes: string | null
  inventory_item?: { sku: string; name: string; unit: string }
}

export type AidDistribution = {
  id: number
  aid_batch_id: number
  beneficiary_id: number
  case_file_id: number | null
  distribution_number: string
  status: string
  scheduled_at: string | null
  delivered_at: string | null
  delivery_method: string
  proof_type: string | null
  failure_reason: string | null
  notes: string | null
  beneficiary?: { code: string; full_name: string }
  case_file?: { case_number: string }
  items?: DistributionItem[]
  items_count?: number
}

export type AidBatchInput = { branch_id?: number | null; warehouse_id?: number | null; title: string; description?: string | null; campaign_id?: number | null; scheduled_date?: string | null }
export type AidDistributionInput = { beneficiary_id: number; case_file_id?: number | null; scheduled_at?: string | null; delivery_method: string; notes?: string | null }
export type DistributionItemInput = { inventory_item_id?: number | null; stock_lot_id?: number | null; quantity?: number | null; cash_amount?: number | null; currency?: string | null; notes?: string | null }

export async function getAidBatches() {
  const response = await apiRequest<CollectionResponse<AidBatch>>('/aid-batches?per_page=100')

  return response.data
}
export function getAidBatchesPage(params: PaginationParams) { return apiRequest<PaginatedResponse<AidBatch>>(paginatedPath('/aid-batches', params)) }

export async function getAidBatch(id: number) { return (await apiRequest<SingleResponse<AidBatch>>(`/aid-batches/${id}`)).data }
export async function createAidBatch(input: AidBatchInput) { return (await apiRequest<SingleResponse<AidBatch>>('/aid-batches', { method: 'POST', body: JSON.stringify(input) })).data }
export async function updateAidBatch(id: number, input: AidBatchInput) { return (await apiRequest<SingleResponse<AidBatch>>(`/aid-batches/${id}`, { method: 'PATCH', body: JSON.stringify(input) })).data }
export async function deleteAidBatch(id: number) { return apiRequest(`/aid-batches/${id}`, { method: 'DELETE' }) }
export async function runAidBatchAction(id: number, action: 'submit-approval' | 'approve' | 'cancel' | 'complete') {
  const headers = action === 'approve' ? { 'Idempotency-Key': crypto.randomUUID() } : undefined
  return (await apiRequest<SingleResponse<AidBatch>>(`/aid-batches/${id}/${action}`, { method: 'POST', headers })).data
}
export async function getEligibleBeneficiaries(id: number) { return (await apiRequest<CollectionResponse<{ id: number; code: string; full_name: string }>>(`/aid-batches/${id}/eligible-beneficiaries`)).data }
export function getEligibleBeneficiariesPage(id: number, params: PaginationParams) { return apiRequest<PaginatedResponse<{ id: number; code: string; full_name: string }>>(paginatedPath(`/aid-batches/${id}/eligible-beneficiaries`, params)) }
export async function getAidBatchStockCheck(id: number) { return (await apiRequest<SingleResponse<Record<string, unknown>>>(`/aid-batches/${id}/stock-check`)).data }
export async function getAidBatchDistributions(id: number) { return (await apiRequest<CollectionResponse<AidDistribution>>(`/aid-batches/${id}/distributions`)).data }
export function getAidBatchDistributionsPage(id: number, params: PaginationParams) { return apiRequest<PaginatedResponse<AidDistribution>>(paginatedPath(`/aid-batches/${id}/distributions`, params)) }
export async function createAidDistribution(batchId: number, input: AidDistributionInput) { return (await apiRequest<SingleResponse<AidDistribution>>(`/aid-batches/${batchId}/distributions`, { method: 'POST', body: JSON.stringify(input) })).data }
export async function updateAidDistribution(id: number, input: AidDistributionInput) { return (await apiRequest<SingleResponse<AidDistribution>>(`/aid-distributions/${id}`, { method: 'PATCH', body: JSON.stringify(input) })).data }
export async function deleteAidDistribution(batchId: number, id: number) { return apiRequest(`/aid-batches/${batchId}/distributions/${id}`, { method: 'DELETE' }) }
export async function getAidDistribution(id: number) { return (await apiRequest<SingleResponse<AidDistribution>>(`/aid-distributions/${id}`)).data }
export function getDistributionItemsPage(id: number, params: PaginationParams) { return apiRequest<PaginatedResponse<DistributionItem>>(paginatedPath(`/aid-distributions/${id}/items`, params)) }
export async function createDistributionItem(id: number, input: DistributionItemInput) { return (await apiRequest<SingleResponse<DistributionItem>>(`/aid-distributions/${id}/items`, { method: 'POST', body: JSON.stringify(input) })).data }
export async function updateDistributionItem(distributionId: number, id: number, input: DistributionItemInput) { return (await apiRequest<SingleResponse<DistributionItem>>(`/aid-distributions/${distributionId}/items/${id}`, { method: 'PATCH', body: JSON.stringify(input) })).data }
export async function deleteDistributionItem(distributionId: number, id: number) { return apiRequest(`/aid-distributions/${distributionId}/items/${id}`, { method: 'DELETE' }) }
export async function markDistributionFailed(id: number, failure_reason: string, notes?: string) { return (await apiRequest<SingleResponse<AidDistribution>>(`/aid-distributions/${id}/mark-failed`, { method: 'POST', body: JSON.stringify({ failure_reason, notes }) })).data }
export async function rescheduleDistribution(id: number, scheduled_at: string, notes?: string) { return (await apiRequest<SingleResponse<AidDistribution>>(`/aid-distributions/${id}/reschedule`, { method: 'POST', body: JSON.stringify({ scheduled_at, notes }) })).data }
export async function submitDistributionProof(id: number, form: FormData, delivered = false) {
  const headers = delivered ? { 'Idempotency-Key': crypto.randomUUID() } : undefined
  return (await apiRequest<SingleResponse<AidDistribution>>(`/aid-distributions/${id}/${delivered ? 'mark-delivered' : 'proof'}`, { method: 'POST', body: form, headers })).data
}
