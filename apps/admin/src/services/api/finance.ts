import { apiRequest } from './apiClient'

type CollectionResponse<T> = {
  data: T[]
}

export type Donor = {
  id: number
  donor_type: string
  name: string
  email: string | null
  status: string
  donations_count?: number
}

export type Campaign = {
  id: number
  title: string
  slug: string
  goal_amount: string
  collected_amount: string
  currency: string
  status: string
  visibility: string
}

export type Donation = {
  id: number
  donation_number: string
  amount: string
  currency: string
  payment_status: string
  donation_status: string
  donor?: {
    name: string
  }
  campaign?: {
    title: string
  }
}

export async function getDonors() {
  const response = await apiRequest<CollectionResponse<Donor>>('/donors')

  return response.data
}

export async function getCampaigns() {
  const response = await apiRequest<CollectionResponse<Campaign>>('/campaigns')

  return response.data
}

export async function getDonations() {
  const response = await apiRequest<CollectionResponse<Donation>>('/donations')

  return response.data
}
