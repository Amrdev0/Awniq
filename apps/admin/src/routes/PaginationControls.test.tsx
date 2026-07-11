import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it, vi } from 'vitest'
import { PaginationControls, type ListPaginationState } from './AppRouter'

const meta = { current_page: 2, from: 16, last_page: 4, path: '/users', per_page: 15, to: 30, total: 52 }

function pagination(overrides: Partial<ListPaginationState> = {}): ListPaginationState {
  return {
    page: 2,
    perPage: 15,
    search: '',
    params: { page: 2, per_page: 15 },
    setPage: vi.fn(),
    setPerPage: vi.fn(),
    setSearch: vi.fn(),
    setFilter: vi.fn(),
    ...overrides,
  }
}

describe('PaginationControls', () => {
  it('navigates pages and changes page size', async () => {
    const user = userEvent.setup()
    const state = pagination()
    render(<PaginationControls meta={meta} pagination={state} />)

    await user.click(screen.getByRole('button', { name: 'Next' }))
    expect(state.setPage).toHaveBeenCalledWith(3)

    await user.selectOptions(screen.getByLabelText('Rows per page'), '25')
    expect(state.setPerPage).toHaveBeenCalledWith(25)
  })

  it('submits and clears server search', async () => {
    const user = userEvent.setup()
    const state = pagination({ search: 'old' })
    render(<PaginationControls meta={meta} pagination={state} />)

    const input = screen.getByLabelText('Search records')
    await user.clear(input)
    await user.type(input, 'new search')
    await user.click(screen.getByRole('button', { name: 'Search' }))
    expect(state.setSearch).toHaveBeenCalledWith('new search')

    await user.click(screen.getByRole('button', { name: 'Clear' }))
    expect(state.setSearch).toHaveBeenCalledWith('')
  })

  it('returns to the final valid page after deletion empties a page', () => {
    const state = pagination({ page: 4 })
    render(<PaginationControls meta={{ ...meta, current_page: 4, last_page: 3, from: null, to: null, total: 45 }} pagination={state} />)
    expect(state.setPage).toHaveBeenCalledWith(3)
  })
})
