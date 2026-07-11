import { apiRequest } from './apiClient'

type CollectionResponse<T> = {
  data: T[]
}

type SingleResponse<T> = {
  data: T
  message?: string
}

type EmptyResponse = {
  data?: unknown
  message?: string
}

type RelatedUser = {
  id: number
  name: string
  email: string
}

type RelatedBeneficiary = {
  id: number
  code: string
  full_name: string
  status?: string
}

type RelatedCaseFile = {
  id: number
  case_number: string
  case_type: string
  status?: string
}

export type Donor = {
  id: number
  donor_type: string
  name: string
  email: string | null
  phone: string | null
  country: string | null
  city: string | null
  address: string | null
  tax_number: string | null
  notes: string | null
  communication_preferences: string[] | Record<string, unknown> | null
  status: string
  donations?: Donation[]
  donations_count?: number
}

export type Campaign = {
  id: number
  title: string
  slug: string
  description: string | null
  goal_amount: string
  collected_amount: string
  currency: string
  start_date: string
  end_date: string | null
  status: string
  visibility: string
  cover_image: string | null
  creator?: RelatedUser | null
  donations?: Donation[]
  donations_count?: number
  allocations_count?: number
}

export type Donation = {
  id: number
  donor_id: number | null
  campaign_id: number | null
  donation_number: string
  amount: string
  currency: string
  payment_method: string
  payment_status: string
  donation_status: string
  donated_at: string
  confirmed_at: string | null
  notes: string | null
  donor?: Pick<Donor, 'id' | 'name' | 'donor_type'> | null
  campaign?: Pick<Campaign, 'id' | 'title' | 'slug' | 'status'> | null
  creator?: RelatedUser | null
  allocations?: DonationAllocation[]
  payment_transactions?: PaymentTransaction[]
  receipt?: Receipt | null
  allocations_count?: number
  payment_transactions_count?: number
}

export type DonationAllocation = {
  id: number
  donation_id: number
  allocation_type: string
  campaign_id: number | null
  campaign?: Pick<Campaign, 'id' | 'title' | 'slug' | 'status'> | null
  beneficiary_id: number | null
  beneficiary?: RelatedBeneficiary | null
  case_file_id: number | null
  case_file?: RelatedCaseFile | null
  amount: string
  notes: string | null
}

export type PaymentTransaction = {
  id: number
  donation_id: number
  provider: string
  provider_transaction_id: string | null
  idempotency_key: string | null
  amount: string
  currency: string
  status: string
  request_payload: Record<string, unknown> | null
  response_payload: Record<string, unknown> | null
  paid_at: string | null
  failed_at: string | null
  created_at: string
}

export type Receipt = {
  id: number
  donation_id: number
  receipt_number: string
  file_path: string | null
  issued_at: string
  issued_by: number
  issuer?: RelatedUser | null
  status: string
}

export type DonorInput = {
  donor_type: 'individual' | 'company' | 'institution' | 'anonymous'
  name: string
  email?: string | null
  phone?: string | null
  country?: string | null
  city?: string | null
  address?: string | null
  tax_number?: string | null
  notes?: string | null
  communication_preferences?: string[] | null
  status?: 'active' | 'inactive' | 'blocked' | null
}

export type CampaignInput = {
  title: string
  slug: string
  description?: string | null
  goal_amount: number
  currency: string
  start_date: string
  end_date?: string | null
  status?: 'draft' | 'active' | 'paused' | 'completed' | 'cancelled' | null
  visibility?: 'private' | 'public' | null
  cover_image?: string | null
}

export type DonationAllocationInput = {
  allocation_type: 'general_fund' | 'campaign' | 'beneficiary' | 'case_file' | 'medical' | 'education' | 'food' | 'emergency' | 'inventory' | 'other'
  campaign_id?: number | null
  beneficiary_id?: number | null
  case_file_id?: number | null
  amount: number
  notes?: string | null
}

export type DonationInput = {
  donor_id?: number | null
  campaign_id?: number | null
  amount?: number
  currency?: string
  payment_method?: 'cash' | 'bank_transfer' | 'card' | 'check' | 'mobile_wallet' | 'other'
  donation_status?: 'draft' | 'pending'
  donated_at?: string
  notes?: string | null
  allocations?: DonationAllocationInput[]
}

export type ConfirmDonationInput = {
  provider?: 'manual' | 'fake' | null
  provider_transaction_id?: string | null
  paid_at?: string | null
  notes?: string | null
}

export async function getDonors() {
  const response = await apiRequest<CollectionResponse<Donor>>('/donors')

  return response.data
}

export async function getDonor(id: number) {
  const response = await apiRequest<SingleResponse<Donor>>(`/donors/${id}`)

  return response.data
}

export async function createDonor(input: DonorInput) {
  const response = await apiRequest<SingleResponse<Donor>>('/donors', {
    method: 'POST',
    body: JSON.stringify(input),
  })

  return response.data
}

export async function updateDonor(id: number, input: DonorInput) {
  const response = await apiRequest<SingleResponse<Donor>>(`/donors/${id}`, {
    method: 'PATCH',
    body: JSON.stringify(input),
  })

  return response.data
}

export async function deleteDonor(id: number) {
  return apiRequest<EmptyResponse>(`/donors/${id}`, { method: 'DELETE' })
}

export async function getDonorDonations(id: number) {
  const response = await apiRequest<CollectionResponse<Donation>>(`/donors/${id}/donations`)

  return response.data
}

export async function getCampaigns() {
  const response = await apiRequest<CollectionResponse<Campaign>>('/campaigns')

  return response.data
}

export async function getCampaign(id: number) {
  const response = await apiRequest<SingleResponse<Campaign>>(`/campaigns/${id}`)

  return response.data
}

export async function createCampaign(input: CampaignInput) {
  const response = await apiRequest<SingleResponse<Campaign>>('/campaigns', {
    method: 'POST',
    body: JSON.stringify(input),
  })

  return response.data
}

export async function updateCampaign(id: number, input: CampaignInput) {
  const response = await apiRequest<SingleResponse<Campaign>>(`/campaigns/${id}`, {
    method: 'PATCH',
    body: JSON.stringify(input),
  })

  return response.data
}

export async function deleteCampaign(id: number) {
  return apiRequest<EmptyResponse>(`/campaigns/${id}`, { method: 'DELETE' })
}

export async function activateCampaign(id: number) {
  const response = await apiRequest<SingleResponse<Campaign>>(`/campaigns/${id}/activate`, { method: 'POST' })

  return response.data
}

export async function pauseCampaign(id: number) {
  const response = await apiRequest<SingleResponse<Campaign>>(`/campaigns/${id}/pause`, { method: 'POST' })

  return response.data
}

export async function completeCampaign(id: number) {
  const response = await apiRequest<SingleResponse<Campaign>>(`/campaigns/${id}/complete`, { method: 'POST' })

  return response.data
}

export async function cancelCampaign(id: number) {
  const response = await apiRequest<SingleResponse<Campaign>>(`/campaigns/${id}/cancel`, { method: 'POST' })

  return response.data
}

export async function getDonations() {
  const response = await apiRequest<CollectionResponse<Donation>>('/donations')

  return response.data
}

export async function getDonation(id: number) {
  const response = await apiRequest<SingleResponse<Donation>>(`/donations/${id}`)

  return response.data
}

export async function createDonation(input: DonationInput) {
  const response = await apiRequest<SingleResponse<Donation>>('/donations', {
    method: 'POST',
    body: JSON.stringify(input),
  })

  return response.data
}

export async function updateDonation(id: number, input: DonationInput) {
  const response = await apiRequest<SingleResponse<Donation>>(`/donations/${id}`, {
    method: 'PATCH',
    body: JSON.stringify(input),
  })

  return response.data
}

export async function confirmDonation(id: number, input: ConfirmDonationInput) {
  const response = await apiRequest<SingleResponse<Donation>>(`/donations/${id}/confirm`, {
    method: 'POST',
    headers: {
      'Idempotency-Key': `manual-${id}-${Date.now()}`,
    },
    body: JSON.stringify(input),
  })

  return response.data
}

export async function cancelDonation(id: number) {
  const response = await apiRequest<SingleResponse<Donation>>(`/donations/${id}/cancel`, { method: 'POST' })

  return response.data
}

export async function getDonationReceipt(id: number) {
  const response = await apiRequest<SingleResponse<Receipt>>(`/donations/${id}/receipt`)

  return response.data
}

export async function generateDonationReceipt(id: number) {
  const response = await apiRequest<SingleResponse<Receipt>>(`/donations/${id}/receipt`, { method: 'POST' })

  return response.data
}

export async function getDonationAllocations(id: number) {
  const response = await apiRequest<CollectionResponse<DonationAllocation>>(`/donations/${id}/allocations`)

  return response.data
}

export async function createDonationAllocation(donationId: number, input: DonationAllocationInput) {
  const response = await apiRequest<SingleResponse<DonationAllocation>>(`/donations/${donationId}/allocations`, {
    method: 'POST',
    body: JSON.stringify(input),
  })

  return response.data
}

export async function updateDonationAllocation(donationId: number, allocationId: number, input: DonationAllocationInput) {
  const response = await apiRequest<SingleResponse<DonationAllocation>>(`/donations/${donationId}/allocations/${allocationId}`, {
    method: 'PATCH',
    body: JSON.stringify(input),
  })

  return response.data
}

export async function deleteDonationAllocation(donationId: number, allocationId: number) {
  return apiRequest<EmptyResponse>(`/donations/${donationId}/allocations/${allocationId}`, { method: 'DELETE' })
}

export async function getDonationPaymentTransactions(donationId: number) {
  const response = await apiRequest<CollectionResponse<PaymentTransaction>>(`/donations/${donationId}/payment-transactions`)

  return response.data
}

export async function getPaymentTransaction(id: number) {
  const response = await apiRequest<SingleResponse<PaymentTransaction>>(`/payment-transactions/${id}`)

  return response.data
}
