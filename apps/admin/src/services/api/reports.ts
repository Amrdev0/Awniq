import { apiRequest } from './apiClient'

type SingleResponse<T> = {
  data: T
}

export type DashboardReport = {
  metrics: {
    total_donations_this_month: string
    active_campaigns: number
    pending_cases: number
    approved_beneficiaries: number
    aid_batches_in_progress: number
    completed_distributions: number
    low_stock_items: number
    expiring_stock_lots: number
  }
}

export async function getDashboardReport() {
  const response = await apiRequest<SingleResponse<DashboardReport>>('/reports/dashboard')

  return response.data
}
