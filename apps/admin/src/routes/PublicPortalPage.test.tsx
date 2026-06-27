import { render, screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { CampaignGrid, PortalDisabledState } from './PublicPortalPage'

describe('PublicPortalPage states', () => {
  it('renders the disabled portal state', () => {
    render(<PortalDisabledState />)

    expect(screen.getByText('Public portal is not available')).toBeInTheDocument()
  })

  it('renders the no-campaign empty state', () => {
    render(<CampaignGrid campaigns={[]} />)

    expect(screen.getByText('No public campaigns found')).toBeInTheDocument()
  })
})
