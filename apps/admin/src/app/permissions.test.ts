import { describe, expect, it } from 'vitest'
import { canAccessAny } from './permissions'

describe('canAccessAny', () => {
  it('allows unguarded destinations', () => {
    expect(canAccessAny([], undefined)).toBe(true)
  })

  it('allows an administrator with a matching permission', () => {
    expect(canAccessAny(['warehouses.view', 'warehouses.create'], ['warehouses.view'])).toBe(true)
  })

  it('hides restricted modules from a limited volunteer', () => {
    expect(canAccessAny(['aid_distributions.view', 'aid_distributions.deliver'], ['donations.view', 'reports.donations.view'])).toBe(false)
  })

  it('denies guarded destinations when permissions are explicitly empty', () => {
    expect(canAccessAny([], ['dashboard.view'])).toBe(false)
  })
})
