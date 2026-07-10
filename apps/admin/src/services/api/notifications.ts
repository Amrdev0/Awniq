import { apiRequest } from './apiClient'

type CollectionResponse<T> = {
  data: T[]
}

type SingleResponse<T> = {
  data: T
}

export type OperationalNotification = {
  id: number
  type: string
  category: string
  severity: 'info' | 'success' | 'warning' | 'critical' | string
  title: string
  body: string | null
  action_url: string | null
  data: Record<string, unknown> | null
  read_at: string | null
  created_at: string
}

export type NotificationPreference = {
  category: string
  database_enabled: boolean
  email_enabled: boolean
}

export type ScheduledJob = {
  name: string
  command: string
  frequency: string
}

export type QueueHealth = {
  connection: string
  pending_jobs: number
  failed_jobs: number
}

export async function getNotifications() {
  const response = await apiRequest<CollectionResponse<OperationalNotification>>('/notifications?per_page=10')

  return response.data
}

export async function getUnreadNotificationCount() {
  const response = await apiRequest<SingleResponse<{ count: number }>>('/notifications/unread-count')

  return response.data.count
}

export async function markNotificationRead(id: number) {
  const response = await apiRequest<SingleResponse<OperationalNotification>>(`/notifications/${id}/mark-read`, {
    method: 'POST',
  })

  return response.data
}

export async function markAllNotificationsRead() {
  const response = await apiRequest<SingleResponse<unknown>>('/notifications/mark-all-read', {
    method: 'POST',
  })

  return response.data
}

export async function getNotificationPreferences() {
  const response = await apiRequest<CollectionResponse<NotificationPreference>>('/notification-preferences')

  return response.data
}

export async function getScheduledJobs() {
  const response = await apiRequest<SingleResponse<{ jobs: ScheduledJob[] }>>('/system/scheduled-jobs')

  return response.data.jobs
}

export async function getQueueHealth() {
  const response = await apiRequest<SingleResponse<QueueHealth>>('/system/queue-health')

  return response.data
}
