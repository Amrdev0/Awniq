import { apiRequest } from './apiClient'

type CollectionResponse<T> = {
  data: T[]
}

type SingleResponse<T> = {
  data: T
}

export type Organization = {
  id: number
  name: string
  slug: string
  email: string | null
  default_currency: string
  timezone: string
  status: string
}

export type Branch = {
  id: number
  name: string
  code: string
  city: string | null
  status: string
}

export type User = {
  id: number
  name: string
  email: string
  status: string
  roles?: string[]
}

export type Role = {
  id: number
  name: string
  is_protected: boolean
  permissions?: string[]
}

export type AuditLog = {
  id: number
  action: string
  entity_type: string
  entity_id: number | null
  created_at: string
}

export async function getOrganization() {
  const response = await apiRequest<SingleResponse<Organization>>('/organization')

  return response.data
}

export async function getBranches() {
  const response = await apiRequest<CollectionResponse<Branch>>('/branches')

  return response.data
}

export async function getUsers() {
  const response = await apiRequest<CollectionResponse<User>>('/users')

  return response.data
}

export async function getRoles() {
  const response = await apiRequest<CollectionResponse<Role>>('/roles')

  return response.data
}

export async function getAuditLogs() {
  const response = await apiRequest<CollectionResponse<AuditLog>>('/audit-logs')

  return response.data
}
