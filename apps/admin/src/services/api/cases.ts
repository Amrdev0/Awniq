import { apiBlobRequest, apiRequest } from './apiClient'

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

type RelatedBranch = {
  id: number
  name: string
  code: string
}

type RelatedUser = {
  id: number
  name: string
  email: string
}

export type BeneficiaryStatus = 'draft' | 'pending_review' | 'approved' | 'rejected' | 'suspended' | 'archived'
export type CaseFileStatus = 'open' | 'under_review' | 'approved' | 'rejected' | 'suspended' | 'closed'
export type Gender = 'male' | 'female' | 'other' | 'unknown'

export type Beneficiary = {
  id: number
  branch_id: number
  branch?: RelatedBranch | null
  code: string
  full_name: string
  national_id: string | null
  birth_date: string | null
  gender: Gender | null
  phone: string | null
  alternate_phone: string | null
  email: string | null
  country: string | null
  status: string
  vulnerability_level: string
  city: string | null
  district: string | null
  address: string | null
  marital_status: string | null
  employment_status: string | null
  monthly_income: string | number | null
  household_size: number
  creator?: RelatedUser | null
  reviewed_by?: RelatedUser | null
  approved_by?: RelatedUser | null
  rejection_reason: string | null
  family_members?: FamilyMember[]
  case_files?: CaseFile[]
  family_members_count?: number
  case_files_count?: number
}

export type FamilyMember = {
  id: number
  beneficiary_id: number
  full_name: string
  relationship: string
  birth_date: string | null
  gender: Gender | null
  national_id: string | null
  education_level: string | null
  employment_status: string | null
  health_notes: string | null
}

export type CaseFile = {
  id: number
  beneficiary_id: number
  assigned_to_user_id: number | null
  case_number: string
  case_type: string
  priority: string
  status: string
  beneficiary?: Pick<Beneficiary, 'id' | 'code' | 'full_name' | 'branch'> | null
  assigned_to?: RelatedUser | null
  reviewed_by?: RelatedUser | null
  approved_by?: RelatedUser | null
  rejection_reason: string | null
  assessment_summary: string | null
  next_follow_up_date: string | null
  notes?: CaseNote[]
  documents?: CaseDocument[]
  notes_count?: number
  documents_count?: number
}

export type CaseNote = {
  id: number
  case_file_id: number
  user_id: number
  user?: RelatedUser | null
  note: string
  visibility: string
  created_at: string
}

export type CaseDocument = {
  id: number
  beneficiary_id: number
  case_file_id: number
  uploaded_by: number
  uploader?: RelatedUser | null
  document_type: string
  original_filename: string
  mime_type: string
  size: number
  status: string
  download_path: string | null
  created_at: string
}

export type BeneficiaryInput = {
  branch_id: number
  full_name: string
  national_id?: string | null
  birth_date?: string | null
  gender?: Gender | null
  phone?: string | null
  alternate_phone?: string | null
  email?: string | null
  country?: string | null
  city?: string | null
  district?: string | null
  address?: string | null
  marital_status?: string | null
  employment_status?: string | null
  monthly_income?: number | null
  household_size?: number | null
  vulnerability_level?: 'low' | 'medium' | 'high' | 'critical' | null
  status?: BeneficiaryStatus | null
}

export type FamilyMemberInput = {
  full_name: string
  relationship: string
  birth_date?: string | null
  gender?: Gender | null
  national_id?: string | null
  education_level?: string | null
  employment_status?: string | null
  health_notes?: string | null
}

export type CaseFileInput = {
  beneficiary_id: number
  case_type: string
  priority?: 'low' | 'medium' | 'high' | 'urgent' | null
  status?: CaseFileStatus | null
  assigned_to_user_id?: number | null
  rejection_reason?: string | null
  assessment_summary?: string | null
  next_follow_up_date?: string | null
}

export type CaseNoteInput = {
  note: string
  visibility?: 'internal' | 'private' | 'public' | null
}

export async function getBeneficiaries() {
  const response = await apiRequest<CollectionResponse<Beneficiary>>('/beneficiaries')

  return response.data
}

export async function getBeneficiary(id: number) {
  const response = await apiRequest<SingleResponse<Beneficiary>>(`/beneficiaries/${id}`)

  return response.data
}

export async function createBeneficiary(input: BeneficiaryInput) {
  const response = await apiRequest<SingleResponse<Beneficiary>>('/beneficiaries', {
    method: 'POST',
    body: JSON.stringify(input),
  })

  return response.data
}

export async function updateBeneficiary(id: number, input: BeneficiaryInput) {
  const response = await apiRequest<SingleResponse<Beneficiary>>(`/beneficiaries/${id}`, {
    method: 'PATCH',
    body: JSON.stringify(input),
  })

  return response.data
}

export async function deleteBeneficiary(id: number) {
  return apiRequest<EmptyResponse>(`/beneficiaries/${id}`, { method: 'DELETE' })
}

export async function submitBeneficiaryReview(id: number) {
  const response = await apiRequest<SingleResponse<Beneficiary>>(`/beneficiaries/${id}/submit-review`, { method: 'POST' })

  return response.data
}

export async function approveBeneficiary(id: number) {
  const response = await apiRequest<SingleResponse<Beneficiary>>(`/beneficiaries/${id}/approve`, { method: 'POST' })

  return response.data
}

export async function rejectBeneficiary(id: number, reason: string) {
  const response = await apiRequest<SingleResponse<Beneficiary>>(`/beneficiaries/${id}/reject`, {
    method: 'POST',
    body: JSON.stringify({ reason }),
  })

  return response.data
}

export async function suspendBeneficiary(id: number) {
  const response = await apiRequest<SingleResponse<Beneficiary>>(`/beneficiaries/${id}/suspend`, { method: 'POST' })

  return response.data
}

export async function reactivateBeneficiary(id: number) {
  const response = await apiRequest<SingleResponse<Beneficiary>>(`/beneficiaries/${id}/reactivate`, { method: 'POST' })

  return response.data
}

export async function getBeneficiaryFamilyMembers(beneficiaryId: number) {
  const response = await apiRequest<CollectionResponse<FamilyMember>>(`/beneficiaries/${beneficiaryId}/family-members`)

  return response.data
}

export async function createFamilyMember(beneficiaryId: number, input: FamilyMemberInput) {
  const response = await apiRequest<SingleResponse<FamilyMember>>(`/beneficiaries/${beneficiaryId}/family-members`, {
    method: 'POST',
    body: JSON.stringify(input),
  })

  return response.data
}

export async function updateFamilyMember(beneficiaryId: number, familyMemberId: number, input: FamilyMemberInput) {
  const response = await apiRequest<SingleResponse<FamilyMember>>(`/beneficiaries/${beneficiaryId}/family-members/${familyMemberId}`, {
    method: 'PATCH',
    body: JSON.stringify(input),
  })

  return response.data
}

export async function deleteFamilyMember(beneficiaryId: number, familyMemberId: number) {
  return apiRequest<EmptyResponse>(`/beneficiaries/${beneficiaryId}/family-members/${familyMemberId}`, { method: 'DELETE' })
}

export async function getCaseFiles() {
  const response = await apiRequest<CollectionResponse<CaseFile>>('/case-files')

  return response.data
}

export async function getCaseFile(id: number) {
  const response = await apiRequest<SingleResponse<CaseFile>>(`/case-files/${id}`)

  return response.data
}

export async function createCaseFile(input: CaseFileInput) {
  const response = await apiRequest<SingleResponse<CaseFile>>('/case-files', {
    method: 'POST',
    body: JSON.stringify(input),
  })

  return response.data
}

export async function updateCaseFile(id: number, input: CaseFileInput) {
  const response = await apiRequest<SingleResponse<CaseFile>>(`/case-files/${id}`, {
    method: 'PATCH',
    body: JSON.stringify(input),
  })

  return response.data
}

export async function deleteCaseFile(id: number) {
  return apiRequest<EmptyResponse>(`/case-files/${id}`, { method: 'DELETE' })
}

export async function submitCaseReview(id: number) {
  const response = await apiRequest<SingleResponse<CaseFile>>(`/case-files/${id}/submit-review`, { method: 'POST' })

  return response.data
}

export async function approveCaseFile(id: number) {
  const response = await apiRequest<SingleResponse<CaseFile>>(`/case-files/${id}/approve`, { method: 'POST' })

  return response.data
}

export async function rejectCaseFile(id: number, reason: string) {
  const response = await apiRequest<SingleResponse<CaseFile>>(`/case-files/${id}/reject`, {
    method: 'POST',
    body: JSON.stringify({ reason }),
  })

  return response.data
}

export async function suspendCaseFile(id: number) {
  const response = await apiRequest<SingleResponse<CaseFile>>(`/case-files/${id}/suspend`, { method: 'POST' })

  return response.data
}

export async function closeCaseFile(id: number) {
  const response = await apiRequest<SingleResponse<CaseFile>>(`/case-files/${id}/close`, { method: 'POST' })

  return response.data
}

export async function reopenCaseFile(id: number) {
  const response = await apiRequest<SingleResponse<CaseFile>>(`/case-files/${id}/reopen`, { method: 'POST' })

  return response.data
}

export async function createCaseNote(caseFileId: number, input: CaseNoteInput) {
  const response = await apiRequest<SingleResponse<CaseNote>>(`/case-files/${caseFileId}/notes`, {
    method: 'POST',
    body: JSON.stringify(input),
  })

  return response.data
}

export async function updateCaseNote(caseFileId: number, noteId: number, input: CaseNoteInput) {
  const response = await apiRequest<SingleResponse<CaseNote>>(`/case-files/${caseFileId}/notes/${noteId}`, {
    method: 'PATCH',
    body: JSON.stringify(input),
  })

  return response.data
}

export async function deleteCaseNote(caseFileId: number, noteId: number) {
  return apiRequest<EmptyResponse>(`/case-files/${caseFileId}/notes/${noteId}`, { method: 'DELETE' })
}

export async function uploadCaseDocument(caseFileId: number, input: { document_type: string; file: File }) {
  const form = new FormData()
  form.append('document_type', input.document_type)
  form.append('file', input.file)

  const response = await apiRequest<SingleResponse<CaseDocument>>(`/case-files/${caseFileId}/documents`, {
    method: 'POST',
    body: form,
  })

  return response.data
}

export async function deleteCaseDocument(caseFileId: number, documentId: number) {
  return apiRequest<EmptyResponse>(`/case-files/${caseFileId}/documents/${documentId}`, { method: 'DELETE' })
}

export async function downloadCaseDocument(caseFileId: number, documentId: number) {
  return apiBlobRequest(`/case-files/${caseFileId}/documents/${documentId}/download`)
}
