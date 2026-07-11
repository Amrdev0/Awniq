import { apiRequest } from './apiClient'
import { paginatedPath, type PaginatedResponse, type PaginationParams } from './pagination'

type CollectionResponse<T> = {
  data: T[]
}

type SingleResponse<T> = {
  data: T
}

type EmptyResponse = {
  data?: unknown
  message?: string
}

export type Organization = {
  id: number
  name: string
  legal_name: string | null
  slug: string
  email: string | null
  phone: string | null
  website: string | null
  logo: string | null
  country: string | null
  city: string | null
  address: string | null
  default_currency: string
  timezone: string
  language: string
  status: string
}

export type Branch = {
  id: number
  name: string
  code: string
  phone: string | null
  email: string | null
  country: string | null
  city: string | null
  address: string | null
  manager_user_id: number | null
  manager?: Pick<User, 'id' | 'name' | 'email'> | null
  status: string
}

export type User = {
  id: number
  name: string
  email: string
  phone: string | null
  branch_id: number | null
  branch?: Pick<Branch, 'id' | 'name' | 'code'> | null
  status: string
  roles?: string[]
}

export type Role = {
  id: number
  name: string
  is_protected: boolean
  permissions?: string[]
}

export type Permission = {
  id: number
  name: string
  guard_name: string
}

export type AuditLog = {
  id: number
  user_id: number | null
  user?: Pick<User, 'id' | 'name' | 'email'> | null
  action: string
  entity_type: string
  entity_id: number | null
  old_values: Record<string, unknown> | null
  new_values: Record<string, unknown> | null
  ip_address: string | null
  created_at: string
}

export type OrganizationInput = {
  name: string
  legal_name?: string | null
  slug: string
  email?: string | null
  phone?: string | null
  website?: string | null
  logo?: string | null
  country?: string | null
  city?: string | null
  address?: string | null
  default_currency: string
  timezone: string
  language: 'en' | 'ar'
  status: 'active' | 'inactive'
}

export type BranchInput = {
  name: string
  code: string
  phone?: string | null
  email?: string | null
  country?: string | null
  city?: string | null
  address?: string | null
  manager_user_id?: number | null
  status: 'active' | 'inactive'
}

export type UserInput = {
  name: string
  email: string
  phone?: string | null
  password?: string
  branch_id?: number | null
  status: 'active' | 'disabled' | 'pending'
  roles?: string[]
}

export type RoleInput = {
  name: string
  permissions: string[]
}

export async function getOrganization() {
  const response = await apiRequest<SingleResponse<Organization>>('/organization')

  return response.data
}

export async function updateOrganization(input: OrganizationInput) {
  const response = await apiRequest<SingleResponse<Organization>>('/organization', {
    method: 'PATCH',
    body: JSON.stringify(input),
  })

  return response.data
}

export async function getBranches() {
  const response = await apiRequest<CollectionResponse<Branch>>('/branches?per_page=100')

  return response.data
}
export function getBranchesPage(params: PaginationParams) { return apiRequest<PaginatedResponse<Branch>>(paginatedPath('/branches', params)) }

export async function createBranch(input: BranchInput) {
  const response = await apiRequest<SingleResponse<Branch>>('/branches', {
    method: 'POST',
    body: JSON.stringify(input),
  })

  return response.data
}

export async function updateBranch(id: number, input: BranchInput) {
  const response = await apiRequest<SingleResponse<Branch>>(`/branches/${id}`, {
    method: 'PATCH',
    body: JSON.stringify(input),
  })

  return response.data
}

export async function deleteBranch(id: number) {
  return apiRequest<EmptyResponse>(`/branches/${id}`, { method: 'DELETE' })
}

export async function getUsers() {
  const response = await apiRequest<CollectionResponse<User>>('/users?per_page=100')

  return response.data
}
export function getUsersPage(params: PaginationParams) { return apiRequest<PaginatedResponse<User>>(paginatedPath('/users', params)) }

export async function createUser(input: UserInput) {
  const response = await apiRequest<SingleResponse<User>>('/users', {
    method: 'POST',
    body: JSON.stringify(input),
  })

  return response.data
}

export async function updateUser(id: number, input: UserInput) {
  const response = await apiRequest<SingleResponse<User>>(`/users/${id}`, {
    method: 'PATCH',
    body: JSON.stringify(input),
  })

  return response.data
}

export async function enableUser(id: number) {
  const response = await apiRequest<SingleResponse<User>>(`/users/${id}/enable`, { method: 'POST' })

  return response.data
}

export async function disableUser(id: number) {
  const response = await apiRequest<SingleResponse<User>>(`/users/${id}/disable`, { method: 'POST' })

  return response.data
}

export async function syncUserRoles(id: number, roles: string[]) {
  const response = await apiRequest<SingleResponse<User>>(`/users/${id}/roles`, {
    method: 'POST',
    body: JSON.stringify({ roles }),
  })

  return response.data
}

export async function getRoles() {
  const response = await apiRequest<CollectionResponse<Role>>('/roles?per_page=100')

  return response.data
}
export function getRolesPage(params: PaginationParams) { return apiRequest<PaginatedResponse<Role>>(paginatedPath('/roles', params)) }

export async function createRole(input: RoleInput) {
  const response = await apiRequest<SingleResponse<Role>>('/roles', {
    method: 'POST',
    body: JSON.stringify(input),
  })

  return response.data
}

export async function updateRole(id: number, input: RoleInput) {
  const response = await apiRequest<SingleResponse<Role>>(`/roles/${id}`, {
    method: 'PATCH',
    body: JSON.stringify(input),
  })

  return response.data
}

export async function deleteRole(id: number) {
  return apiRequest<EmptyResponse>(`/roles/${id}`, { method: 'DELETE' })
}

export async function getPermissions() {
  const response = await apiRequest<CollectionResponse<Permission>>('/permissions')

  return response.data
}

export async function getAuditLogs() {
  const response = await apiRequest<CollectionResponse<AuditLog>>('/audit-logs')

  return response.data
}
export function getAuditLogsPage(params: PaginationParams) { return apiRequest<PaginatedResponse<AuditLog>>(paginatedPath('/audit-logs', params)) }
