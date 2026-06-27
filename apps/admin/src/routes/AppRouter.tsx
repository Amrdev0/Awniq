import { useEffect, useState } from 'react'
import type { FormEvent, ReactNode } from 'react'
import { createBrowserRouter, RouterProvider } from 'react-router'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Building2, FileText, GitBranch, ListChecks, LogOut, ShieldCheck, Users } from 'lucide-react'
import { clearStoredToken, getStoredToken, storeToken } from '../app/auth'
import { EmptyState } from '../components/ui/EmptyState'
import { LoadingState } from '../components/ui/LoadingState'
import { getMe, login, logout } from '../services/api/auth'
import { getBeneficiaries, getCaseFiles } from '../services/api/cases'
import { getCampaigns, getDonations, getDonors } from '../services/api/finance'
import { getAuditLogs, getBranches, getOrganization, getRoles, getUsers } from '../services/api/identity'
import { getExpiringStock, getInventoryItems, getLowStock, getStockSummary, getWarehouses } from '../services/api/inventory'

function LoginPage({ onAuthenticated }: { onAuthenticated: (token: string) => void }) {
  const queryClient = useQueryClient()
  const [email, setEmail] = useState('admin@awniq.test')
  const [password, setPassword] = useState('Password123!')
  const loginMutation = useMutation({
    mutationFn: login,
    onSuccess: (response) => {
      storeToken(response.data.token)
      onAuthenticated(response.data.token)
      void queryClient.invalidateQueries()
    },
  })

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    loginMutation.mutate({ email, password })
  }

  return (
    <main className="grid min-h-svh place-items-center bg-[#f6f8f7] px-4 text-[#172026]">
      <form className="w-full max-w-sm rounded-md border border-[#d9e1de] bg-white p-6" onSubmit={handleSubmit}>
        <div className="mb-6">
          <p className="text-sm font-medium uppercase tracking-wide text-[#4b635b]">Awniq Admin</p>
          <h1 className="mt-2 text-2xl font-semibold text-[#10201a]">Sign in</h1>
        </div>

        <label className="mb-4 block text-sm">
          <span className="mb-1 block font-medium text-[#10201a]">Email</span>
          <input
            className="w-full rounded-md border border-[#c8d4cf] px-3 py-2"
            value={email}
            onChange={(event) => setEmail(event.target.value)}
            type="email"
          />
        </label>

        <label className="mb-5 block text-sm">
          <span className="mb-1 block font-medium text-[#10201a]">Password</span>
          <input
            className="w-full rounded-md border border-[#c8d4cf] px-3 py-2"
            value={password}
            onChange={(event) => setPassword(event.target.value)}
            type="password"
          />
        </label>

        {loginMutation.isError ? (
          <div className="mb-4 rounded-md border border-[#efd8bb] bg-[#fff8ed] p-3 text-sm text-[#6c4b1f]">
            Login failed. Check the API server and credentials.
          </div>
        ) : null}

        <button
          className="w-full rounded-md bg-[#236b55] px-4 py-2 font-medium text-white disabled:opacity-60"
          disabled={loginMutation.isPending}
          type="submit"
        >
          {loginMutation.isPending ? 'Signing in...' : 'Sign in'}
        </button>
      </form>
    </main>
  )
}

function AdminPage() {
  const queryClient = useQueryClient()
  const [authToken, setAuthToken] = useState(() => getStoredToken())
  const me = useQuery({ queryKey: ['me'], queryFn: getMe, enabled: Boolean(authToken) })
  const organization = useQuery({ queryKey: ['organization'], queryFn: getOrganization, enabled: Boolean(me.data) })
  const branches = useQuery({ queryKey: ['branches'], queryFn: getBranches, enabled: Boolean(me.data) })
  const users = useQuery({ queryKey: ['users'], queryFn: getUsers, enabled: Boolean(me.data) })
  const roles = useQuery({ queryKey: ['roles'], queryFn: getRoles, enabled: Boolean(me.data) })
  const auditLogs = useQuery({ queryKey: ['audit-logs'], queryFn: getAuditLogs, enabled: Boolean(me.data) })
  const beneficiaries = useQuery({ queryKey: ['beneficiaries'], queryFn: getBeneficiaries, enabled: Boolean(me.data) })
  const caseFiles = useQuery({ queryKey: ['case-files'], queryFn: getCaseFiles, enabled: Boolean(me.data) })
  const donors = useQuery({ queryKey: ['donors'], queryFn: getDonors, enabled: Boolean(me.data) })
  const campaigns = useQuery({ queryKey: ['campaigns'], queryFn: getCampaigns, enabled: Boolean(me.data) })
  const donations = useQuery({ queryKey: ['donations'], queryFn: getDonations, enabled: Boolean(me.data) })
  const warehouses = useQuery({ queryKey: ['warehouses'], queryFn: getWarehouses, enabled: Boolean(me.data) })
  const inventoryItems = useQuery({ queryKey: ['inventory-items'], queryFn: getInventoryItems, enabled: Boolean(me.data) })
  const stockSummary = useQuery({ queryKey: ['stock-summary'], queryFn: getStockSummary, enabled: Boolean(me.data) })
  const lowStock = useQuery({ queryKey: ['stock-low-stock'], queryFn: getLowStock, enabled: Boolean(me.data) })
  const expiringStock = useQuery({ queryKey: ['stock-expiring'], queryFn: getExpiringStock, enabled: Boolean(me.data) })
  const logoutMutation = useMutation({
    mutationFn: logout,
    onSettled: () => {
      clearStoredToken()
      queryClient.clear()
      setAuthToken(null)
    },
  })

  useEffect(() => {
    if (me.isError) {
      clearStoredToken()
      setAuthToken(null)
    }
  }, [me.isError])

  if (!authToken) {
    return <LoginPage onAuthenticated={setAuthToken} />
  }

  if (me.isPending) {
    return (
      <main className="grid min-h-svh place-items-center bg-[#f6f8f7]">
        <LoadingState label="Loading admin session" />
      </main>
    )
  }

  if (me.isError) {
    return <LoginPage onAuthenticated={setAuthToken} />
  }

  return (
    <main className="min-h-svh bg-[#f6f8f7] text-[#172026]">
      <div className="mx-auto w-full max-w-6xl px-6 py-8">
        <header className="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-[#d9e1de] pb-5">
          <div>
            <p className="text-sm font-medium uppercase tracking-wide text-[#4b635b]">Operations foundation</p>
            <h1 className="mt-2 text-3xl font-semibold text-[#10201a]">Awniq Admin</h1>
            <p className="mt-1 text-sm text-[#52645e]">
              Signed in as {me.data.name} ({me.data.email})
            </p>
          </div>
          <button
            className="inline-flex items-center gap-2 rounded-md border border-[#c8d4cf] bg-white px-3 py-2 text-sm"
            onClick={() => logoutMutation.mutate()}
            type="button"
          >
            <LogOut size={16} aria-hidden="true" />
            Logout
          </button>
        </header>

        <div className="grid gap-4 lg:grid-cols-2">
          <Panel icon={<Building2 size={20} />} title="Organization">
            {organization.data ? (
              <KeyValueRows
                rows={[
                  ['Name', organization.data.name],
                  ['Slug', organization.data.slug],
                  ['Currency', organization.data.default_currency],
                  ['Timezone', organization.data.timezone],
                  ['Status', organization.data.status],
                ]}
              />
            ) : (
              <LoadingOrEmpty isLoading={organization.isPending} label="Loading organization" />
            )}
          </Panel>

          <Panel icon={<GitBranch size={20} />} title="Branches">
            <SimpleList items={branches.data} label="branches" render={(branch) => `${branch.code} - ${branch.name} (${branch.status})`} />
          </Panel>

          <Panel icon={<Users size={20} />} title="Users">
            <SimpleList items={users.data} label="users" render={(user) => `${user.name} - ${user.email} - ${user.status}`} />
          </Panel>

          <Panel icon={<ShieldCheck size={20} />} title="Roles">
            <SimpleList items={roles.data} label="roles" render={(role) => `${role.name} (${role.permissions?.length ?? 0} permissions)`} />
          </Panel>

          <Panel icon={<Users size={20} />} title="Beneficiaries">
            <SimpleList
              items={beneficiaries.data}
              label="beneficiaries"
              render={(beneficiary) =>
                `${beneficiary.code} - ${beneficiary.full_name} - ${beneficiary.status} - ${beneficiary.vulnerability_level} - ${beneficiary.household_size} household`
              }
            />
          </Panel>

          <Panel icon={<FileText size={20} />} title="Case Files">
            <SimpleList
              items={caseFiles.data}
              label="case files"
              render={(caseFile) =>
                `${caseFile.case_number} - ${caseFile.beneficiary?.full_name ?? 'Unassigned'} - ${caseFile.case_type} - ${caseFile.status} - ${caseFile.priority}`
              }
            />
          </Panel>

          <Panel icon={<Users size={20} />} title="Donors">
            <SimpleList
              items={donors.data}
              label="donors"
              render={(donor) => `${donor.name} - ${donor.donor_type} - ${donor.status} - ${donor.donations_count ?? 0} donations`}
            />
          </Panel>

          <Panel icon={<ListChecks size={20} />} title="Campaigns">
            <SimpleList
              items={campaigns.data}
              label="campaigns"
              render={(campaign) =>
                `${campaign.title} - ${campaign.status} - ${campaign.collected_amount}/${campaign.goal_amount} ${campaign.currency} - ${campaign.visibility}`
              }
            />
          </Panel>

          <section className="lg:col-span-2">
            <Panel icon={<FileText size={20} />} title="Donations">
              <SimpleList
                items={donations.data}
                label="donations"
                render={(donation) =>
                  `${donation.donation_number} - ${donation.donor?.name ?? 'Anonymous'} - ${donation.amount} ${donation.currency} - ${donation.payment_status}/${donation.donation_status}`
                }
              />
            </Panel>
          </section>

          <Panel icon={<Building2 size={20} />} title="Warehouses">
            <SimpleList
              items={warehouses.data}
              label="warehouses"
              render={(warehouse) => `${warehouse.code} - ${warehouse.name} - ${warehouse.status} - ${warehouse.stock_lots_count ?? 0} lots`}
            />
          </Panel>

          <Panel icon={<ListChecks size={20} />} title="Inventory Items">
            <SimpleList
              items={inventoryItems.data}
              label="inventory items"
              render={(item) => `${item.sku} - ${item.name} - ${item.category} - min ${item.minimum_stock_level} ${item.unit}`}
            />
          </Panel>

          <Panel icon={<ListChecks size={20} />} title="Stock Summary">
            <SimpleList
              items={stockSummary.data}
              label="stock summary rows"
              render={(row) => `${row.sku} - ${row.available_quantity} ${row.unit} available - ${row.low_stock ? 'low stock' : 'healthy'}`}
            />
          </Panel>

          <Panel icon={<FileText size={20} />} title="Low Stock">
            <SimpleList
              items={lowStock.data}
              label="low stock rows"
              render={(row) => `${row.sku} - ${row.available_quantity}/${row.minimum_stock_level} ${row.unit}`}
            />
          </Panel>

          <section className="lg:col-span-2">
            <Panel icon={<FileText size={20} />} title="Expiring Stock">
              <SimpleList
                items={expiringStock.data}
                label="expiring stock lots"
                render={(lot) =>
                  `${lot.inventory_item?.sku ?? 'Unknown item'} - ${lot.remaining_quantity} ${lot.inventory_item?.unit ?? ''} - ${lot.warehouse?.code ?? 'No warehouse'} - expires ${lot.expiry_date ?? '-'}`
                }
              />
            </Panel>
          </section>

          <section className="lg:col-span-2">
            <Panel icon={<ListChecks size={20} />} title="Recent Audit Logs">
              <SimpleList items={auditLogs.data} label="audit logs" render={(log) => `${log.action} - ${log.entity_type} #${log.entity_id ?? '-'} - ${log.created_at}`} />
            </Panel>
          </section>
        </div>
      </div>
    </main>
  )
}

function Panel({ icon, title, children }: { icon: ReactNode; title: string; children: ReactNode }) {
  return (
    <section className="rounded-md border border-[#d9e1de] bg-white p-5">
      <div className="mb-4 flex items-center gap-2 text-[#10201a]">
        <span className="text-[#236b55]">{icon}</span>
        <h2 className="text-lg font-semibold">{title}</h2>
      </div>
      {children}
    </section>
  )
}

function KeyValueRows({ rows }: { rows: [string, string | null][] }) {
  return (
    <dl className="space-y-2 text-sm">
      {rows.map(([label, value]) => (
        <div className="flex justify-between gap-4 border-b border-[#edf1ef] pb-2" key={label}>
          <dt className="text-[#52645e]">{label}</dt>
          <dd className="font-medium text-[#10201a]">{value ?? '-'}</dd>
        </div>
      ))}
    </dl>
  )
}

function SimpleList<T>({ items, label, render }: { items: T[] | undefined; label: string; render: (item: T) => string }) {
  if (!items) {
    return <LoadingOrEmpty isLoading label={`Loading ${label}`} />
  }

  if (items.length === 0) {
    return <EmptyState title={`No ${label} found`} />
  }

  return (
    <ul className="divide-y divide-[#edf1ef] text-sm">
      {items.map((item, index) => (
        <li className="py-2 text-[#10201a]" key={index}>
          {render(item)}
        </li>
      ))}
    </ul>
  )
}

function LoadingOrEmpty({ isLoading, label }: { isLoading: boolean; label: string }) {
  return isLoading ? <LoadingState label={label} /> : <EmptyState title="No data available" />
}

const router = createBrowserRouter([
  {
    path: '/',
    element: <AdminPage />,
  },
])

export function AppRouter() {
  return <RouterProvider router={router} />
}
