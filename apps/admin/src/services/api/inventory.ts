import { apiRequest } from './apiClient'

type CollectionResponse<T> = {
  data: T[]
}

export type Warehouse = {
  id: number
  name: string
  code: string
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
  const response = await apiRequest<CollectionResponse<Warehouse>>('/warehouses')

  return response.data
}

export async function getInventoryItems() {
  const response = await apiRequest<CollectionResponse<InventoryItem>>('/inventory-items')

  return response.data
}

export async function getStockLots() {
  const response = await apiRequest<CollectionResponse<StockLot>>('/stock/lots')

  return response.data
}

export async function getStockMovements() {
  const response = await apiRequest<CollectionResponse<StockMovement>>('/stock/movements')

  return response.data
}

export async function getStockSummary() {
  const response = await apiRequest<CollectionResponse<StockSummaryRow>>('/stock/summary')

  return response.data
}

export async function getLowStock() {
  const response = await apiRequest<CollectionResponse<StockSummaryRow>>('/stock/low-stock')

  return response.data
}

export async function getExpiringStock() {
  const response = await apiRequest<CollectionResponse<StockLot>>('/stock/expiring?days=30')

  return response.data
}
