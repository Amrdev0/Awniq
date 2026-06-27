import { apiRequest } from './apiClient'

type ResourceResponse<T> = {
  data: T
}

type CollectionResponse<T> = {
  data: T[]
}

export type PublicOrganization = {
  name: string
  slug: string
  website: string | null
  logo: string | null
  country: string | null
  city: string | null
  default_currency: string
  language: string
  about: string | null
  contact: {
    email: string | null
    phone: string | null
  }
  settings: {
    show_donation_totals: boolean
    show_campaign_progress: boolean
    show_completed_campaigns: boolean
    donations_enabled: boolean
    reports_enabled: boolean
  }
}

export type PublicCampaign = {
  title: string
  slug: string
  description: string | null
  currency: string
  status: string
  cover_image: string | null
  start_date: string | null
  end_date: string | null
  goal_amount: string | null
  collected_amount: string | null
  progress_percentage: number | null
  donations_enabled: boolean
}

export type PublicStats = {
  total_beneficiaries_helped: number
  total_aid_distributions: number
  total_aid_items_distributed: string
  total_confirmed_donations_collected: string | null
  currency: string
  active_campaigns: number
  completed_campaigns: number
  generated_at: string
}

export type PublicDonationIntent = {
  reference: string
  status: string
  amount: string
  currency: string
  campaign: {
    title: string
    slug: string
  } | null
  message: string
  created_at: string
}

export type CreatePublicDonationIntentInput = {
  campaign_slug?: string
  donor_name?: string
  donor_email?: string
  amount: number
  currency: string
}

export async function getPublicOrganization() {
  const response = await apiRequest<ResourceResponse<PublicOrganization>>('/public/organization')

  return response.data
}

export async function getPublicCampaigns() {
  const response = await apiRequest<CollectionResponse<PublicCampaign>>('/public/campaigns')

  return response.data
}

export async function getPublicCampaign(slug: string) {
  const response = await apiRequest<ResourceResponse<PublicCampaign>>(`/public/campaigns/${slug}`)

  return response.data
}

export async function getPublicStats() {
  const response = await apiRequest<ResourceResponse<PublicStats>>('/public/stats')

  return response.data
}

export async function createPublicDonationIntent(input: CreatePublicDonationIntentInput) {
  const response = await apiRequest<ResourceResponse<PublicDonationIntent>>('/public/donations', {
    method: 'POST',
    body: JSON.stringify(input),
  })

  return response.data
}
