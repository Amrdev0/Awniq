import { describe, expect, it } from 'vitest'
import { paginatedPath, readPagination } from './pagination'

describe('paginatedPath', () => {
  it('serializes pagination and filters while omitting empty values', () => {
    expect(paginatedPath('/beneficiaries', { page: 2, per_page: 25, search: 'Ali', status: '' })).toBe('/beneficiaries?page=2&per_page=25&search=Ali')
  })

  it('reads valid URL state and clamps invalid client values', () => {
    expect(readPagination(new URLSearchParams('page=3&per_page=50&search=rice'))).toEqual({ page: 3, perPage: 50, search: 'rice' })
    expect(readPagination(new URLSearchParams('page=-2&per_page=999'))).toEqual({ page: 1, perPage: 15, search: '' })
  })

  it('isolates nested collection state with prefixes', () => {
    expect(readPagination(new URLSearchParams('page=2&notes_page=4&notes_per_page=25'), 'notes_')).toEqual({ page: 4, perPage: 25, search: '' })
  })
})
