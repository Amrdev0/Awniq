import { apiBlobRequest, apiRequest } from './apiClient'
import { paginatedPath, type PaginatedResponse, type PaginationParams } from './pagination'

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

export const reportTypes = ['donations', 'campaigns', 'beneficiaries', 'case-files', 'distributions', 'inventory', 'audit-logs'] as const
export type ReportType = (typeof reportTypes)[number]
export type ReportFilters = { date_from?: string; date_to?: string; status?: string; branch_id?: number; warehouse_id?: number; campaign_id?: number }
export type ExportRecord = { id: number; report_type: string; format: string; status: string; error_message: string | null; completed_at: string | null; created_at: string }

export async function getReport(type: ReportType, filters: ReportFilters = {}) {
  const params = new URLSearchParams(Object.entries(filters).filter(([, value]) => value !== undefined && value !== '').map(([key, value]) => [key, String(value)]))
  return (await apiRequest<SingleResponse<Record<string, unknown>>>(`/reports/${type}${params.size ? `?${params}` : ''}`)).data
}

export async function getExports() {
  return (await apiRequest<{ data: ExportRecord[] }>('/exports')).data
}
export function getExportsPage(params: PaginationParams) { return apiRequest<PaginatedResponse<ExportRecord>>(paginatedPath('/exports', params)) }

export async function createExport(report_type: string, filters: ReportFilters = {}) {
  return (await apiRequest<SingleResponse<ExportRecord>>('/exports', { method: 'POST', body: JSON.stringify({ report_type: report_type.replace('-', '_'), format: 'csv', filters }) })).data
}

export async function downloadExport(record: ExportRecord) {
  const blob = await apiBlobRequest(`/exports/${record.id}/download`)
  const url = URL.createObjectURL(blob)
  const anchor = document.createElement('a')
  anchor.href = url
  anchor.download = `${record.report_type}-export-${record.id}.${record.format}`
  anchor.click()
  URL.revokeObjectURL(url)
}
