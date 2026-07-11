import { apiRequest } from './apiClient'
import { paginatedPath, type PaginatedResponse, type PaginationParams } from './pagination'

type CollectionResponse<T> = {
  data: T[]
}

type SingleResponse<T> = { data: T }

export type Warehouse = {
  id: number
  branch_id?: number | null
  name: string
  code: string
  address?: string | null
  manager_user_id?: number | null
  status: string
  stock_lots_count?: number
  stock_movements_count?: number
}

export type InventoryItem = {
  id: number
  sku: string
  name: string
  category: string
  unit: string
  description?: string | null
  minimum_stock_level: string
  track_expiry: boolean
  status: string
  stock_lots_count?: number
}

export type StockSummaryRow = {
  inventory_item_id: number
  sku: string
  name: string
  category: string
  unit: string
  minimum_stock_level: string
  available_quantity: string
  reserved_quantity: string
  low_stock: boolean
  track_expiry: boolean
  status: string
}

export type StockLot = {
  id: number
  warehouse_id?: number
  inventory_item_id?: number
  source_type: string
  source_id: number | null
  quantity?: string
  remaining_quantity: string
  reserved_quantity?: string
  expiry_date: string | null
  received_at?: string
  warehouse?: {
    code: string
    name: string
  }
  inventory_item?: {
    sku: string
    name: string
    unit: string
  }
}

export type WarehouseInput = {
  branch_id?: number | null
  name: string
  code: string
  address?: string | null
  manager_user_id?: number | null
  status?: string
}

export type InventoryItemInput = {
  sku: string
  name: string
  category: string
  unit: string
  description?: string | null
  minimum_stock_level?: number
  track_expiry?: boolean
  status?: string
}

export type ReceiveStockInput = {
  warehouse_id: number
  inventory_item_id: number
  quantity: number
  source_type: string
  source_id?: number | null
  expiry_date?: string | null
  received_at: string
  notes?: string | null
}

export type AdjustStockInput = {
  warehouse_id: number
  inventory_item_id: number
  stock_lot_id?: number | null
  movement_type: string
  quantity: number
  expiry_date?: string | null
  notes: string
}

export type StockMovement = {
  id: number
  movement_type: string
  quantity: string
  notes: string | null
  created_at: string
  warehouse?: {
    code: string
    name: string
  }
  inventory_item?: {
    sku: string
    name: string
    unit: string
  }
  stock_lot?: {
    id: number
    source_type: string
    source_id: number | null
  }
}

export async function getWarehouses() {
  const response = await apiRequest<CollectionResponse<Warehouse>>('/warehouses?per_page=100')

  return response.data
}
export function getWarehousesPage(params: PaginationParams) { return apiRequest<PaginatedResponse<Warehouse>>(paginatedPath('/warehouses', params)) }

export async function getWarehouse(id: number) {
  return (await apiRequest<SingleResponse<Warehouse>>(`/warehouses/${id}`)).data
}

export async function createWarehouse(input: WarehouseInput) {
  return (await apiRequest<SingleResponse<Warehouse>>('/warehouses', { method: 'POST', body: JSON.stringify(input) })).data
}

export async function updateWarehouse(id: number, input: WarehouseInput) {
  return (await apiRequest<SingleResponse<Warehouse>>(`/warehouses/${id}`, { method: 'PATCH', body: JSON.stringify(input) })).data
}

export async function deleteWarehouse(id: number) {
  return apiRequest(`/warehouses/${id}`, { method: 'DELETE' })
}

export async function getInventoryItems() {
  const response = await apiRequest<CollectionResponse<InventoryItem>>('/inventory-items?per_page=100')

  return response.data
}
export function getInventoryItemsPage(params: PaginationParams) { return apiRequest<PaginatedResponse<InventoryItem>>(paginatedPath('/inventory-items', params)) }

export async function getInventoryItem(id: number) {
  return (await apiRequest<SingleResponse<InventoryItem>>(`/inventory-items/${id}`)).data
}

export async function createInventoryItem(input: InventoryItemInput) {
  return (await apiRequest<SingleResponse<InventoryItem>>('/inventory-items', { method: 'POST', body: JSON.stringify(input) })).data
}

export async function updateInventoryItem(id: number, input: InventoryItemInput) {
  return (await apiRequest<SingleResponse<InventoryItem>>(`/inventory-items/${id}`, { method: 'PATCH', body: JSON.stringify(input) })).data
}

export async function deleteInventoryItem(id: number) {
  return apiRequest(`/inventory-items/${id}`, { method: 'DELETE' })
}

export async function getInventoryItemStock(id: number) {
  return (await apiRequest<SingleResponse<InventoryItem>>(`/inventory-items/${id}/stock`)).data
}

export async function getInventoryItemMovements(id: number) {
  return (await apiRequest<CollectionResponse<StockMovement>>(`/inventory-items/${id}/movements`)).data
}
export function getInventoryItemMovementsPage(id: number, params: PaginationParams) { return apiRequest<PaginatedResponse<StockMovement>>(paginatedPath(`/inventory-items/${id}/movements`, params)) }

export async function getStockLots() {
  const response = await apiRequest<CollectionResponse<StockLot>>('/stock/lots?per_page=100&available_only=1')

  return response.data
}
export function getStockLotsPage(params: PaginationParams) { return apiRequest<PaginatedResponse<StockLot>>(paginatedPath('/stock/lots', params)) }

export async function getStockLot(id: number) {
  return (await apiRequest<SingleResponse<StockLot>>(`/stock/lots/${id}`)).data
}

export async function receiveStock(input: ReceiveStockInput) {
  return (await apiRequest<SingleResponse<StockMovement>>('/stock/movements/receive', { method: 'POST', body: JSON.stringify(input) })).data
}

export async function adjustStock(input: AdjustStockInput) {
  return (await apiRequest<SingleResponse<StockMovement>>('/stock/movements/adjust', { method: 'POST', body: JSON.stringify(input) })).data
}

export async function getStockMovements() {
  const response = await apiRequest<CollectionResponse<StockMovement>>('/stock/movements')

  return response.data
}
export function getStockMovementsPage(params: PaginationParams) { return apiRequest<PaginatedResponse<StockMovement>>(paginatedPath('/stock/movements', params)) }

export async function getStockSummary() {
  const response = await apiRequest<CollectionResponse<StockSummaryRow>>('/stock/summary')

  return response.data
}
export function getStockSummaryPage(params: PaginationParams) { return apiRequest<PaginatedResponse<StockSummaryRow>>(paginatedPath('/stock/summary', params)) }

export async function getLowStock() {
  const response = await apiRequest<CollectionResponse<StockSummaryRow>>('/stock/low-stock')

  return response.data
}
export function getLowStockPage(params: PaginationParams) { return apiRequest<PaginatedResponse<StockSummaryRow>>(paginatedPath('/stock/low-stock', params)) }

export async function getExpiringStock() {
  const response = await apiRequest<CollectionResponse<StockLot>>('/stock/expiring?days=30')

  return response.data
}
export function getExpiringStockPage(params: PaginationParams) { return apiRequest<PaginatedResponse<StockLot>>(paginatedPath('/stock/expiring', { days: 30, ...params })) }
