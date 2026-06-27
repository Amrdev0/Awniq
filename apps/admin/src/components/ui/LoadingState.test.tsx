import { render, screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { LoadingState } from './LoadingState'

describe('LoadingState', () => {
  it('renders the supplied label', () => {
    render(<LoadingState label="Checking API health" />)

    expect(screen.getByText('Checking API health')).toBeInTheDocument()
  })
})
