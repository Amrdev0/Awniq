import { useEffect, useMemo, useState } from 'react'
import type { ComponentType, FormEvent, InputHTMLAttributes, ReactNode, SelectHTMLAttributes, TextareaHTMLAttributes } from 'react'
import { createBrowserRouter, Navigate, NavLink, Outlet, RouterProvider, useLocation } from 'react-router'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import {
  Bell,
  Boxes,
  Building2,
  CalendarClock,
  Check,
  ClipboardCheck,
  ClipboardList,
  DollarSign,
  FileBarChart,
  FileText,
  GitBranch,
  Home,
  Landmark,
  ListChecks,
  LogOut,
  Package,
  Receipt,
  Settings,
  ShieldCheck,
  Truck,
  Users,
  Warehouse,
} from 'lucide-react'
import { clearStoredToken, getStoredToken, storeToken } from '../app/auth'
import { EmptyState } from '../components/ui/EmptyState'
import { LoadingState } from '../components/ui/LoadingState'
import { UnauthorizedState } from '../components/ui/UnauthorizedState'
import { getAidBatches } from '../services/api/aid'
import { getMe, login, logout, type CurrentUser } from '../services/api/auth'
import {
  approveBeneficiary,
  approveCaseFile,
  closeCaseFile,
  createBeneficiary,
  createCaseFile,
  createCaseNote,
  createFamilyMember,
  deleteBeneficiary,
  deleteCaseDocument,
  deleteCaseFile,
  deleteCaseNote,
  deleteFamilyMember,
  downloadCaseDocument,
  getBeneficiaries,
  getBeneficiary,
  getCaseFile,
  getCaseFiles,
  reactivateBeneficiary,
  rejectBeneficiary,
  rejectCaseFile,
  reopenCaseFile,
  submitBeneficiaryReview,
  submitCaseReview,
  suspendBeneficiary,
  suspendCaseFile,
  updateBeneficiary,
  updateCaseFile,
  updateCaseNote,
  updateFamilyMember,
  uploadCaseDocument,
  type BeneficiaryInput,
  type CaseDocument,
  type CaseFileInput,
  type CaseNote,
  type FamilyMember,
  type FamilyMemberInput,
} from '../services/api/cases'
import { getCampaigns, getDonations, getDonors } from '../services/api/finance'
import {
  createBranch,
  createRole,
  createUser,
  deleteBranch,
  deleteRole,
  disableUser,
  enableUser,
  getAuditLogs,
  getBranches,
  getOrganization,
  getPermissions,
  getRoles,
  getUsers,
  syncUserRoles,
  updateBranch,
  updateOrganization,
  updateRole,
  updateUser,
  type AuditLog,
  type Branch,
  type OrganizationInput,
  type Role,
  type User,
} from '../services/api/identity'
import {
  getExpiringStock,
  getInventoryItems,
  getLowStock,
  getStockLots,
  getStockMovements,
  getStockSummary,
  getWarehouses,
} from '../services/api/inventory'
import {
  getNotificationPreferences,
  getNotifications,
  getQueueHealth,
  getScheduledJobs,
  getUnreadNotificationCount,
  markAllNotificationsRead,
  markNotificationRead,
  type OperationalNotification,
} from '../services/api/notifications'
import { getDashboardReport } from '../services/api/reports'
import { PublicCampaignDetailsPage, PublicPortalPage } from './PublicPortalPage'

type NavItem = {
  label: string
  path: string
  icon: ComponentType<{ size?: number }>
  permissions?: string[]
}

type NavGroup = {
  label: string
  items: NavItem[]
}

const navGroups: NavGroup[] = [
  {
    label: 'Home',
    items: [{ label: 'Dashboard', path: '/dashboard', icon: Home, permissions: ['dashboard.view'] }],
  },
  {
    label: 'Identity',
    items: [
      { label: 'Organization', path: '/organization', icon: Building2, permissions: ['organization.view'] },
      { label: 'Branches', path: '/branches', icon: GitBranch, permissions: ['branches.view'] },
      { label: 'Users', path: '/users', icon: Users, permissions: ['users.view'] },
      { label: 'Roles & Permissions', path: '/roles', icon: ShieldCheck, permissions: ['roles.view', 'permissions.view'] },
      { label: 'Audit Logs', path: '/audit-logs', icon: ClipboardList, permissions: ['audit_logs.view'] },
    ],
  },
  {
    label: 'Cases',
    items: [
      { label: 'Beneficiaries', path: '/beneficiaries', icon: Users, permissions: ['beneficiaries.view'] },
      { label: 'Case Files', path: '/case-files', icon: FileText, permissions: ['case_files.view'] },
    ],
  },
  {
    label: 'Finance',
    items: [
      { label: 'Donors', path: '/donors', icon: Users, permissions: ['donors.view'] },
      { label: 'Campaigns', path: '/campaigns', icon: Landmark, permissions: ['campaigns.view'] },
      { label: 'Donations', path: '/donations', icon: DollarSign, permissions: ['donations.view'] },
      { label: 'Payments & Receipts', path: '/finance/payments', icon: Receipt, permissions: ['payment_transactions.view', 'receipts.view'] },
    ],
  },
  {
    label: 'Inventory',
    items: [
      { label: 'Warehouses', path: '/warehouses', icon: Warehouse, permissions: ['warehouses.view'] },
      { label: 'Inventory Items', path: '/inventory-items', icon: Package, permissions: ['inventory_items.view'] },
      { label: 'Stock Summary', path: '/stock/summary', icon: Boxes, permissions: ['stock_reports.view'] },
      { label: 'Stock Lots', path: '/stock/lots', icon: Boxes, permissions: ['stock_lots.view'] },
      { label: 'Stock Movements', path: '/stock/movements', icon: ListChecks, permissions: ['stock_movements.view'] },
      { label: 'Low Stock', path: '/stock/low-stock', icon: ClipboardCheck, permissions: ['stock_reports.view'] },
      { label: 'Expiring Stock', path: '/stock/expiring', icon: CalendarClock, permissions: ['stock_reports.view'] },
    ],
  },
  {
    label: 'Aid',
    items: [
      { label: 'Aid Batches', path: '/aid-batches', icon: Truck, permissions: ['aid_batches.view'] },
      { label: 'Aid Distributions', path: '/aid-distributions', icon: ClipboardCheck, permissions: ['aid_distributions.view'] },
    ],
  },
  {
    label: 'Visibility',
    items: [
      {
        label: 'Reports & Exports',
        path: '/reports',
        icon: FileBarChart,
        permissions: [
          'reports.donations.view',
          'reports.campaigns.view',
          'reports.beneficiaries.view',
          'reports.case_files.view',
          'reports.distributions.view',
          'reports.inventory.view',
          'reports.audit_logs.view',
          'exports.view',
        ],
      },
      { label: 'Public Portal Settings', path: '/settings/public-portal', icon: Settings, permissions: ['public_portal_settings.view'] },
      { label: 'Notifications', path: '/notifications', icon: Bell, permissions: ['notifications.view'] },
      { label: 'System', path: '/system', icon: Settings, permissions: ['system.queue.view', 'system.scheduler.view'] },
    ],
  },
]

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
          <input className="w-full rounded-md border border-[#c8d4cf] px-3 py-2" value={email} onChange={(event) => setEmail(event.target.value)} type="email" />
        </label>

        <label className="mb-5 block text-sm">
          <span className="mb-1 block font-medium text-[#10201a]">Password</span>
          <input className="w-full rounded-md border border-[#c8d4cf] px-3 py-2" value={password} onChange={(event) => setPassword(event.target.value)} type="password" />
        </label>

        {loginMutation.isError ? <div className="mb-4 rounded-md border border-[#efd8bb] bg-[#fff8ed] p-3 text-sm text-[#6c4b1f]">Login failed. Check the API server and credentials.</div> : null}

        <button className="w-full rounded-md bg-[#236b55] px-4 py-2 font-medium text-white disabled:opacity-60" disabled={loginMutation.isPending} type="submit">
          {loginMutation.isPending ? 'Signing in...' : 'Sign in'}
        </button>
      </form>
    </main>
  )
}

function AdminRouteGate() {
  const queryClient = useQueryClient()
  const [authToken, setAuthToken] = useState(() => getStoredToken())
  const me = useQuery({ queryKey: ['me'], queryFn: getMe, enabled: Boolean(authToken) })
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

  return <AdminShell me={me.data} onLogout={() => logoutMutation.mutate()} />
}

function AdminShell({ me, onLogout }: { me: CurrentUser; onLogout: () => void }) {
  const queryClient = useQueryClient()
  const location = useLocation()
  const [mobileNavOpen, setMobileNavOpen] = useState(false)
  const [notificationsOpen, setNotificationsOpen] = useState(false)
  const visibleGroups = useMemo(
    () =>
      navGroups
        .map((group) => ({
          ...group,
          items: group.items.filter((item) => canSeeNavItem(me, item)),
        }))
        .filter((group) => group.items.length > 0),
    [me],
  )
  const currentItem = visibleGroups.flatMap((group) => group.items).find((item) => location.pathname === item.path || location.pathname.startsWith(`${item.path}/`))
  const organization = useQuery({ queryKey: ['organization'], queryFn: getOrganization })
  const notifications = useQuery({ queryKey: ['notifications'], queryFn: getNotifications, refetchInterval: 30000 })
  const unreadNotifications = useQuery({ queryKey: ['notifications-unread-count'], queryFn: getUnreadNotificationCount, refetchInterval: 30000 })
  const markReadMutation = useMutation({
    mutationFn: markNotificationRead,
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['notifications'] })
      void queryClient.invalidateQueries({ queryKey: ['notifications-unread-count'] })
    },
  })
  const markAllReadMutation = useMutation({
    mutationFn: markAllNotificationsRead,
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['notifications'] })
      void queryClient.invalidateQueries({ queryKey: ['notifications-unread-count'] })
    },
  })

  return (
    <main className="min-h-svh bg-[#f6f8f7] text-[#172026]">
      <div className="flex min-h-svh">
        <aside className="hidden w-72 shrink-0 border-r border-[#d9e1de] bg-white lg:block">
          <SidebarNav groups={visibleGroups} onNavigate={() => undefined} />
        </aside>

        {mobileNavOpen ? (
          <div className="fixed inset-0 z-30 lg:hidden">
            <button className="absolute inset-0 bg-black/30" onClick={() => setMobileNavOpen(false)} type="button" aria-label="Close navigation" />
            <aside className="relative h-full w-[min(20rem,88vw)] border-r border-[#d9e1de] bg-white">
              <SidebarNav groups={visibleGroups} onNavigate={() => setMobileNavOpen(false)} />
            </aside>
          </div>
        ) : null}

        <section className="min-w-0 flex-1">
          <header className="sticky top-0 z-20 border-b border-[#d9e1de] bg-[#f6f8f7]/95 px-4 py-4 backdrop-blur md:px-6">
            <div className="flex flex-wrap items-center justify-between gap-3">
              <div className="flex min-w-0 items-center gap-3">
                <button className="rounded-md border border-[#c8d4cf] bg-white px-3 py-2 text-sm font-medium lg:hidden" onClick={() => setMobileNavOpen(true)} type="button">
                  Menu
                </button>
                <div className="min-w-0">
                  <p className="text-xs font-medium uppercase tracking-wide text-[#4b635b]">{organization.data?.name ?? 'Awniq'}</p>
                  <h1 className="truncate text-xl font-semibold text-[#10201a]">{currentItem?.label ?? 'Dashboard'}</h1>
                </div>
              </div>

              <div className="relative flex items-center gap-2">
                <button
                  className="relative inline-flex h-10 w-10 items-center justify-center rounded-md border border-[#c8d4cf] bg-white text-[#24332e]"
                  onClick={() => setNotificationsOpen((open) => !open)}
                  title="Notifications"
                  type="button"
                >
                  <Bell size={18} aria-hidden="true" />
                  {(unreadNotifications.data ?? 0) > 0 ? (
                    <span className="absolute -right-1 -top-1 min-w-5 rounded-full bg-[#a44a3f] px-1.5 py-0.5 text-center text-xs font-semibold text-white">{unreadNotifications.data}</span>
                  ) : null}
                </button>
                {notificationsOpen ? (
                  <NotificationDropdown
                    isLoading={notifications.isPending}
                    notifications={notifications.data}
                    onMarkAllRead={() => markAllReadMutation.mutate()}
                    onMarkRead={(id) => markReadMutation.mutate(id)}
                  />
                ) : null}
                <div className="hidden text-right text-xs text-[#52645e] sm:block">
                  <p className="font-medium text-[#10201a]">{me.name}</p>
                  <p>{me.email}</p>
                </div>
                <button className="inline-flex items-center gap-2 rounded-md border border-[#c8d4cf] bg-white px-3 py-2 text-sm" onClick={onLogout} type="button">
                  <LogOut size={16} aria-hidden="true" />
                  Logout
                </button>
              </div>
            </div>
          </header>

          <div className="px-4 py-6 md:px-6">
            <Outlet />
          </div>
        </section>
      </div>
    </main>
  )
}

function SidebarNav({ groups, onNavigate }: { groups: NavGroup[]; onNavigate: () => void }) {
  return (
    <nav className="flex h-full flex-col overflow-y-auto px-4 py-5">
      <div className="mb-6 border-b border-[#edf1ef] pb-4">
        <p className="text-sm font-medium uppercase tracking-wide text-[#4b635b]">Awniq</p>
        <p className="mt-1 text-lg font-semibold text-[#10201a]">Control System</p>
      </div>

      <div className="space-y-5">
        {groups.map((group) => (
          <div key={group.label}>
            <p className="mb-2 px-2 text-xs font-semibold uppercase tracking-wide text-[#7a8b85]">{group.label}</p>
            <ul className="space-y-1">
              {group.items.map((item) => {
                const Icon = item.icon

                return (
                  <li key={item.path}>
                    <NavLink
                      className={({ isActive }) =>
                        [
                          'flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium',
                          isActive ? 'bg-[#e8f2ee] text-[#174b3b]' : 'text-[#394a44] hover:bg-[#f1f5f3] hover:text-[#10201a]',
                        ].join(' ')
                      }
                      onClick={onNavigate}
                      to={item.path}
                    >
                      <Icon size={17} />
                      <span>{item.label}</span>
                    </NavLink>
                  </li>
                )
              })}
            </ul>
          </div>
        ))}
      </div>
    </nav>
  )
}

function DashboardPage() {
  const dashboardReport = useQuery({ queryKey: ['reports-dashboard'], queryFn: getDashboardReport })

  return (
    <ModulePage
      description="Operational metrics stay as the first screen, but the rest of the system now lives behind real module routes."
      title="Dashboard"
      planned={['Link metric cards to filtered module pages.', 'Add charts after module workflows are usable.']}
    >
      <Panel icon={<ListChecks size={20} />} title="Dashboard Metrics">
        {dashboardReport.data ? (
          <KeyValueRows
            rows={[
              ['Donations this month', `${dashboardReport.data.metrics.total_donations_this_month} EGP`],
              ['Active campaigns', String(dashboardReport.data.metrics.active_campaigns)],
              ['Pending cases', String(dashboardReport.data.metrics.pending_cases)],
              ['Approved beneficiaries', String(dashboardReport.data.metrics.approved_beneficiaries)],
              ['Aid batches in progress', String(dashboardReport.data.metrics.aid_batches_in_progress)],
              ['Completed distributions', String(dashboardReport.data.metrics.completed_distributions)],
              ['Low stock items', String(dashboardReport.data.metrics.low_stock_items)],
              ['Expiring stock lots', String(dashboardReport.data.metrics.expiring_stock_lots)],
            ]}
          />
        ) : (
          <LoadingOrEmpty isError={dashboardReport.isError} isLoading={dashboardReport.isPending} label="Loading dashboard metrics" />
        )}
      </Panel>
    </ModulePage>
  )
}

function OrganizationPage() {
  const queryClient = useQueryClient()
  const organization = useQuery({ queryKey: ['organization'], queryFn: getOrganization })
  const updateMutation = useMutation({
    mutationFn: updateOrganization,
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['organization'] })
    },
  })

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    const form = new FormData(event.currentTarget)
    const input: OrganizationInput = {
      name: formString(form, 'name'),
      legal_name: formNullable(form, 'legal_name'),
      slug: formString(form, 'slug'),
      email: formNullable(form, 'email'),
      phone: formNullable(form, 'phone'),
      website: formNullable(form, 'website'),
      logo: formNullable(form, 'logo'),
      country: formNullable(form, 'country'),
      city: formNullable(form, 'city'),
      address: formNullable(form, 'address'),
      default_currency: formString(form, 'default_currency').toUpperCase(),
      timezone: formString(form, 'timezone'),
      language: formString(form, 'language') as OrganizationInput['language'],
      status: formString(form, 'status') as OrganizationInput['status'],
    }

    updateMutation.mutate(input)
  }

  return (
    <ModulePage description="Manage the organization profile and operating defaults." title="Organization">
      <Panel icon={<Building2 size={20} />} title="Organization Profile">
        {organization.data ? (
          <KeyValueRows
            rows={[
              ['Name', organization.data.name],
              ['Slug', organization.data.slug],
              ['Email', organization.data.email],
              ['Currency', organization.data.default_currency],
              ['Timezone', organization.data.timezone],
              ['Status', organization.data.status],
            ]}
          />
        ) : (
          <LoadingOrEmpty isError={organization.isError} isLoading={organization.isPending} label="Loading organization" />
        )}
      </Panel>
      <Panel icon={<Settings size={20} />} title="Edit Organization">
        {organization.data ? (
          <form className="space-y-4" key={organization.data.id} onSubmit={handleSubmit}>
            <FormGrid>
              <TextField defaultValue={organization.data.name} label="Name" name="name" required />
              <TextField defaultValue={organization.data.legal_name ?? ''} label="Legal name" name="legal_name" />
              <TextField defaultValue={organization.data.slug} label="Slug" name="slug" required />
              <TextField defaultValue={organization.data.email ?? ''} label="Email" name="email" type="email" />
              <TextField defaultValue={organization.data.phone ?? ''} label="Phone" name="phone" />
              <TextField defaultValue={organization.data.website ?? ''} label="Website" name="website" type="url" />
              <TextField defaultValue={organization.data.country ?? ''} label="Country" name="country" />
              <TextField defaultValue={organization.data.city ?? ''} label="City" name="city" />
              <TextField defaultValue={organization.data.default_currency} label="Currency" maxLength={3} name="default_currency" required />
              <TextField defaultValue={organization.data.timezone} label="Timezone" name="timezone" required />
              <SelectField defaultValue={organization.data.language} label="Language" name="language" options={['en', 'ar']} />
              <SelectField defaultValue={organization.data.status} label="Status" name="status" options={['active', 'inactive']} />
            </FormGrid>
            <TextAreaField defaultValue={organization.data.address ?? ''} label="Address" name="address" />
            <TextField defaultValue={organization.data.logo ?? ''} label="Logo URL/path" name="logo" />
            <FormFooter isPending={updateMutation.isPending} submitLabel="Save organization" />
            <MutationState isError={updateMutation.isError} isSuccess={updateMutation.isSuccess} />
          </form>
        ) : (
          <LoadingOrEmpty isError={organization.isError} isLoading={organization.isPending} label="Loading organization form" />
        )}
      </Panel>
    </ModulePage>
  )
}

function BranchesPage() {
  const queryClient = useQueryClient()
  const [editingBranch, setEditingBranch] = useState<Branch | null>(null)
  const branches = useQuery({ queryKey: ['branches'], queryFn: getBranches })
  const users = useQuery({ queryKey: ['users'], queryFn: getUsers })
  const createMutation = useMutation({
    mutationFn: createBranch,
    onSuccess: () => {
      setEditingBranch(null)
      void queryClient.invalidateQueries({ queryKey: ['branches'] })
    },
  })
  const updateMutation = useMutation({
    mutationFn: ({ id, input }: { id: number; input: Parameters<typeof updateBranch>[1] }) => updateBranch(id, input),
    onSuccess: () => {
      setEditingBranch(null)
      void queryClient.invalidateQueries({ queryKey: ['branches'] })
    },
  })
  const deleteMutation = useMutation({
    mutationFn: deleteBranch,
    onSuccess: () => {
      setEditingBranch(null)
      void queryClient.invalidateQueries({ queryKey: ['branches'] })
    },
  })

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    const form = new FormData(event.currentTarget)
    const input = {
      name: formString(form, 'name'),
      code: formString(form, 'code'),
      phone: formNullable(form, 'phone'),
      email: formNullable(form, 'email'),
      country: formNullable(form, 'country'),
      city: formNullable(form, 'city'),
      address: formNullable(form, 'address'),
      manager_user_id: formNullableNumber(form, 'manager_user_id'),
      status: formString(form, 'status') as 'active' | 'inactive',
    }

    if (editingBranch) {
      updateMutation.mutate({ id: editingBranch.id, input })
    } else {
      createMutation.mutate(input)
    }
  }

  return (
    <ModulePage description="Manage operational branches and branch contact details." title="Branches">
      <Panel icon={<GitBranch size={20} />} title="Branches">
        <RecordList
          isError={branches.isError}
          isLoading={branches.isPending}
          items={branches.data}
          label="branches"
          render={(branch) => (
            <div className="flex flex-wrap items-center justify-between gap-2">
              <span>
                <StatusBadge status={branch.status} /> {branch.code} - {branch.name} - {branch.city ?? 'No city'} - manager {branch.manager?.name ?? 'none'}
              </span>
              <span className="flex gap-2">
                <SmallButton onClick={() => setEditingBranch(branch)}>Edit</SmallButton>
                <SmallButton danger onClick={() => window.confirm(`Delete ${branch.name}?`) && deleteMutation.mutate(branch.id)}>
                  Delete
                </SmallButton>
              </span>
            </div>
          )}
        />
      </Panel>
      <Panel icon={<FileText size={20} />} title="Branch Detail">
        {editingBranch ? (
          <KeyValueRows
            rows={[
              ['Name', editingBranch.name],
              ['Code', editingBranch.code],
              ['Status', editingBranch.status],
              ['Manager', editingBranch.manager?.email ?? 'None'],
              ['Email', editingBranch.email],
              ['Phone', editingBranch.phone],
              ['Location', [editingBranch.city, editingBranch.country].filter(Boolean).join(', ') || null],
              ['Address', editingBranch.address],
            ]}
          />
        ) : (
          <EmptyState title="Select a branch" />
        )}
      </Panel>
      <Panel icon={<Settings size={20} />} title={editingBranch ? `Edit ${editingBranch.name}` : 'Create Branch'}>
        <form className="space-y-4" key={editingBranch?.id ?? 'new'} onSubmit={handleSubmit}>
          <FormGrid>
            <TextField defaultValue={editingBranch?.name ?? ''} label="Name" name="name" required />
            <TextField defaultValue={editingBranch?.code ?? ''} label="Code" name="code" required />
            <TextField defaultValue={editingBranch?.phone ?? ''} label="Phone" name="phone" />
            <TextField defaultValue={editingBranch?.email ?? ''} label="Email" name="email" type="email" />
            <TextField defaultValue={editingBranch?.country ?? ''} label="Country" name="country" />
            <TextField defaultValue={editingBranch?.city ?? ''} label="City" name="city" />
            <SelectField
              defaultValue={editingBranch?.manager_user_id ? String(editingBranch.manager_user_id) : ''}
              label="Manager"
              name="manager_user_id"
              options={['', ...(users.data ?? []).map((user) => String(user.id))]}
              optionLabels={{ '': 'No manager', ...(users.data ?? []).reduce<Record<string, string>>((labels, user) => ({ ...labels, [String(user.id)]: `${user.name} (${user.email})` }), {}) }}
            />
            <SelectField defaultValue={editingBranch?.status ?? 'active'} label="Status" name="status" options={['active', 'inactive']} />
          </FormGrid>
          <TextAreaField defaultValue={editingBranch?.address ?? ''} label="Address" name="address" />
          <FormFooter isPending={createMutation.isPending || updateMutation.isPending} onCancel={editingBranch ? () => setEditingBranch(null) : undefined} submitLabel={editingBranch ? 'Save branch' : 'Create branch'} />
          <MutationState isError={createMutation.isError || updateMutation.isError || deleteMutation.isError} isSuccess={createMutation.isSuccess || updateMutation.isSuccess || deleteMutation.isSuccess} />
        </form>
      </Panel>
    </ModulePage>
  )
}

function UsersPage() {
  const queryClient = useQueryClient()
  const [editingUser, setEditingUser] = useState<User | null>(null)
  const users = useQuery({ queryKey: ['users'], queryFn: getUsers })
  const branches = useQuery({ queryKey: ['branches'], queryFn: getBranches })
  const roles = useQuery({ queryKey: ['roles'], queryFn: getRoles })
  const createMutation = useMutation({
    mutationFn: createUser,
    onSuccess: () => {
      setEditingUser(null)
      void queryClient.invalidateQueries({ queryKey: ['users'] })
    },
  })
  const updateMutation = useMutation({
    mutationFn: ({ id, input }: { id: number; input: Parameters<typeof updateUser>[1] }) => updateUser(id, input),
    onSuccess: () => {
      setEditingUser(null)
      void queryClient.invalidateQueries({ queryKey: ['users'] })
    },
  })
  const enableMutation = useMutation({
    mutationFn: enableUser,
    onSuccess: () => void queryClient.invalidateQueries({ queryKey: ['users'] }),
  })
  const disableMutation = useMutation({
    mutationFn: disableUser,
    onSuccess: () => void queryClient.invalidateQueries({ queryKey: ['users'] }),
  })
  const syncRolesMutation = useMutation({
    mutationFn: ({ id, roles }: { id: number; roles: string[] }) => syncUserRoles(id, roles),
    onSuccess: () => void queryClient.invalidateQueries({ queryKey: ['users'] }),
  })

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    const form = new FormData(event.currentTarget)
    const password = formString(form, 'password')
    const input = {
      name: formString(form, 'name'),
      email: formString(form, 'email'),
      phone: formNullable(form, 'phone'),
      branch_id: formNullableNumber(form, 'branch_id'),
      status: formString(form, 'status') as 'active' | 'disabled' | 'pending',
      roles: form.getAll('roles').map(String),
      ...(password ? { password } : {}),
    }

    if (editingUser) {
      updateMutation.mutate({ id: editingUser.id, input })
    } else {
      createMutation.mutate(input)
    }
  }

  return (
    <ModulePage description="Manage staff accounts, status, branches, and roles." title="Users">
      <Panel icon={<Users size={20} />} title="Users">
        <RecordList
          isError={users.isError}
          isLoading={users.isPending}
          items={users.data}
          label="users"
          render={(user) => (
            <div className="flex flex-wrap items-center justify-between gap-2">
              <span>
                <StatusBadge status={user.status} /> {user.name} - {user.email} - {user.branch?.code ?? 'No branch'} - {(user.roles ?? []).join(', ') || 'No role'}
              </span>
              <span className="flex flex-wrap gap-2">
                <SmallButton onClick={() => setEditingUser(user)}>Edit</SmallButton>
                {user.status === 'disabled' ? <SmallButton onClick={() => enableMutation.mutate(user.id)}>Enable</SmallButton> : <SmallButton danger onClick={() => window.confirm(`Disable ${user.name}?`) && disableMutation.mutate(user.id)}>Disable</SmallButton>}
              </span>
            </div>
          )}
        />
      </Panel>
      <Panel icon={<FileText size={20} />} title="User Detail">
        {editingUser ? (
          <KeyValueRows
            rows={[
              ['Name', editingUser.name],
              ['Email', editingUser.email],
              ['Status', editingUser.status],
              ['Phone', editingUser.phone],
              ['Branch', editingUser.branch ? `${editingUser.branch.code} - ${editingUser.branch.name}` : 'None'],
              ['Roles', (editingUser.roles ?? []).join(', ') || 'None'],
            ]}
          />
        ) : (
          <EmptyState title="Select a user" />
        )}
      </Panel>
      <Panel icon={<Settings size={20} />} title={editingUser ? `Edit ${editingUser.name}` : 'Create User'}>
        <form className="space-y-4" key={editingUser?.id ?? 'new'} onSubmit={handleSubmit}>
          <FormGrid>
            <TextField defaultValue={editingUser?.name ?? ''} label="Name" name="name" required />
            <TextField defaultValue={editingUser?.email ?? ''} label="Email" name="email" required type="email" />
            <TextField defaultValue={editingUser?.phone ?? ''} label="Phone" name="phone" />
            <TextField label={editingUser ? 'New password' : 'Password'} minLength={8} name="password" required={!editingUser} type="password" />
            <SelectField
              defaultValue={editingUser?.branch_id ? String(editingUser.branch_id) : ''}
              label="Branch"
              name="branch_id"
              options={['', ...(branches.data ?? []).map((branch) => String(branch.id))]}
              optionLabels={{ '': 'No branch', ...(branches.data ?? []).reduce<Record<string, string>>((labels, branch) => ({ ...labels, [String(branch.id)]: `${branch.code} - ${branch.name}` }), {}) }}
            />
            <SelectField defaultValue={editingUser?.status ?? 'active'} label="Status" name="status" options={['active', 'disabled', 'pending']} />
          </FormGrid>
          <CheckboxGroup defaultValues={editingUser?.roles ?? []} label="Roles" name="roles" options={(roles.data ?? []).map((role) => role.name)} />
          <FormFooter isPending={createMutation.isPending || updateMutation.isPending || syncRolesMutation.isPending} onCancel={editingUser ? () => setEditingUser(null) : undefined} submitLabel={editingUser ? 'Save user' : 'Create user'} />
          {editingUser ? (
            <button
              className="rounded-md border border-[#c8d4cf] bg-white px-3 py-2 text-sm"
              onClick={(event) => {
                const formElement = event.currentTarget.form

                if (formElement) {
                  syncRolesMutation.mutate({ id: editingUser.id, roles: new FormData(formElement).getAll('roles').map(String) })
                }
              }}
              type="button"
            >
              Save roles only
            </button>
          ) : null}
          <MutationState isError={createMutation.isError || updateMutation.isError || enableMutation.isError || disableMutation.isError || syncRolesMutation.isError} isSuccess={createMutation.isSuccess || updateMutation.isSuccess || enableMutation.isSuccess || disableMutation.isSuccess || syncRolesMutation.isSuccess} />
        </form>
      </Panel>
    </ModulePage>
  )
}

function RolesPage() {
  const queryClient = useQueryClient()
  const [editingRole, setEditingRole] = useState<Role | null>(null)
  const roles = useQuery({ queryKey: ['roles'], queryFn: getRoles })
  const permissions = useQuery({ queryKey: ['permissions'], queryFn: getPermissions })
  const createMutation = useMutation({
    mutationFn: createRole,
    onSuccess: () => {
      setEditingRole(null)
      void queryClient.invalidateQueries({ queryKey: ['roles'] })
    },
  })
  const updateMutation = useMutation({
    mutationFn: ({ id, input }: { id: number; input: Parameters<typeof updateRole>[1] }) => updateRole(id, input),
    onSuccess: () => {
      setEditingRole(null)
      void queryClient.invalidateQueries({ queryKey: ['roles'] })
    },
  })
  const deleteMutation = useMutation({
    mutationFn: deleteRole,
    onSuccess: () => {
      setEditingRole(null)
      void queryClient.invalidateQueries({ queryKey: ['roles'] })
    },
  })

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    const form = new FormData(event.currentTarget)
    const input = {
      name: formString(form, 'name'),
      permissions: form.getAll('permissions').map(String),
    }

    if (editingRole) {
      updateMutation.mutate({ id: editingRole.id, input })
    } else {
      createMutation.mutate(input)
    }
  }

  return (
    <ModulePage description="Review and manage roles and permission groups." title="Roles & Permissions">
      <Panel icon={<ShieldCheck size={20} />} title="Roles">
        <RecordList
          isError={roles.isError}
          isLoading={roles.isPending}
          items={roles.data}
          label="roles"
          render={(role) => (
            <div className="flex flex-wrap items-center justify-between gap-2">
              <span>
                {role.name} - {role.is_protected ? 'protected' : 'custom'} - {role.permissions?.length ?? 0} permissions
              </span>
              <span className="flex gap-2">
                <SmallButton onClick={() => setEditingRole(role)}>Edit</SmallButton>
                {!role.is_protected ? (
                  <SmallButton danger onClick={() => window.confirm(`Delete ${role.name}?`) && deleteMutation.mutate(role.id)}>
                    Delete
                  </SmallButton>
                ) : null}
              </span>
            </div>
          )}
        />
      </Panel>
      <Panel icon={<FileText size={20} />} title="Role Detail">
        {editingRole ? (
          <div className="space-y-4">
            <KeyValueRows
              rows={[
                ['Name', editingRole.name],
                ['Type', editingRole.is_protected ? 'Protected' : 'Custom'],
                ['Permissions', String(editingRole.permissions?.length ?? 0)],
              ]}
            />
            <RecordList items={editingRole.permissions ?? []} label="role permissions" render={(permission) => permission} />
          </div>
        ) : (
          <EmptyState title="Select a role" />
        )}
      </Panel>
      <Panel icon={<Settings size={20} />} title={editingRole ? `Edit ${editingRole.name}` : 'Create Role'}>
        <form className="space-y-4" key={editingRole?.id ?? 'new'} onSubmit={handleSubmit}>
          <TextField defaultValue={editingRole?.name ?? ''} label="Role name" name="name" readOnly={editingRole?.is_protected} required />
          <CheckboxGroup defaultValues={editingRole?.permissions ?? []} isLoading={permissions.isPending} label="Permissions" name="permissions" options={(permissions.data ?? []).map((permission) => permission.name)} />
          <FormFooter isPending={createMutation.isPending || updateMutation.isPending} onCancel={editingRole ? () => setEditingRole(null) : undefined} submitLabel={editingRole ? 'Save role' : 'Create role'} />
          <MutationState isError={createMutation.isError || updateMutation.isError || deleteMutation.isError} isSuccess={createMutation.isSuccess || updateMutation.isSuccess || deleteMutation.isSuccess} />
        </form>
      </Panel>
    </ModulePage>
  )
}

function AuditLogsPage() {
  const [selectedLog, setSelectedLog] = useState<AuditLog | null>(null)
  const auditLogs = useQuery({ queryKey: ['audit-logs'], queryFn: getAuditLogs })

  return (
    <ModulePage description="Inspect audited state changes and sensitive operations." title="Audit Logs" planned={['Add filters by date/action/user/entity.']}>
      <Panel icon={<ClipboardList size={20} />} title="Recent Audit Logs">
        <RecordList
          isError={auditLogs.isError}
          isLoading={auditLogs.isPending}
          items={auditLogs.data}
          label="audit logs"
          render={(log) => (
            <div className="flex flex-wrap items-center justify-between gap-2">
              <span>
                {log.action} - {log.entity_type} #{log.entity_id ?? '-'} - {log.user?.email ?? 'system'} - {formatDate(log.created_at)}
              </span>
              <SmallButton onClick={() => setSelectedLog(log)}>Details</SmallButton>
            </div>
          )}
        />
      </Panel>
      <Panel icon={<FileText size={20} />} title="Audit Detail">
        {selectedLog ? (
          <div className="space-y-4 text-sm">
            <KeyValueRows
              rows={[
                ['Action', selectedLog.action],
                ['Entity', `${selectedLog.entity_type} #${selectedLog.entity_id ?? '-'}`],
                ['User', selectedLog.user?.email ?? 'system'],
                ['IP address', selectedLog.ip_address],
                ['Created', formatDate(selectedLog.created_at)],
              ]}
            />
            <JsonBlock label="Old values" value={selectedLog.old_values} />
            <JsonBlock label="New values" value={selectedLog.new_values} />
          </div>
        ) : (
          <EmptyState title="Select an audit log" />
        )}
      </Panel>
    </ModulePage>
  )
}

function BeneficiariesPage() {
  const queryClient = useQueryClient()
  const [selectedBeneficiaryId, setSelectedBeneficiaryId] = useState<number | null>(null)
  const [editingBeneficiaryId, setEditingBeneficiaryId] = useState<number | null>(null)
  const [editingFamilyMember, setEditingFamilyMember] = useState<FamilyMember | null>(null)
  const beneficiaries = useQuery({ queryKey: ['beneficiaries'], queryFn: getBeneficiaries })
  const branches = useQuery({ queryKey: ['branches'], queryFn: getBranches })
  const beneficiaryDetail = useQuery({
    queryKey: ['beneficiary', selectedBeneficiaryId],
    queryFn: () => getBeneficiary(selectedBeneficiaryId as number),
    enabled: selectedBeneficiaryId !== null,
  })
  const editingBeneficiary = editingBeneficiaryId ? beneficiaryDetail.data ?? beneficiaries.data?.find((beneficiary) => beneficiary.id === editingBeneficiaryId) ?? null : null

  useEffect(() => {
    setEditingFamilyMember(null)
  }, [selectedBeneficiaryId])

  function refreshBeneficiaries(id?: number | null) {
    void queryClient.invalidateQueries({ queryKey: ['beneficiaries'] })

    if (id) {
      void queryClient.invalidateQueries({ queryKey: ['beneficiary', id] })
    }
  }

  const createMutation = useMutation({
    mutationFn: createBeneficiary,
    onSuccess: (beneficiary) => {
      setSelectedBeneficiaryId(beneficiary.id)
      setEditingBeneficiaryId(null)
      refreshBeneficiaries(beneficiary.id)
    },
  })
  const updateMutation = useMutation({
    mutationFn: ({ id, input }: { id: number; input: BeneficiaryInput }) => updateBeneficiary(id, input),
    onSuccess: (beneficiary) => {
      setSelectedBeneficiaryId(beneficiary.id)
      setEditingBeneficiaryId(null)
      refreshBeneficiaries(beneficiary.id)
    },
  })
  const deleteMutation = useMutation({
    mutationFn: deleteBeneficiary,
    onSuccess: (_, id) => {
      if (selectedBeneficiaryId === id) {
        setSelectedBeneficiaryId(null)
        setEditingBeneficiaryId(null)
      }

      refreshBeneficiaries(null)
    },
  })
  const workflowMutation = useMutation({
    mutationFn: ({ action, id, reason }: { action: 'submit' | 'approve' | 'reject' | 'suspend' | 'reactivate'; id: number; reason?: string }) => {
      if (action === 'submit') {
        return submitBeneficiaryReview(id)
      }

      if (action === 'approve') {
        return approveBeneficiary(id)
      }

      if (action === 'reject') {
        return rejectBeneficiary(id, reason ?? '')
      }

      if (action === 'suspend') {
        return suspendBeneficiary(id)
      }

      return reactivateBeneficiary(id)
    },
    onSuccess: (beneficiary) => refreshBeneficiaries(beneficiary.id),
  })
  const createFamilyMutation = useMutation({
    mutationFn: ({ beneficiaryId, input }: { beneficiaryId: number; input: FamilyMemberInput }) => createFamilyMember(beneficiaryId, input),
    onSuccess: (_, variables) => {
      setEditingFamilyMember(null)
      refreshBeneficiaries(variables.beneficiaryId)
    },
  })
  const updateFamilyMutation = useMutation({
    mutationFn: ({ beneficiaryId, familyMemberId, input }: { beneficiaryId: number; familyMemberId: number; input: FamilyMemberInput }) => updateFamilyMember(beneficiaryId, familyMemberId, input),
    onSuccess: (_, variables) => {
      setEditingFamilyMember(null)
      refreshBeneficiaries(variables.beneficiaryId)
    },
  })
  const deleteFamilyMutation = useMutation({
    mutationFn: ({ beneficiaryId, familyMemberId }: { beneficiaryId: number; familyMemberId: number }) => deleteFamilyMember(beneficiaryId, familyMemberId),
    onSuccess: (_, variables) => refreshBeneficiaries(variables.beneficiaryId),
  })

  function buildBeneficiaryInput(form: FormData): BeneficiaryInput {
    return {
      branch_id: Number(formString(form, 'branch_id')),
      full_name: formString(form, 'full_name'),
      national_id: formNullable(form, 'national_id'),
      birth_date: formNullable(form, 'birth_date'),
      gender: formNullable(form, 'gender') as BeneficiaryInput['gender'],
      phone: formNullable(form, 'phone'),
      alternate_phone: formNullable(form, 'alternate_phone'),
      email: formNullable(form, 'email'),
      country: formNullable(form, 'country'),
      city: formNullable(form, 'city'),
      district: formNullable(form, 'district'),
      address: formNullable(form, 'address'),
      marital_status: formNullable(form, 'marital_status'),
      employment_status: formNullable(form, 'employment_status'),
      monthly_income: formNullableNumber(form, 'monthly_income'),
      household_size: formNullableNumber(form, 'household_size'),
      vulnerability_level: formNullable(form, 'vulnerability_level') as BeneficiaryInput['vulnerability_level'],
      status: formNullable(form, 'status') as BeneficiaryInput['status'],
    }
  }

  function buildFamilyMemberInput(form: FormData): FamilyMemberInput {
    return {
      full_name: formString(form, 'family_full_name'),
      relationship: formString(form, 'relationship'),
      birth_date: formNullable(form, 'family_birth_date'),
      gender: formNullable(form, 'family_gender') as FamilyMemberInput['gender'],
      national_id: formNullable(form, 'family_national_id'),
      education_level: formNullable(form, 'education_level'),
      employment_status: formNullable(form, 'family_employment_status'),
      health_notes: formNullable(form, 'health_notes'),
    }
  }

  function handleBeneficiarySubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    const input = buildBeneficiaryInput(new FormData(event.currentTarget))

    if (editingBeneficiaryId) {
      updateMutation.mutate({ id: editingBeneficiaryId, input })
    } else {
      createMutation.mutate(input)
    }
  }

  function handleFamilyMemberSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()

    if (!selectedBeneficiaryId) {
      return
    }

    const input = buildFamilyMemberInput(new FormData(event.currentTarget))

    if (editingFamilyMember) {
      updateFamilyMutation.mutate({ beneficiaryId: selectedBeneficiaryId, familyMemberId: editingFamilyMember.id, input })
    } else {
      createFamilyMutation.mutate({ beneficiaryId: selectedBeneficiaryId, input })
    }
  }

  function runBeneficiaryReject(id: number) {
    const reason = window.prompt('Rejection reason')?.trim()

    if (reason) {
      workflowMutation.mutate({ action: 'reject', id, reason })
    }
  }

  return (
    <ModulePage description="Register beneficiaries, review eligibility, and manage household details." title="Beneficiaries">
      <Panel icon={<Users size={20} />} title="Beneficiaries">
        <RecordList
          isError={beneficiaries.isError}
          isLoading={beneficiaries.isPending}
          items={beneficiaries.data}
          label="beneficiaries"
          render={(beneficiary) => (
            <div className="flex flex-wrap items-center justify-between gap-2">
              <span>
                <StatusBadge status={beneficiary.status} /> {beneficiary.code} - {beneficiary.full_name} - {beneficiary.vulnerability_level} - {beneficiary.household_size} household
              </span>
              <span className="flex flex-wrap gap-2">
                <SmallButton onClick={() => setSelectedBeneficiaryId(beneficiary.id)}>Details</SmallButton>
                <SmallButton
                  onClick={() => {
                    setSelectedBeneficiaryId(beneficiary.id)
                    setEditingBeneficiaryId(beneficiary.id)
                  }}
                >
                  Edit
                </SmallButton>
                <SmallButton danger onClick={() => window.confirm(`Delete ${beneficiary.full_name}?`) && deleteMutation.mutate(beneficiary.id)}>
                  Delete
                </SmallButton>
              </span>
            </div>
          )}
        />
      </Panel>
      <Panel icon={<FileText size={20} />} title="Beneficiary Detail">
        {selectedBeneficiaryId ? (
          beneficiaryDetail.data ? (
            <div className="space-y-4">
              <KeyValueRows
                rows={[
                  ['Code', beneficiaryDetail.data.code],
                  ['Name', beneficiaryDetail.data.full_name],
                  ['Branch', beneficiaryDetail.data.branch ? `${beneficiaryDetail.data.branch.code} - ${beneficiaryDetail.data.branch.name}` : null],
                  ['Status', beneficiaryDetail.data.status],
                  ['Vulnerability', beneficiaryDetail.data.vulnerability_level],
                  ['Household size', String(beneficiaryDetail.data.household_size)],
                  ['National ID', beneficiaryDetail.data.national_id],
                  ['Phone', beneficiaryDetail.data.phone],
                  ['Location', [beneficiaryDetail.data.city, beneficiaryDetail.data.district, beneficiaryDetail.data.country].filter(Boolean).join(', ') || null],
                  ['Rejection reason', beneficiaryDetail.data.rejection_reason],
                ]}
              />
              <div className="flex flex-wrap gap-2">
                {['draft', 'rejected'].includes(beneficiaryDetail.data.status) ? <SmallButton onClick={() => workflowMutation.mutate({ action: 'submit', id: beneficiaryDetail.data.id })}>Submit review</SmallButton> : null}
                {beneficiaryDetail.data.status === 'pending_review' ? <SmallButton onClick={() => workflowMutation.mutate({ action: 'approve', id: beneficiaryDetail.data.id })}>Approve</SmallButton> : null}
                {beneficiaryDetail.data.status === 'pending_review' ? <SmallButton danger onClick={() => runBeneficiaryReject(beneficiaryDetail.data.id)}>Reject</SmallButton> : null}
                {['draft', 'pending_review', 'approved', 'rejected'].includes(beneficiaryDetail.data.status) ? <SmallButton danger onClick={() => workflowMutation.mutate({ action: 'suspend', id: beneficiaryDetail.data.id })}>Suspend</SmallButton> : null}
                {['suspended', 'archived'].includes(beneficiaryDetail.data.status) ? <SmallButton onClick={() => workflowMutation.mutate({ action: 'reactivate', id: beneficiaryDetail.data.id })}>Reactivate</SmallButton> : null}
              </div>
            </div>
          ) : (
            <LoadingOrEmpty isError={beneficiaryDetail.isError} isLoading={beneficiaryDetail.isPending} label="Loading beneficiary" />
          )
        ) : (
          <EmptyState title="Select a beneficiary" />
        )}
      </Panel>
      <Panel icon={<Settings size={20} />} title={editingBeneficiary ? `Edit ${editingBeneficiary.full_name}` : 'Create Beneficiary'}>
        <form className="space-y-4" key={editingBeneficiary?.id ?? 'new-beneficiary'} onSubmit={handleBeneficiarySubmit}>
          <FormGrid>
            <SelectField
              defaultValue={editingBeneficiary?.branch_id ? String(editingBeneficiary.branch_id) : ''}
              label="Branch"
              name="branch_id"
              optionLabels={{ '': 'Select branch', ...(branches.data ?? []).reduce<Record<string, string>>((labels, branch) => ({ ...labels, [String(branch.id)]: `${branch.code} - ${branch.name}` }), {}) }}
              options={['', ...(branches.data ?? []).map((branch) => String(branch.id))]}
              required
            />
            <TextField defaultValue={editingBeneficiary?.full_name ?? ''} label="Full name" name="full_name" required />
            <TextField defaultValue={editingBeneficiary?.national_id ?? ''} label="National ID" name="national_id" />
            <TextField defaultValue={editingBeneficiary?.birth_date ?? ''} label="Birth date" name="birth_date" type="date" />
            <SelectField defaultValue={editingBeneficiary?.gender ?? 'unknown'} label="Gender" name="gender" options={['unknown', 'male', 'female', 'other']} />
            <TextField defaultValue={editingBeneficiary?.phone ?? ''} label="Phone" name="phone" />
            <TextField defaultValue={editingBeneficiary?.alternate_phone ?? ''} label="Alternate phone" name="alternate_phone" />
            <TextField defaultValue={editingBeneficiary?.email ?? ''} label="Email" name="email" type="email" />
            <TextField defaultValue={editingBeneficiary?.country ?? ''} label="Country" name="country" />
            <TextField defaultValue={editingBeneficiary?.city ?? ''} label="City" name="city" />
            <TextField defaultValue={editingBeneficiary?.district ?? ''} label="District" name="district" />
            <SelectField defaultValue={editingBeneficiary?.marital_status ?? 'unknown'} label="Marital status" name="marital_status" options={['unknown', 'single', 'married', 'widowed', 'divorced', 'separated']} />
            <SelectField defaultValue={editingBeneficiary?.employment_status ?? 'unknown'} label="Employment" name="employment_status" options={['unknown', 'employed', 'unemployed', 'self_employed', 'student', 'retired', 'unable_to_work']} />
            <TextField defaultValue={editingBeneficiary?.monthly_income ?? ''} label="Monthly income" min={0} name="monthly_income" step="0.01" type="number" />
            <TextField defaultValue={editingBeneficiary?.household_size ?? 1} label="Household size" max={100} min={1} name="household_size" type="number" />
            <SelectField defaultValue={editingBeneficiary?.vulnerability_level ?? 'medium'} label="Vulnerability" name="vulnerability_level" options={['low', 'medium', 'high', 'critical']} />
            <SelectField defaultValue={editingBeneficiary?.status ?? 'draft'} label="Status" name="status" options={['draft', 'pending_review', 'approved', 'rejected', 'suspended', 'archived']} />
          </FormGrid>
          <TextAreaField defaultValue={editingBeneficiary?.address ?? ''} label="Address" name="address" />
          <FormFooter isPending={createMutation.isPending || updateMutation.isPending} onCancel={editingBeneficiaryId ? () => setEditingBeneficiaryId(null) : undefined} submitLabel={editingBeneficiary ? 'Save beneficiary' : 'Create beneficiary'} />
          <MutationState isError={createMutation.isError || updateMutation.isError || deleteMutation.isError || workflowMutation.isError} isSuccess={createMutation.isSuccess || updateMutation.isSuccess || deleteMutation.isSuccess || workflowMutation.isSuccess} />
        </form>
      </Panel>
      <Panel icon={<Users size={20} />} title="Family Members">
        {beneficiaryDetail.data ? (
          <div className="space-y-4">
            <RecordList
              items={beneficiaryDetail.data.family_members ?? []}
              label="family members"
              render={(familyMember) => (
                <div className="flex flex-wrap items-center justify-between gap-2">
                  <span>
                    {familyMember.full_name} - {familyMember.relationship} - {familyMember.gender ?? 'unknown'}
                  </span>
                  <span className="flex gap-2">
                    <SmallButton onClick={() => setEditingFamilyMember(familyMember)}>Edit</SmallButton>
                    <SmallButton danger onClick={() => selectedBeneficiaryId && window.confirm(`Delete ${familyMember.full_name}?`) && deleteFamilyMutation.mutate({ beneficiaryId: selectedBeneficiaryId, familyMemberId: familyMember.id })}>
                      Delete
                    </SmallButton>
                  </span>
                </div>
              )}
            />
            <form className="space-y-4" key={editingFamilyMember?.id ?? `family-${selectedBeneficiaryId}`} onSubmit={handleFamilyMemberSubmit}>
              <FormGrid>
                <TextField defaultValue={editingFamilyMember?.full_name ?? ''} label="Full name" name="family_full_name" required />
                <TextField defaultValue={editingFamilyMember?.relationship ?? ''} label="Relationship" name="relationship" required />
                <TextField defaultValue={editingFamilyMember?.birth_date ?? ''} label="Birth date" name="family_birth_date" type="date" />
                <SelectField defaultValue={editingFamilyMember?.gender ?? 'unknown'} label="Gender" name="family_gender" options={['unknown', 'male', 'female', 'other']} />
                <TextField defaultValue={editingFamilyMember?.national_id ?? ''} label="National ID" name="family_national_id" />
                <TextField defaultValue={editingFamilyMember?.education_level ?? ''} label="Education" name="education_level" />
                <TextField defaultValue={editingFamilyMember?.employment_status ?? ''} label="Employment" name="family_employment_status" />
              </FormGrid>
              <TextAreaField defaultValue={editingFamilyMember?.health_notes ?? ''} label="Health notes" name="health_notes" />
              <FormFooter isPending={createFamilyMutation.isPending || updateFamilyMutation.isPending} onCancel={editingFamilyMember ? () => setEditingFamilyMember(null) : undefined} submitLabel={editingFamilyMember ? 'Save family member' : 'Add family member'} />
              <MutationState isError={createFamilyMutation.isError || updateFamilyMutation.isError || deleteFamilyMutation.isError} isSuccess={createFamilyMutation.isSuccess || updateFamilyMutation.isSuccess || deleteFamilyMutation.isSuccess} />
            </form>
          </div>
        ) : (
          <EmptyState title="Select a beneficiary" />
        )}
      </Panel>
    </ModulePage>
  )
}

function CaseFilesPage() {
  const queryClient = useQueryClient()
  const [selectedCaseFileId, setSelectedCaseFileId] = useState<number | null>(null)
  const [editingCaseFileId, setEditingCaseFileId] = useState<number | null>(null)
  const [editingCaseNote, setEditingCaseNote] = useState<CaseNote | null>(null)
  const caseFiles = useQuery({ queryKey: ['case-files'], queryFn: getCaseFiles })
  const beneficiaries = useQuery({ queryKey: ['beneficiaries'], queryFn: getBeneficiaries })
  const users = useQuery({ queryKey: ['users'], queryFn: getUsers })
  const caseFileDetail = useQuery({
    queryKey: ['case-file', selectedCaseFileId],
    queryFn: () => getCaseFile(selectedCaseFileId as number),
    enabled: selectedCaseFileId !== null,
  })
  const editingCaseFile = editingCaseFileId ? caseFileDetail.data ?? caseFiles.data?.find((caseFile) => caseFile.id === editingCaseFileId) ?? null : null

  useEffect(() => {
    setEditingCaseNote(null)
  }, [selectedCaseFileId])

  function refreshCaseFiles(id?: number | null) {
    void queryClient.invalidateQueries({ queryKey: ['case-files'] })
    void queryClient.invalidateQueries({ queryKey: ['beneficiaries'] })

    if (id) {
      void queryClient.invalidateQueries({ queryKey: ['case-file', id] })
    }
  }

  const createMutation = useMutation({
    mutationFn: createCaseFile,
    onSuccess: (caseFile) => {
      setSelectedCaseFileId(caseFile.id)
      setEditingCaseFileId(null)
      refreshCaseFiles(caseFile.id)
    },
  })
  const updateMutation = useMutation({
    mutationFn: ({ id, input }: { id: number; input: CaseFileInput }) => updateCaseFile(id, input),
    onSuccess: (caseFile) => {
      setSelectedCaseFileId(caseFile.id)
      setEditingCaseFileId(null)
      refreshCaseFiles(caseFile.id)
    },
  })
  const deleteMutation = useMutation({
    mutationFn: deleteCaseFile,
    onSuccess: (_, id) => {
      if (selectedCaseFileId === id) {
        setSelectedCaseFileId(null)
        setEditingCaseFileId(null)
      }

      refreshCaseFiles(null)
    },
  })
  const workflowMutation = useMutation({
    mutationFn: ({ action, id, reason }: { action: 'submit' | 'approve' | 'reject' | 'suspend' | 'close' | 'reopen'; id: number; reason?: string }) => {
      if (action === 'submit') {
        return submitCaseReview(id)
      }

      if (action === 'approve') {
        return approveCaseFile(id)
      }

      if (action === 'reject') {
        return rejectCaseFile(id, reason ?? '')
      }

      if (action === 'suspend') {
        return suspendCaseFile(id)
      }

      if (action === 'close') {
        return closeCaseFile(id)
      }

      return reopenCaseFile(id)
    },
    onSuccess: (caseFile) => refreshCaseFiles(caseFile.id),
  })
  const createNoteMutation = useMutation({
    mutationFn: ({ caseFileId, input }: { caseFileId: number; input: Parameters<typeof createCaseNote>[1] }) => createCaseNote(caseFileId, input),
    onSuccess: (_, variables) => {
      setEditingCaseNote(null)
      refreshCaseFiles(variables.caseFileId)
    },
  })
  const updateNoteMutation = useMutation({
    mutationFn: ({ caseFileId, input, noteId }: { caseFileId: number; input: Parameters<typeof updateCaseNote>[2]; noteId: number }) => updateCaseNote(caseFileId, noteId, input),
    onSuccess: (_, variables) => {
      setEditingCaseNote(null)
      refreshCaseFiles(variables.caseFileId)
    },
  })
  const deleteNoteMutation = useMutation({
    mutationFn: ({ caseFileId, noteId }: { caseFileId: number; noteId: number }) => deleteCaseNote(caseFileId, noteId),
    onSuccess: (_, variables) => refreshCaseFiles(variables.caseFileId),
  })
  const uploadDocumentMutation = useMutation({
    mutationFn: ({ caseFileId, file, type }: { caseFileId: number; file: File; type: string }) => uploadCaseDocument(caseFileId, { document_type: type, file }),
    onSuccess: (_, variables) => refreshCaseFiles(variables.caseFileId),
  })
  const deleteDocumentMutation = useMutation({
    mutationFn: ({ caseFileId, documentId }: { caseFileId: number; documentId: number }) => deleteCaseDocument(caseFileId, documentId),
    onSuccess: (_, variables) => refreshCaseFiles(variables.caseFileId),
  })
  const downloadDocumentMutation = useMutation({
    mutationFn: async ({ caseDocument, caseFileId }: { caseDocument: CaseDocument; caseFileId: number }) => {
      const blob = await downloadCaseDocument(caseFileId, caseDocument.id)
      const url = URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = caseDocument.original_filename
      link.click()
      URL.revokeObjectURL(url)
    },
  })

  function buildCaseFileInput(form: FormData): CaseFileInput {
    return {
      beneficiary_id: Number(formString(form, 'beneficiary_id')),
      case_type: formString(form, 'case_type'),
      priority: formNullable(form, 'priority') as CaseFileInput['priority'],
      status: formNullable(form, 'status') as CaseFileInput['status'],
      assigned_to_user_id: formNullableNumber(form, 'assigned_to_user_id'),
      rejection_reason: formNullable(form, 'case_rejection_reason'),
      assessment_summary: formNullable(form, 'assessment_summary'),
      next_follow_up_date: formNullable(form, 'next_follow_up_date'),
    }
  }

  function handleCaseFileSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    const input = buildCaseFileInput(new FormData(event.currentTarget))

    if (editingCaseFileId) {
      updateMutation.mutate({ id: editingCaseFileId, input })
    } else {
      createMutation.mutate(input)
    }
  }

  function handleCaseNoteSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()

    if (!selectedCaseFileId) {
      return
    }

    const form = new FormData(event.currentTarget)
    const input = {
      note: formString(form, 'case_note'),
      visibility: formNullable(form, 'note_visibility') as Parameters<typeof createCaseNote>[1]['visibility'],
    }

    if (editingCaseNote) {
      updateNoteMutation.mutate({ caseFileId: selectedCaseFileId, input, noteId: editingCaseNote.id })
    } else {
      createNoteMutation.mutate({ caseFileId: selectedCaseFileId, input })
    }
  }

  function handleDocumentUpload(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()

    if (!selectedCaseFileId) {
      return
    }

    const form = new FormData(event.currentTarget)
    const file = form.get('file')

    if (file instanceof File && file.size > 0) {
      uploadDocumentMutation.mutate({ caseFileId: selectedCaseFileId, file, type: formString(form, 'document_type') })
    }
  }

  function runCaseReject(id: number) {
    const reason = window.prompt('Rejection reason')?.trim()

    if (reason) {
      workflowMutation.mutate({ action: 'reject', id, reason })
    }
  }

  return (
    <ModulePage description="Manage case files, notes, documents, and approval workflows." title="Case Files">
      <Panel icon={<FileText size={20} />} title="Case Files">
        <RecordList
          isError={caseFiles.isError}
          isLoading={caseFiles.isPending}
          items={caseFiles.data}
          label="case files"
          render={(caseFile) => (
            <div className="flex flex-wrap items-center justify-between gap-2">
              <span>
                <StatusBadge status={caseFile.status} /> {caseFile.case_number} - {caseFile.beneficiary?.full_name ?? 'Unassigned'} - {caseFile.case_type} - {caseFile.priority}
              </span>
              <span className="flex flex-wrap gap-2">
                <SmallButton onClick={() => setSelectedCaseFileId(caseFile.id)}>Details</SmallButton>
                <SmallButton
                  onClick={() => {
                    setSelectedCaseFileId(caseFile.id)
                    setEditingCaseFileId(caseFile.id)
                  }}
                >
                  Edit
                </SmallButton>
                <SmallButton danger onClick={() => window.confirm(`Delete ${caseFile.case_number}?`) && deleteMutation.mutate(caseFile.id)}>
                  Delete
                </SmallButton>
              </span>
            </div>
          )}
        />
      </Panel>
      <Panel icon={<FileText size={20} />} title="Case Detail">
        {selectedCaseFileId ? (
          caseFileDetail.data ? (
            <div className="space-y-4">
              <KeyValueRows
                rows={[
                  ['Case number', caseFileDetail.data.case_number],
                  ['Beneficiary', caseFileDetail.data.beneficiary ? `${caseFileDetail.data.beneficiary.code} - ${caseFileDetail.data.beneficiary.full_name}` : null],
                  ['Status', caseFileDetail.data.status],
                  ['Type', caseFileDetail.data.case_type],
                  ['Priority', caseFileDetail.data.priority],
                  ['Assigned to', caseFileDetail.data.assigned_to?.email ?? null],
                  ['Next follow-up', caseFileDetail.data.next_follow_up_date],
                  ['Notes', String(caseFileDetail.data.notes_count ?? caseFileDetail.data.notes?.length ?? 0)],
                  ['Documents', String(caseFileDetail.data.documents_count ?? caseFileDetail.data.documents?.length ?? 0)],
                  ['Rejection reason', caseFileDetail.data.rejection_reason],
                ]}
              />
              <p className="text-sm leading-6 text-[#52645e]">{caseFileDetail.data.assessment_summary ?? 'No assessment summary.'}</p>
              <div className="flex flex-wrap gap-2">
                {['open', 'rejected'].includes(caseFileDetail.data.status) ? <SmallButton onClick={() => workflowMutation.mutate({ action: 'submit', id: caseFileDetail.data.id })}>Submit review</SmallButton> : null}
                {caseFileDetail.data.status === 'under_review' ? <SmallButton onClick={() => workflowMutation.mutate({ action: 'approve', id: caseFileDetail.data.id })}>Approve</SmallButton> : null}
                {caseFileDetail.data.status === 'under_review' ? <SmallButton danger onClick={() => runCaseReject(caseFileDetail.data.id)}>Reject</SmallButton> : null}
                {['open', 'under_review', 'approved', 'rejected'].includes(caseFileDetail.data.status) ? <SmallButton danger onClick={() => workflowMutation.mutate({ action: 'suspend', id: caseFileDetail.data.id })}>Suspend</SmallButton> : null}
                {['open', 'under_review', 'approved', 'rejected', 'suspended'].includes(caseFileDetail.data.status) ? <SmallButton onClick={() => workflowMutation.mutate({ action: 'close', id: caseFileDetail.data.id })}>Close</SmallButton> : null}
                {['closed', 'rejected', 'suspended'].includes(caseFileDetail.data.status) ? <SmallButton onClick={() => workflowMutation.mutate({ action: 'reopen', id: caseFileDetail.data.id })}>Reopen</SmallButton> : null}
              </div>
            </div>
          ) : (
            <LoadingOrEmpty isError={caseFileDetail.isError} isLoading={caseFileDetail.isPending} label="Loading case file" />
          )
        ) : (
          <EmptyState title="Select a case file" />
        )}
      </Panel>
      <Panel icon={<Settings size={20} />} title={editingCaseFile ? `Edit ${editingCaseFile.case_number}` : 'Create Case File'}>
        <form className="space-y-4" key={editingCaseFile?.id ?? 'new-case-file'} onSubmit={handleCaseFileSubmit}>
          <FormGrid>
            <SelectField
              defaultValue={editingCaseFile?.beneficiary_id ? String(editingCaseFile.beneficiary_id) : ''}
              label="Beneficiary"
              name="beneficiary_id"
              optionLabels={{ '': 'Select beneficiary', ...(beneficiaries.data ?? []).reduce<Record<string, string>>((labels, beneficiary) => ({ ...labels, [String(beneficiary.id)]: `${beneficiary.code} - ${beneficiary.full_name}` }), {}) }}
              options={['', ...(beneficiaries.data ?? []).map((beneficiary) => String(beneficiary.id))]}
              required
            />
            <TextField defaultValue={editingCaseFile?.case_type ?? ''} label="Case type" name="case_type" required />
            <SelectField defaultValue={editingCaseFile?.priority ?? 'medium'} label="Priority" name="priority" options={['low', 'medium', 'high', 'urgent']} />
            <SelectField defaultValue={editingCaseFile?.status ?? 'open'} label="Status" name="status" options={['open', 'under_review', 'approved', 'rejected', 'suspended', 'closed']} />
            <SelectField
              defaultValue={editingCaseFile?.assigned_to_user_id ? String(editingCaseFile.assigned_to_user_id) : ''}
              label="Assigned to"
              name="assigned_to_user_id"
              optionLabels={{ '': 'Unassigned', ...(users.data ?? []).reduce<Record<string, string>>((labels, user) => ({ ...labels, [String(user.id)]: `${user.name} (${user.email})` }), {}) }}
              options={['', ...(users.data ?? []).map((user) => String(user.id))]}
            />
            <TextField defaultValue={editingCaseFile?.next_follow_up_date ?? ''} label="Next follow-up" name="next_follow_up_date" type="date" />
          </FormGrid>
          <TextAreaField defaultValue={editingCaseFile?.assessment_summary ?? ''} label="Assessment summary" name="assessment_summary" />
          <TextAreaField defaultValue={editingCaseFile?.rejection_reason ?? ''} label="Rejection reason" name="case_rejection_reason" />
          <FormFooter isPending={createMutation.isPending || updateMutation.isPending} onCancel={editingCaseFileId ? () => setEditingCaseFileId(null) : undefined} submitLabel={editingCaseFile ? 'Save case file' : 'Create case file'} />
          <MutationState isError={createMutation.isError || updateMutation.isError || deleteMutation.isError || workflowMutation.isError} isSuccess={createMutation.isSuccess || updateMutation.isSuccess || deleteMutation.isSuccess || workflowMutation.isSuccess} />
        </form>
      </Panel>
      <Panel icon={<ClipboardList size={20} />} title="Case Notes">
        {caseFileDetail.data ? (
          <div className="space-y-4">
            <RecordList
              items={caseFileDetail.data.notes ?? []}
              label="case notes"
              render={(note) => (
                <div className="space-y-2">
                  <div className="flex flex-wrap items-center justify-between gap-2">
                    <span>
                      <StatusBadge status={note.visibility} /> {note.user?.email ?? 'system'} - {formatDate(note.created_at)}
                    </span>
                    <span className="flex gap-2">
                      <SmallButton onClick={() => setEditingCaseNote(note)}>Edit</SmallButton>
                      <SmallButton danger onClick={() => selectedCaseFileId && window.confirm('Delete this note?') && deleteNoteMutation.mutate({ caseFileId: selectedCaseFileId, noteId: note.id })}>
                        Delete
                      </SmallButton>
                    </span>
                  </div>
                  <p className="text-[#52645e]">{note.note}</p>
                </div>
              )}
            />
            <form className="space-y-4" key={editingCaseNote?.id ?? `note-${selectedCaseFileId}`} onSubmit={handleCaseNoteSubmit}>
              <SelectField defaultValue={editingCaseNote?.visibility ?? 'internal'} label="Visibility" name="note_visibility" options={['internal', 'private', 'public']} />
              <TextAreaField defaultValue={editingCaseNote?.note ?? ''} label="Note" name="case_note" required />
              <FormFooter isPending={createNoteMutation.isPending || updateNoteMutation.isPending} onCancel={editingCaseNote ? () => setEditingCaseNote(null) : undefined} submitLabel={editingCaseNote ? 'Save note' : 'Add note'} />
              <MutationState isError={createNoteMutation.isError || updateNoteMutation.isError || deleteNoteMutation.isError} isSuccess={createNoteMutation.isSuccess || updateNoteMutation.isSuccess || deleteNoteMutation.isSuccess} />
            </form>
          </div>
        ) : (
          <EmptyState title="Select a case file" />
        )}
      </Panel>
      <Panel icon={<FileText size={20} />} title="Case Documents">
        {caseFileDetail.data ? (
          <div className="space-y-4">
            <RecordList
              items={caseFileDetail.data.documents ?? []}
              label="case documents"
              render={(caseDocument) => (
                <div className="flex flex-wrap items-center justify-between gap-2">
                  <span>
                    <StatusBadge status={caseDocument.status} /> {caseDocument.document_type} - {caseDocument.original_filename} - {formatFileSize(caseDocument.size)}
                  </span>
                  <span className="flex flex-wrap gap-2">
                    <SmallButton onClick={() => selectedCaseFileId && downloadDocumentMutation.mutate({ caseDocument, caseFileId: selectedCaseFileId })}>Download</SmallButton>
                    <SmallButton danger onClick={() => selectedCaseFileId && window.confirm(`Delete ${caseDocument.original_filename}?`) && deleteDocumentMutation.mutate({ caseFileId: selectedCaseFileId, documentId: caseDocument.id })}>
                      Delete
                    </SmallButton>
                  </span>
                </div>
              )}
            />
            <form className="space-y-4" onSubmit={handleDocumentUpload}>
              <FormGrid>
                <SelectField defaultValue="assessment" label="Document type" name="document_type" options={['identity', 'proof_of_address', 'medical_report', 'income_proof', 'assessment', 'consent', 'other']} />
                <label className="block text-sm">
                  <span className="mb-1 block font-medium text-[#29483d]">File</span>
                  <input className="w-full rounded-md border border-[#c8d4cf] bg-white px-3 py-2 text-sm text-[#10201a]" name="file" required type="file" />
                </label>
              </FormGrid>
              <FormFooter isPending={uploadDocumentMutation.isPending} submitLabel="Upload document" />
              <MutationState isError={uploadDocumentMutation.isError || deleteDocumentMutation.isError || downloadDocumentMutation.isError} isSuccess={uploadDocumentMutation.isSuccess || deleteDocumentMutation.isSuccess} />
            </form>
          </div>
        ) : (
          <EmptyState title="Select a case file" />
        )}
      </Panel>
    </ModulePage>
  )
}

function DonorsPage() {
  const donors = useQuery({ queryKey: ['donors'], queryFn: getDonors })

  return (
    <ModulePage description="Manage donor records and donation history." title="Donors" planned={['Add create/edit/detail pages.', 'Add donor donation history view.']}>
      <Panel icon={<Users size={20} />} title="Donors">
        <RecordList isError={donors.isError} isLoading={donors.isPending} items={donors.data} label="donors" render={(donor) => `${donor.name} - ${donor.donor_type} - ${donor.status} - ${donor.donations_count ?? 0} donations`} />
      </Panel>
    </ModulePage>
  )
}

function CampaignsPage() {
  const campaigns = useQuery({ queryKey: ['campaigns'], queryFn: getCampaigns })

  return (
    <ModulePage description="Manage fundraising campaigns and visibility." title="Campaigns" planned={['Add create/edit/detail pages.', 'Add activate, pause, complete, and cancel actions.']}>
      <Panel icon={<Landmark size={20} />} title="Campaigns">
        <RecordList
          isError={campaigns.isError}
          isLoading={campaigns.isPending}
          items={campaigns.data}
          label="campaigns"
          render={(campaign) => (
            <>
              <StatusBadge status={campaign.status} /> {campaign.title} - {campaign.collected_amount}/{campaign.goal_amount} {campaign.currency} - {campaign.visibility}
            </>
          )}
        />
      </Panel>
    </ModulePage>
  )
}

function DonationsPage() {
  const donations = useQuery({ queryKey: ['donations'], queryFn: getDonations })

  return (
    <ModulePage description="Record donations, manage allocations, confirm payments, and generate receipts." title="Donations" planned={['Add donation form with allocation builder.', 'Add manual confirmation and receipt actions.']}>
      <Panel icon={<DollarSign size={20} />} title="Donations">
        <RecordList
          isError={donations.isError}
          isLoading={donations.isPending}
          items={donations.data}
          label="donations"
          render={(donation) => (
            <>
              <StatusBadge status={donation.payment_status} /> {donation.donation_number} - {donation.donor?.name ?? 'Anonymous'} - {donation.amount} {donation.currency} - {donation.donation_status}
            </>
          )}
        />
      </Panel>
    </ModulePage>
  )
}

function PaymentsPage() {
  return (
    <PlaceholderModulePage
      icon={<Receipt size={20} />}
      planned={['Add payment transaction list/detail.', 'Add receipt view and generation flow from donation detail pages.']}
      title="Payments & Receipts"
      description="Payment transactions and receipts are API-backed, but dedicated browser screens are queued for the finance UI slice."
    />
  )
}

function WarehousesPage() {
  const warehouses = useQuery({ queryKey: ['warehouses'], queryFn: getWarehouses })

  return (
    <ModulePage description="Manage warehouses, branch assignment, status, and managers." title="Warehouses" planned={['Add create/edit/detail pages.', 'Add status and manager controls.']}>
      <Panel icon={<Warehouse size={20} />} title="Warehouses">
        <RecordList isError={warehouses.isError} isLoading={warehouses.isPending} items={warehouses.data} label="warehouses" render={(warehouse) => `${warehouse.code} - ${warehouse.name} - ${warehouse.status} - ${warehouse.stock_lots_count ?? 0} lots`} />
      </Panel>
    </ModulePage>
  )
}

function InventoryItemsPage() {
  const inventoryItems = useQuery({ queryKey: ['inventory-items'], queryFn: getInventoryItems })

  return (
    <ModulePage description="Manage catalog items, units, categories, minimum stock, and expiry tracking." title="Inventory Items" planned={['Add create/edit/detail pages.', 'Add item stock and movement drill-downs.']}>
      <Panel icon={<Package size={20} />} title="Inventory Items">
        <RecordList isError={inventoryItems.isError} isLoading={inventoryItems.isPending} items={inventoryItems.data} label="inventory items" render={(item) => `${item.sku} - ${item.name} - ${item.category} - min ${item.minimum_stock_level} ${item.unit}`} />
      </Panel>
    </ModulePage>
  )
}

function StockSummaryPage() {
  const stockSummary = useQuery({ queryKey: ['stock-summary'], queryFn: getStockSummary })

  return (
    <ModulePage description="Review available and reserved quantities by item." title="Stock Summary" planned={['Add filters by warehouse/category.', 'Link rows to item and movement detail pages.']}>
      <Panel icon={<Boxes size={20} />} title="Stock Summary">
        <RecordList isError={stockSummary.isError} isLoading={stockSummary.isPending} items={stockSummary.data} label="stock summary rows" render={(row) => `${row.sku} - ${row.available_quantity} ${row.unit} available - ${row.reserved_quantity} reserved - ${row.low_stock ? 'low stock' : 'healthy'}`} />
      </Panel>
    </ModulePage>
  )
}

function StockLotsPage() {
  const stockLots = useQuery({ queryKey: ['stock-lots'], queryFn: getStockLots })

  return (
    <ModulePage description="Review stock lots, remaining quantities, reservations, sources, and expiry dates." title="Stock Lots" planned={['Add receive stock form route.', 'Add lot detail view.']}>
      <Panel icon={<Boxes size={20} />} title="Stock Lots">
        <RecordList
          isError={stockLots.isError}
          isLoading={stockLots.isPending}
          items={stockLots.data}
          label="stock lots"
          render={(lot) =>
            `${lot.inventory_item?.sku ?? 'Unknown item'} - ${lot.remaining_quantity} ${lot.inventory_item?.unit ?? ''} remaining - ${lot.warehouse?.code ?? 'No warehouse'} - ${lot.source_type} #${lot.source_id ?? '-'} - expires ${lot.expiry_date ?? '-'}`
          }
        />
      </Panel>
    </ModulePage>
  )
}

function StockMovementsPage() {
  const stockMovements = useQuery({ queryKey: ['stock-movements'], queryFn: getStockMovements })

  return (
    <ModulePage description="Review all stock-in, adjustment, reservation, release, and distribution movements." title="Stock Movements" planned={['Add receive stock and adjust stock forms.', 'Add movement filters.']}>
      <Panel icon={<ListChecks size={20} />} title="Stock Movements">
        <RecordList
          isError={stockMovements.isError}
          isLoading={stockMovements.isPending}
          items={stockMovements.data}
          label="stock movements"
          render={(movement) => `${movement.movement_type} - ${movement.inventory_item?.sku ?? 'Unknown item'} - ${movement.quantity} ${movement.inventory_item?.unit ?? ''} - ${movement.warehouse?.code ?? 'No warehouse'} - ${formatDate(movement.created_at)}`}
        />
      </Panel>
    </ModulePage>
  )
}

function LowStockPage() {
  const lowStock = useQuery({ queryKey: ['stock-low-stock'], queryFn: getLowStock })

  return (
    <ModulePage description="Review items under configured minimum stock levels." title="Low Stock" planned={['Add links to receive stock and item detail pages.']}>
      <Panel icon={<ClipboardCheck size={20} />} title="Low Stock">
        <RecordList isError={lowStock.isError} isLoading={lowStock.isPending} items={lowStock.data} label="low stock rows" render={(row) => `${row.sku} - ${row.available_quantity}/${row.minimum_stock_level} ${row.unit}`} />
      </Panel>
    </ModulePage>
  )
}

function ExpiringStockPage() {
  const expiringStock = useQuery({ queryKey: ['stock-expiring'], queryFn: getExpiringStock })

  return (
    <ModulePage description="Review stock lots expiring soon." title="Expiring Stock" planned={['Add expiry window filter.', 'Add links to affected lots and warehouses.']}>
      <Panel icon={<CalendarClock size={20} />} title="Expiring Stock">
        <RecordList
          isError={expiringStock.isError}
          isLoading={expiringStock.isPending}
          items={expiringStock.data}
          label="expiring stock lots"
          render={(lot) => `${lot.inventory_item?.sku ?? 'Unknown item'} - ${lot.remaining_quantity} ${lot.inventory_item?.unit ?? ''} - ${lot.warehouse?.code ?? 'No warehouse'} - expires ${lot.expiry_date ?? '-'}`}
        />
      </Panel>
    </ModulePage>
  )
}

function AidBatchesPage() {
  const aidBatches = useQuery({ queryKey: ['aid-batches'], queryFn: getAidBatches })

  return (
    <ModulePage description="Plan aid batches, add distributions, check stock, approve, and complete deliveries." title="Aid Batches" planned={['Add create/edit/detail pages.', 'Add distributions, stock-check, submit, approve, cancel, and complete controls.']}>
      <Panel icon={<Truck size={20} />} title="Aid Batches">
        <RecordList
          isError={aidBatches.isError}
          isLoading={aidBatches.isPending}
          items={aidBatches.data}
          label="aid batches"
          render={(batch) => (
            <>
              <StatusBadge status={batch.status} /> {batch.batch_number} - {batch.title} - {batch.distributions_count ?? 0} distributions - {batch.warehouse?.code ?? 'No warehouse'}
            </>
          )}
        />
      </Panel>
    </ModulePage>
  )
}

function AidDistributionsPage() {
  return (
    <PlaceholderModulePage
      icon={<ClipboardCheck size={20} />}
      planned={['Add distribution list/detail views.', 'Add delivery, failure, reschedule, and proof upload actions.']}
      title="Aid Distributions"
      description="Distribution records are currently managed under batches in the API. Dedicated distribution screens are part of Slice 11.6."
    />
  )
}

function ReportsPage() {
  const dashboardReport = useQuery({ queryKey: ['reports-dashboard'], queryFn: getDashboardReport })

  return (
    <ModulePage description="Run operational reports and export CSV files." title="Reports & Exports" planned={['Add report-specific filters.', 'Add export creation/list/download controls.']}>
      <Panel icon={<FileBarChart size={20} />} title="Report Entry Points">
        <RecordList
          items={[
            'Donation report',
            'Campaign report',
            'Beneficiary report',
            'Case file report',
            'Distribution report',
            'Inventory report',
            'Audit log report',
            'CSV exports',
          ]}
          label="report entry points"
          render={(item) => item}
        />
      </Panel>
      <Panel icon={<ListChecks size={20} />} title="Current Dashboard Snapshot">
        {dashboardReport.data ? (
          <KeyValueRows
            rows={[
              ['Donations this month', `${dashboardReport.data.metrics.total_donations_this_month} EGP`],
              ['Active campaigns', String(dashboardReport.data.metrics.active_campaigns)],
              ['Pending cases', String(dashboardReport.data.metrics.pending_cases)],
              ['Low stock items', String(dashboardReport.data.metrics.low_stock_items)],
            ]}
          />
        ) : (
          <LoadingOrEmpty isError={dashboardReport.isError} isLoading={dashboardReport.isPending} label="Loading report snapshot" />
        )}
      </Panel>
    </ModulePage>
  )
}

function PublicPortalSettingsPage() {
  return (
    <PlaceholderModulePage
      icon={<Settings size={20} />}
      planned={['Add settings form for portal visibility, campaign progress, contact info, public reports, and donation intent toggle.', 'Add link to /public preview.']}
      title="Public Portal Settings"
      description="Public portal APIs exist, and this route is reserved for the authenticated settings form in Slice 11.7."
    />
  )
}

function NotificationsPage() {
  const notifications = useQuery({ queryKey: ['notifications'], queryFn: getNotifications })
  const preferences = useQuery({ queryKey: ['notification-preferences'], queryFn: getNotificationPreferences })

  return (
    <ModulePage description="Review operational notifications and channel preferences." title="Notifications" planned={['Add full inbox filters.', 'Add preferences update form.']}>
      <Panel icon={<Bell size={20} />} title="Recent Notifications">
        <RecordList
          isError={notifications.isError}
          isLoading={notifications.isPending}
          items={notifications.data}
          label="notifications"
          render={(notification) => (
            <>
              <StatusBadge status={notification.severity} /> {notification.title} - {notification.category} - {notification.read_at ? 'read' : 'unread'}
            </>
          )}
        />
      </Panel>
      <Panel icon={<Settings size={20} />} title="Notification Preferences">
        <RecordList
          isError={preferences.isError}
          isLoading={preferences.isPending}
          items={preferences.data}
          label="notification preferences"
          render={(preference) => `${preference.category} - database ${preference.database_enabled ? 'on' : 'off'} - email ${preference.email_enabled ? 'on' : 'off'}`}
        />
      </Panel>
    </ModulePage>
  )
}

function SystemPage() {
  const queueHealth = useQuery({ queryKey: ['system-queue-health'], queryFn: getQueueHealth })
  const scheduledJobs = useQuery({ queryKey: ['system-scheduled-jobs'], queryFn: getScheduledJobs })

  return (
    <ModulePage description="Inspect queue and scheduler visibility for operational automation." title="System" planned={['Add richer worker status and failed-job operations after backend support is expanded.']}>
      <Panel icon={<Settings size={20} />} title="Queue Health">
        {queueHealth.data ? (
          <KeyValueRows
            rows={[
              ['Connection', queueHealth.data.connection],
              ['Pending jobs', String(queueHealth.data.pending_jobs)],
              ['Failed jobs', String(queueHealth.data.failed_jobs)],
            ]}
          />
        ) : (
          <LoadingOrEmpty isError={queueHealth.isError} isLoading={queueHealth.isPending} label="Loading queue health" />
        )}
      </Panel>
      <Panel icon={<CalendarClock size={20} />} title="Scheduled Jobs">
        <RecordList isError={scheduledJobs.isError} isLoading={scheduledJobs.isPending} items={scheduledJobs.data} label="scheduled jobs" render={(job) => `${job.name} - ${job.command} - ${job.frequency}`} />
      </Panel>
    </ModulePage>
  )
}

function PlaceholderModulePage({ description, icon, planned, title }: { description: string; icon: ReactNode; planned: string[]; title: string }) {
  return (
    <ModulePage description={description} planned={planned} title={title}>
      <Panel icon={icon} title={`${title} Controls`}>
        <EmptyState title="Controls queued for Phase 11" />
      </Panel>
    </ModulePage>
  )
}

function ModulePage({ children, description, planned, title }: { children: ReactNode; description: string; planned?: string[]; title: string }) {
  return (
    <div className="mx-auto max-w-7xl">
      <header className="mb-5">
        <p className="text-sm font-medium uppercase tracking-wide text-[#4b635b]">Admin control system</p>
        <h2 className="mt-1 text-2xl font-semibold text-[#10201a]">{title}</h2>
        <p className="mt-2 max-w-3xl text-sm leading-6 text-[#52645e]">{description}</p>
      </header>

      {planned && planned.length > 0 ? <PhaseNotice items={planned} /> : null}

      <div className="grid gap-4 lg:grid-cols-2">{children}</div>
    </div>
  )
}

function PhaseNotice({ items }: { items: string[] }) {
  return (
    <div className="mb-5 rounded-md border border-[#d8e5df] bg-[#f0f7f4] p-4 text-sm text-[#29483d]">
      <p className="font-semibold text-[#10201a]">Next controls for this module</p>
      <ul className="mt-2 list-inside list-disc space-y-1">
        {items.map((item) => (
          <li key={item}>{item}</li>
        ))}
      </ul>
    </div>
  )
}

function Panel({ icon, title, children }: { icon: ReactNode; title: string; children: ReactNode }) {
  return (
    <section className="rounded-md border border-[#d9e1de] bg-white p-5">
      <div className="mb-4 flex items-center gap-2 text-[#10201a]">
        <span className="text-[#236b55]">{icon}</span>
        <h3 className="text-lg font-semibold">{title}</h3>
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
          <dd className="text-right font-medium text-[#10201a]">{value ?? '-'}</dd>
        </div>
      ))}
    </dl>
  )
}

function RecordList<T>({
  isError,
  isLoading,
  items,
  label,
  render,
}: {
  isError?: boolean
  isLoading?: boolean
  items: T[] | undefined
  label: string
  render: (item: T) => ReactNode
}) {
  if (isLoading) {
    return <LoadingState label={`Loading ${label}`} />
  }

  if (isError) {
    return <UnauthorizedState title={`Cannot load ${label}`} />
  }

  if (!items || items.length === 0) {
    return <EmptyState title={`No ${label} found`} />
  }

  return (
    <ul className="divide-y divide-[#edf1ef] text-sm">
      {items.map((item, index) => (
        <li className="py-2 leading-6 text-[#10201a]" key={index}>
          {render(item)}
        </li>
      ))}
    </ul>
  )
}

function LoadingOrEmpty({ isError, isLoading, label }: { isError?: boolean; isLoading: boolean; label: string }) {
  if (isLoading) {
    return <LoadingState label={label} />
  }

  if (isError) {
    return <UnauthorizedState title="Unable to load data" />
  }

  return <EmptyState title="No data available" />
}

function FormGrid({ children }: { children: ReactNode }) {
  return <div className="grid gap-4 md:grid-cols-2">{children}</div>
}

function TextField({
  label,
  name,
  ...props
}: {
  label: string
  name: string
} & Omit<InputHTMLAttributes<HTMLInputElement>, 'className' | 'name'>) {
  return (
    <label className="block text-sm">
      <span className="mb-1 block font-medium text-[#29483d]">{label}</span>
      <input
        className="w-full rounded-md border border-[#c8d4cf] bg-white px-3 py-2 text-sm text-[#10201a] outline-none transition focus:border-[#236b55] focus:ring-2 focus:ring-[#236b55]/15 disabled:bg-[#f3f6f4]"
        name={name}
        {...props}
      />
    </label>
  )
}

function SelectField({
  defaultValue,
  label,
  name,
  optionLabels,
  options,
  ...props
}: {
  defaultValue?: string
  label: string
  name: string
  optionLabels?: Record<string, string>
  options: string[]
} & Omit<SelectHTMLAttributes<HTMLSelectElement>, 'className' | 'defaultValue' | 'name'>) {
  return (
    <label className="block text-sm">
      <span className="mb-1 block font-medium text-[#29483d]">{label}</span>
      <select
        className="w-full rounded-md border border-[#c8d4cf] bg-white px-3 py-2 text-sm text-[#10201a] outline-none transition focus:border-[#236b55] focus:ring-2 focus:ring-[#236b55]/15"
        defaultValue={defaultValue}
        name={name}
        {...props}
      >
        {options.map((option) => (
          <option key={option} value={option}>
            {optionLabels?.[option] ?? option}
          </option>
        ))}
      </select>
    </label>
  )
}

function TextAreaField({
  label,
  name,
  ...props
}: {
  label: string
  name: string
} & Omit<TextareaHTMLAttributes<HTMLTextAreaElement>, 'className' | 'name'>) {
  return (
    <label className="block text-sm">
      <span className="mb-1 block font-medium text-[#29483d]">{label}</span>
      <textarea
        className="min-h-24 w-full rounded-md border border-[#c8d4cf] bg-white px-3 py-2 text-sm text-[#10201a] outline-none transition focus:border-[#236b55] focus:ring-2 focus:ring-[#236b55]/15"
        name={name}
        {...props}
      />
    </label>
  )
}

function CheckboxGroup({
  defaultValues = [],
  isLoading,
  label,
  name,
  options,
}: {
  defaultValues?: string[]
  isLoading?: boolean
  label: string
  name: string
  options: string[]
}) {
  if (isLoading) {
    return <LoadingState label={`Loading ${label.toLowerCase()}`} />
  }

  if (options.length === 0) {
    return <EmptyState title={`No ${label.toLowerCase()} found`} />
  }

  return (
    <fieldset className="text-sm">
      <legend className="mb-2 block font-medium text-[#29483d]">{label}</legend>
      <div className="grid max-h-80 gap-2 overflow-y-auto rounded-md border border-[#d9e1de] bg-[#fbfcfb] p-3 md:grid-cols-2">
        {options.map((option) => (
          <label className="flex items-center gap-2 rounded-md bg-white px-3 py-2 text-[#10201a]" key={option}>
            <input className="h-4 w-4 accent-[#236b55]" defaultChecked={defaultValues.includes(option)} name={name} type="checkbox" value={option} />
            <span className="min-w-0 break-words">{option}</span>
          </label>
        ))}
      </div>
    </fieldset>
  )
}

function FormFooter({
  isPending,
  onCancel,
  submitLabel,
}: {
  isPending: boolean
  onCancel?: () => void
  submitLabel: string
}) {
  return (
    <div className="flex flex-wrap items-center gap-2">
      <button className="rounded-md bg-[#236b55] px-4 py-2 text-sm font-medium text-white disabled:cursor-not-allowed disabled:bg-[#8ca79d]" disabled={isPending} type="submit">
        {isPending ? 'Saving...' : submitLabel}
      </button>
      {onCancel ? (
        <button className="rounded-md border border-[#c8d4cf] bg-white px-4 py-2 text-sm font-medium text-[#29483d]" onClick={onCancel} type="button">
          Cancel
        </button>
      ) : null}
    </div>
  )
}

function MutationState({ isError, isSuccess }: { isError: boolean; isSuccess: boolean }) {
  if (isError) {
    return <p className="rounded-md border border-[#e8b8b0] bg-[#fff1ef] px-3 py-2 text-sm text-[#a44a3f]">Action failed. Check the form values and your permissions.</p>
  }

  if (isSuccess) {
    return <p className="rounded-md border border-[#b7d8c8] bg-[#edf8f1] px-3 py-2 text-sm text-[#236b55]">Saved successfully.</p>
  }

  return null
}

function SmallButton({ children, danger, onClick }: { children: ReactNode; danger?: boolean; onClick: () => void }) {
  return (
    <button className={`rounded-md border px-3 py-1.5 text-xs font-medium ${danger ? 'border-[#e8b8b0] bg-[#fff1ef] text-[#a44a3f]' : 'border-[#c8d4cf] bg-white text-[#29483d]'}`} onClick={onClick} type="button">
      {children}
    </button>
  )
}

function JsonBlock({ label, value }: { label: string; value: Record<string, unknown> | null }) {
  return (
    <div>
      <h4 className="mb-2 font-semibold text-[#10201a]">{label}</h4>
      <pre className="max-h-72 overflow-auto rounded-md border border-[#d9e1de] bg-[#fbfcfb] p-3 text-xs leading-5 text-[#29483d]">{JSON.stringify(value ?? {}, null, 2)}</pre>
    </div>
  )
}

function StatusBadge({ status }: { status: string }) {
  return <span className={`mr-2 rounded-md border px-2 py-0.5 text-xs font-medium ${statusClass(status)}`}>{status}</span>
}

function formString(form: FormData, key: string) {
  const value = form.get(key)

  return typeof value === 'string' ? value.trim() : ''
}

function formNullable(form: FormData, key: string) {
  const value = formString(form, key)

  return value === '' ? null : value
}

function formNullableNumber(form: FormData, key: string) {
  const value = formString(form, key)
  const number = Number(value)

  return value === '' || Number.isNaN(number) ? null : number
}

function NotificationDropdown({
  isLoading,
  notifications,
  onMarkAllRead,
  onMarkRead,
}: {
  isLoading: boolean
  notifications: OperationalNotification[] | undefined
  onMarkAllRead: () => void
  onMarkRead: (id: number) => void
}) {
  return (
    <div className="absolute right-0 top-12 z-10 w-[min(360px,calc(100vw-2rem))] rounded-md border border-[#d9e1de] bg-white p-4 shadow-lg">
      <div className="mb-3 flex items-center justify-between gap-3">
        <h2 className="text-sm font-semibold text-[#10201a]">Notifications</h2>
        <button className="inline-flex items-center gap-1 text-xs font-medium text-[#236b55]" onClick={onMarkAllRead} type="button">
          <Check size={14} aria-hidden="true" />
          Mark all
        </button>
      </div>
      {isLoading ? <LoadingState label="Loading notifications" /> : <NotificationItems notifications={notifications ?? []} onMarkRead={onMarkRead} />}
    </div>
  )
}

function NotificationItems({ notifications, onMarkRead }: { notifications: OperationalNotification[]; onMarkRead: (id: number) => void }) {
  if (notifications.length === 0) {
    return <EmptyState title="No notifications" />
  }

  return (
    <ul className="max-h-96 divide-y divide-[#edf1ef] overflow-y-auto text-sm">
      {notifications.map((notification) => (
        <li className="py-3" key={notification.id}>
          <div className="mb-1 flex items-start justify-between gap-3">
            <div>
              <span className={`rounded-md border px-2 py-0.5 text-xs font-medium ${statusClass(notification.severity)}`}>{notification.severity}</span>
              <h3 className="mt-2 font-semibold text-[#10201a]">{notification.title}</h3>
            </div>
            {!notification.read_at ? (
              <button className="rounded-md border border-[#c8d4cf] p-1 text-[#236b55]" onClick={() => onMarkRead(notification.id)} title="Mark read" type="button">
                <Check size={15} aria-hidden="true" />
              </button>
            ) : null}
          </div>
          {notification.body ? <p className="leading-5 text-[#52645e]">{notification.body}</p> : null}
          <p className="mt-2 text-xs text-[#7b8b85]">{formatDate(notification.created_at)}</p>
        </li>
      ))}
    </ul>
  )
}

function canSeeNavItem(user: CurrentUser, item: NavItem) {
  if (!item.permissions || item.permissions.length === 0) {
    return true
  }

  if (!user.permissions || user.permissions.length === 0) {
    return true
  }

  return item.permissions.some((permission) => user.permissions?.includes(permission))
}

function statusClass(status: string) {
  const normalized = status.toLowerCase()

  if (['approved', 'active', 'paid', 'confirmed', 'completed', 'delivered', 'success'].includes(normalized)) {
    return 'border-[#b7d8c8] bg-[#edf8f1] text-[#236b55]'
  }

  if (['pending', 'pending_review', 'under_review', 'pending_approval', 'draft', 'warning'].includes(normalized)) {
    return 'border-[#e8d6ad] bg-[#fff8e8] text-[#6f541e]'
  }

  if (['rejected', 'cancelled', 'failed', 'suspended', 'critical'].includes(normalized)) {
    return 'border-[#e8b8b0] bg-[#fff1ef] text-[#a44a3f]'
  }

  return 'border-[#bbd5e7] bg-[#eef7fc] text-[#245a7a]'
}

function formatDate(value: string) {
  return new Date(value).toLocaleString()
}

function formatFileSize(bytes: number) {
  if (bytes < 1024) {
    return `${bytes} B`
  }

  if (bytes < 1024 * 1024) {
    return `${(bytes / 1024).toFixed(1)} KB`
  }

  return `${(bytes / 1024 / 1024).toFixed(1)} MB`
}

const router = createBrowserRouter([
  {
    path: '/public',
    element: <PublicPortalPage />,
  },
  {
    path: '/public/campaigns/:slug',
    element: <PublicCampaignDetailsPage />,
  },
  {
    path: '/',
    element: <AdminRouteGate />,
    children: [
      { index: true, element: <Navigate replace to="/dashboard" /> },
      { path: 'dashboard', element: <DashboardPage /> },
      { path: 'organization', element: <OrganizationPage /> },
      { path: 'branches', element: <BranchesPage /> },
      { path: 'users', element: <UsersPage /> },
      { path: 'roles', element: <RolesPage /> },
      { path: 'audit-logs', element: <AuditLogsPage /> },
      { path: 'beneficiaries', element: <BeneficiariesPage /> },
      { path: 'case-files', element: <CaseFilesPage /> },
      { path: 'donors', element: <DonorsPage /> },
      { path: 'campaigns', element: <CampaignsPage /> },
      { path: 'donations', element: <DonationsPage /> },
      { path: 'finance/payments', element: <PaymentsPage /> },
      { path: 'warehouses', element: <WarehousesPage /> },
      { path: 'inventory-items', element: <InventoryItemsPage /> },
      { path: 'stock/summary', element: <StockSummaryPage /> },
      { path: 'stock/lots', element: <StockLotsPage /> },
      { path: 'stock/movements', element: <StockMovementsPage /> },
      { path: 'stock/low-stock', element: <LowStockPage /> },
      { path: 'stock/expiring', element: <ExpiringStockPage /> },
      { path: 'aid-batches', element: <AidBatchesPage /> },
      { path: 'aid-distributions', element: <AidDistributionsPage /> },
      { path: 'reports', element: <ReportsPage /> },
      { path: 'settings/public-portal', element: <PublicPortalSettingsPage /> },
      { path: 'notifications', element: <NotificationsPage /> },
      { path: 'system', element: <SystemPage /> },
      { path: '*', element: <Navigate replace to="/dashboard" /> },
    ],
  },
])

export function AppRouter() {
  return <RouterProvider router={router} />
}
