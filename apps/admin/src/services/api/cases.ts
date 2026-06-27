import { apiRequest } from './apiClient'

type CollectionResponse<T> = {
  data: T[]
}

export type Beneficiary = {
  id: number
  code: string
  full_name: string
  status: string
  vulnerability_level: string
  city: string | null
  household_size: number
  family_members_count?: number
  case_files_count?: number
}

export type CaseFile = {
  id: number
  case_number: string
  case_type: string
  priority: string
  status: string
  beneficiary?: {
    code: string
    full_name: string
  }
  notes_count?: number
  documents_count?: number
}

export async function getBeneficiaries() {
  const response = await apiRequest<CollectionResponse<Beneficiary>>('/beneficiaries')

  return response.data
}

export async function getCaseFiles() {
  const response = await apiRequest<CollectionResponse<CaseFile>>('/case-files')

  return response.data
}
