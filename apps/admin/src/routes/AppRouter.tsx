import { useEffect, useMemo, useState } from 'react'
import type { ComponentType, FormEvent, InputHTMLAttributes, ReactNode, SelectHTMLAttributes, TextareaHTMLAttributes } from 'react'
import { createBrowserRouter, Navigate, NavLink, Outlet, RouterProvider, useLocation, useOutletContext, useSearchParams } from 'react-router'
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
import { canAccessAny } from '../app/permissions'
import { EmptyState } from '../components/ui/EmptyState'
import { LoadingState } from '../components/ui/LoadingState'
import { UnauthorizedState } from '../components/ui/UnauthorizedState'
import {
  createAidBatch,
  createAidDistribution,
  createDistributionItem,
  deleteAidBatch,
  deleteAidDistribution,
  deleteDistributionItem,
  getAidBatch,
  getAidBatchDistributionsPage,
  getAidBatches,
  getAidBatchesPage,
  getAidBatchStockCheck,
  getAidDistribution,
  getDistributionItemsPage,
  getEligibleBeneficiariesPage,
  markDistributionFailed,
  rescheduleDistribution,
  runAidBatchAction,
  submitDistributionProof,
  updateAidBatch,
  type AidBatch,
  type AidBatchInput,
  type AidDistribution,
} from '../services/api/aid'
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
  getBeneficiariesPage,
  getBeneficiary,
  getBeneficiaryFamilyMembersPage,
  getCaseFile,
  getCaseDocumentsPage,
  getCaseFiles,
  getCaseFilesPage,
  getCaseNotesPage,
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
import {
  activateCampaign,
  cancelCampaign,
  cancelDonation,
  completeCampaign,
  confirmDonation,
  createCampaign,
  createDonation,
  createDonationAllocation,
  createDonor,
  deleteCampaign,
  deleteDonationAllocation,
  deleteDonor,
  generateDonationReceipt,
  getCampaign,
  getCampaigns,
  getCampaignsPage,
  getDonation,
  getDonationAllocationsPage,
  getDonationPaymentTransactionsPage,
  getDonationsPage,
  getDonor,
  getDonorDonationsPage,
  getDonors,
  getDonorsPage,
  getPaymentTransaction,
  pauseCampaign,
  updateCampaign,
  updateDonation,
  updateDonationAllocation,
  updateDonor,
  type Campaign,
  type CampaignInput,
  type ConfirmDonationInput,
  type Donation,
  type DonationAllocation,
  type DonationAllocationInput,
  type DonationInput,
  type Donor,
  type DonorInput,
  type PaymentTransaction,
} from '../services/api/finance'
import {
  createBranch,
  createRole,
  createUser,
  deleteBranch,
  deleteRole,
  disableUser,
  enableUser,
  getAuditLogsPage,
  getBranches,
  getBranchesPage,
  getOrganization,
  getPermissions,
  getRoles,
  getRolesPage,
  getUsers,
  getUsersPage,
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
  adjustStock,
  createInventoryItem,
  createWarehouse,
  deleteInventoryItem,
  deleteWarehouse,
  getExpiringStockPage,
  getInventoryItems,
  getInventoryItemsPage,
  getLowStockPage,
  getStockLots,
  getStockLotsPage,
  getStockMovementsPage,
  getStockSummaryPage,
  getWarehouses,
  getWarehousesPage,
  receiveStock,
  updateInventoryItem,
  updateWarehouse,
  type InventoryItem,
  type InventoryItemInput,
  type Warehouse as WarehouseRecord,
  type WarehouseInput,
} from '../services/api/inventory'
import {
  getNotificationPreferences,
  getNotifications,
  getNotificationsPage,
  getQueueHealth,
  getScheduledJobs,
  getUnreadNotificationCount,
  markAllNotificationsRead,
  markNotificationRead,
  updateNotificationPreferences,
  type OperationalNotification,
} from '../services/api/notifications'
import { createExport, downloadExport, getDashboardReport, getExportsPage, getReport, reportTypes, type ReportType } from '../services/api/reports'
import { getPublicPortalSettings, updatePublicPortalSettings } from '../services/api/publicPortal'
import { readPagination, type PaginationMeta, type PaginationParams } from '../services/api/pagination'
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
    <main className="h-svh overflow-hidden bg-[#f6f8f7] text-[#172026]">
      <div className="flex h-full min-h-0">
        <aside className="hidden h-full w-72 shrink-0 overflow-hidden border-r border-[#d9e1de] bg-white lg:block">
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

        <section className="h-full min-w-0 flex-1 overflow-y-auto overscroll-contain">
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
            <Outlet context={{ me }} />
          </div>
        </section>
      </div>
    </main>
  )
}

function SidebarNav({ groups, onNavigate }: { groups: NavGroup[]; onNavigate: () => void }) {
  return (
    <nav className="flex h-full min-h-0 flex-col overflow-y-auto overscroll-contain px-4 py-5">
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
  const pagination = useListPagination()
  const [editingBranch, setEditingBranch] = useState<Branch | null>(null)
  const branches = useQuery({ queryKey: ['branches', pagination.params], queryFn: () => getBranchesPage(pagination.params) })
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
          items={branches.data?.data}
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
        <PaginationControls meta={branches.data?.meta} pagination={pagination} />
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
  const pagination = useListPagination()
  const [editingUser, setEditingUser] = useState<User | null>(null)
  const users = useQuery({ queryKey: ['users', pagination.params], queryFn: () => getUsersPage(pagination.params) })
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
          items={users.data?.data}
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
        <PaginationControls meta={users.data?.meta} pagination={pagination} />
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
  const pagination = useListPagination()
  const [editingRole, setEditingRole] = useState<Role | null>(null)
  const roles = useQuery({ queryKey: ['roles', pagination.params], queryFn: () => getRolesPage(pagination.params) })
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
          items={roles.data?.data}
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
        <PaginationControls meta={roles.data?.meta} pagination={pagination} />
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
  const pagination = useListPagination()
  const [selectedLog, setSelectedLog] = useState<AuditLog | null>(null)
  const auditLogs = useQuery({ queryKey: ['audit-logs', pagination.params], queryFn: () => getAuditLogsPage(pagination.params) })

  return (
    <ModulePage description="Inspect audited state changes and sensitive operations." title="Audit Logs" planned={['Add filters by date/action/user/entity.']}>
      <Panel icon={<ClipboardList size={20} />} title="Recent Audit Logs">
        <RecordList
          isError={auditLogs.isError}
          isLoading={auditLogs.isPending}
          items={auditLogs.data?.data}
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
        <PaginationControls meta={auditLogs.data?.meta} pagination={pagination} />
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
  const pagination = useListPagination()
  const familyPagination = useListPagination('family_')
  const [selectedBeneficiaryId, setSelectedBeneficiaryId] = useState<number | null>(null)
  const [editingBeneficiaryId, setEditingBeneficiaryId] = useState<number | null>(null)
  const [editingFamilyMember, setEditingFamilyMember] = useState<FamilyMember | null>(null)
  const beneficiaries = useQuery({ queryKey: ['beneficiaries', pagination.params], queryFn: () => getBeneficiariesPage(pagination.params) })
  const branches = useQuery({ queryKey: ['branches'], queryFn: getBranches })
  const beneficiaryDetail = useQuery({
    queryKey: ['beneficiary', selectedBeneficiaryId],
    queryFn: () => getBeneficiary(selectedBeneficiaryId as number),
    enabled: selectedBeneficiaryId !== null,
  })
  const familyMembers = useQuery({
    queryKey: ['beneficiary-family-members', selectedBeneficiaryId, familyPagination.params],
    queryFn: () => getBeneficiaryFamilyMembersPage(selectedBeneficiaryId as number, familyPagination.params),
    enabled: selectedBeneficiaryId !== null,
  })
  const editingBeneficiary = editingBeneficiaryId ? beneficiaryDetail.data ?? beneficiaries.data?.data.find((beneficiary) => beneficiary.id === editingBeneficiaryId) ?? null : null

  useEffect(() => {
    setEditingFamilyMember(null)
  }, [selectedBeneficiaryId])

  function refreshBeneficiaries(id?: number | null) {
    void queryClient.invalidateQueries({ queryKey: ['beneficiaries'] })

    if (id) {
      void queryClient.invalidateQueries({ queryKey: ['beneficiary', id] })
      void queryClient.invalidateQueries({ queryKey: ['beneficiary-family-members', id] })
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
          items={beneficiaries.data?.data}
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
        <PaginationControls meta={beneficiaries.data?.meta} pagination={pagination} />
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
              isError={familyMembers.isError}
              isLoading={familyMembers.isPending}
              items={familyMembers.data?.data}
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
            <PaginationControls meta={familyMembers.data?.meta} pagination={familyPagination} />
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
  const pagination = useListPagination()
  const notesPagination = useListPagination('notes_')
  const documentsPagination = useListPagination('documents_')
  const [selectedCaseFileId, setSelectedCaseFileId] = useState<number | null>(null)
  const [editingCaseFileId, setEditingCaseFileId] = useState<number | null>(null)
  const [editingCaseNote, setEditingCaseNote] = useState<CaseNote | null>(null)
  const caseFiles = useQuery({ queryKey: ['case-files', pagination.params], queryFn: () => getCaseFilesPage(pagination.params) })
  const beneficiaries = useQuery({ queryKey: ['beneficiaries'], queryFn: getBeneficiaries })
  const users = useQuery({ queryKey: ['users'], queryFn: getUsers })
  const caseFileDetail = useQuery({
    queryKey: ['case-file', selectedCaseFileId],
    queryFn: () => getCaseFile(selectedCaseFileId as number),
    enabled: selectedCaseFileId !== null,
  })
  const caseNotes = useQuery({ queryKey: ['case-notes', selectedCaseFileId, notesPagination.params], queryFn: () => getCaseNotesPage(selectedCaseFileId as number, notesPagination.params), enabled: selectedCaseFileId !== null })
  const caseDocuments = useQuery({ queryKey: ['case-documents', selectedCaseFileId, documentsPagination.params], queryFn: () => getCaseDocumentsPage(selectedCaseFileId as number, documentsPagination.params), enabled: selectedCaseFileId !== null })
  const editingCaseFile = editingCaseFileId ? caseFileDetail.data ?? caseFiles.data?.data.find((caseFile) => caseFile.id === editingCaseFileId) ?? null : null

  useEffect(() => {
    setEditingCaseNote(null)
  }, [selectedCaseFileId])

  function refreshCaseFiles(id?: number | null) {
    void queryClient.invalidateQueries({ queryKey: ['case-files'] })
    void queryClient.invalidateQueries({ queryKey: ['beneficiaries'] })

    if (id) {
      void queryClient.invalidateQueries({ queryKey: ['case-file', id] })
      void queryClient.invalidateQueries({ queryKey: ['case-notes', id] })
      void queryClient.invalidateQueries({ queryKey: ['case-documents', id] })
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
          items={caseFiles.data?.data}
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
        <PaginationControls meta={caseFiles.data?.meta} pagination={pagination} />
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
              isError={caseNotes.isError}
              isLoading={caseNotes.isPending}
              items={caseNotes.data?.data}
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
            <PaginationControls meta={caseNotes.data?.meta} pagination={notesPagination} />
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
              isError={caseDocuments.isError}
              isLoading={caseDocuments.isPending}
              items={caseDocuments.data?.data}
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
            <PaginationControls meta={caseDocuments.data?.meta} pagination={documentsPagination} />
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
  const queryClient = useQueryClient()
  const pagination = useListPagination()
  const donationsPagination = useListPagination('donor_donations_')
  const [selectedDonorId, setSelectedDonorId] = useState<number | null>(null)
  const [editingDonorId, setEditingDonorId] = useState<number | null>(null)
  const donors = useQuery({ queryKey: ['donors', pagination.params], queryFn: () => getDonorsPage(pagination.params) })
  const donorDetail = useQuery({
    queryKey: ['donor', selectedDonorId],
    queryFn: () => getDonor(selectedDonorId as number),
    enabled: selectedDonorId !== null,
  })
  const donorDonations = useQuery({ queryKey: ['donor-donations', selectedDonorId, donationsPagination.params], queryFn: () => getDonorDonationsPage(selectedDonorId as number, donationsPagination.params), enabled: selectedDonorId !== null })
  const editingDonor = editingDonorId ? donorDetail.data ?? donors.data?.data.find((donor) => donor.id === editingDonorId) ?? null : null

  function refreshDonors(id?: number | null) {
    void queryClient.invalidateQueries({ queryKey: ['donors'] })

    if (id) {
      void queryClient.invalidateQueries({ queryKey: ['donor', id] })
    }
  }

  const createMutation = useMutation({
    mutationFn: createDonor,
    onSuccess: (donor) => {
      setSelectedDonorId(donor.id)
      setEditingDonorId(null)
      refreshDonors(donor.id)
    },
  })
  const updateMutation = useMutation({
    mutationFn: ({ id, input }: { id: number; input: DonorInput }) => updateDonor(id, input),
    onSuccess: (donor) => {
      setSelectedDonorId(donor.id)
      setEditingDonorId(null)
      refreshDonors(donor.id)
    },
  })
  const deleteMutation = useMutation({
    mutationFn: deleteDonor,
    onSuccess: (_, id) => {
      if (selectedDonorId === id) {
        setSelectedDonorId(null)
        setEditingDonorId(null)
      }

      refreshDonors(null)
    },
  })

  function buildDonorInput(form: FormData): DonorInput {
    const preferences = formString(form, 'communication_preferences')

    return {
      donor_type: formString(form, 'donor_type') as DonorInput['donor_type'],
      name: formString(form, 'name'),
      email: formNullable(form, 'email'),
      phone: formNullable(form, 'phone'),
      country: formNullable(form, 'country'),
      city: formNullable(form, 'city'),
      address: formNullable(form, 'address'),
      tax_number: formNullable(form, 'tax_number'),
      notes: formNullable(form, 'notes'),
      communication_preferences: preferences ? preferences.split(',').map((preference) => preference.trim()).filter(Boolean) : null,
      status: formNullable(form, 'status') as DonorInput['status'],
    }
  }

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    const input = buildDonorInput(new FormData(event.currentTarget))

    if (editingDonorId) {
      updateMutation.mutate({ id: editingDonorId, input })
    } else {
      createMutation.mutate(input)
    }
  }

  return (
    <ModulePage description="Manage donor records and donation history." title="Donors">
      <Panel icon={<Users size={20} />} title="Donors">
        <RecordList
          isError={donors.isError}
          isLoading={donors.isPending}
          items={donors.data?.data}
          label="donors"
          render={(donor) => (
            <div className="flex flex-wrap items-center justify-between gap-2">
              <span>
                <StatusBadge status={donor.status} /> {donor.name} - {donor.donor_type} - {donor.donations_count ?? 0} donations
              </span>
              <span className="flex flex-wrap gap-2">
                <SmallButton onClick={() => setSelectedDonorId(donor.id)}>Details</SmallButton>
                <SmallButton
                  onClick={() => {
                    setSelectedDonorId(donor.id)
                    setEditingDonorId(donor.id)
                  }}
                >
                  Edit
                </SmallButton>
                <SmallButton danger onClick={() => window.confirm(`Delete ${donor.name}?`) && deleteMutation.mutate(donor.id)}>
                  Delete
                </SmallButton>
              </span>
            </div>
          )}
        />
        <PaginationControls meta={donors.data?.meta} pagination={pagination} />
      </Panel>
      <Panel icon={<FileText size={20} />} title="Donor Detail">
        {selectedDonorId ? (
          donorDetail.data ? (
            <div className="space-y-4">
              <KeyValueRows
                rows={[
                  ['Name', donorDetail.data.name],
                  ['Type', donorDetail.data.donor_type],
                  ['Status', donorDetail.data.status],
                  ['Email', donorDetail.data.email],
                  ['Phone', donorDetail.data.phone],
                  ['Location', [donorDetail.data.city, donorDetail.data.country].filter(Boolean).join(', ') || null],
                  ['Tax number', donorDetail.data.tax_number],
                  ['Donations', String(donorDetail.data.donations_count ?? donorDetail.data.donations?.length ?? 0)],
                ]}
              />
              <p className="text-sm leading-6 text-[#52645e]">{donorDetail.data.notes ?? 'No donor notes.'}</p>
              <RecordList isError={donorDonations.isError} isLoading={donorDonations.isPending} items={donorDonations.data?.data} label="donor donations" render={(donation) => `${donation.donation_number} - ${formatMoney(donation.amount, donation.currency)} - ${donation.payment_status}`} />
              <PaginationControls meta={donorDonations.data?.meta} pagination={donationsPagination} />
            </div>
          ) : (
            <LoadingOrEmpty isError={donorDetail.isError} isLoading={donorDetail.isPending} label="Loading donor" />
          )
        ) : (
          <EmptyState title="Select a donor" />
        )}
      </Panel>
      <Panel icon={<Settings size={20} />} title={editingDonor ? `Edit ${editingDonor.name}` : 'Create Donor'}>
        <form className="space-y-4" key={editingDonor?.id ?? 'new-donor'} onSubmit={handleSubmit}>
          <FormGrid>
            <SelectField defaultValue={editingDonor?.donor_type ?? 'individual'} label="Type" name="donor_type" options={['individual', 'company', 'institution', 'anonymous']} />
            <TextField defaultValue={editingDonor?.name ?? ''} label="Name" name="name" required />
            <TextField defaultValue={editingDonor?.email ?? ''} label="Email" name="email" type="email" />
            <TextField defaultValue={editingDonor?.phone ?? ''} label="Phone" name="phone" />
            <TextField defaultValue={editingDonor?.country ?? ''} label="Country" name="country" />
            <TextField defaultValue={editingDonor?.city ?? ''} label="City" name="city" />
            <TextField defaultValue={editingDonor?.tax_number ?? ''} label="Tax number" name="tax_number" />
            <TextField defaultValue={formatPreferences(editingDonor?.communication_preferences)} label="Communication preferences" name="communication_preferences" />
            <SelectField defaultValue={editingDonor?.status ?? 'active'} label="Status" name="status" options={['active', 'inactive', 'blocked']} />
          </FormGrid>
          <TextAreaField defaultValue={editingDonor?.address ?? ''} label="Address" name="address" />
          <TextAreaField defaultValue={editingDonor?.notes ?? ''} label="Notes" name="notes" />
          <FormFooter isPending={createMutation.isPending || updateMutation.isPending} onCancel={editingDonorId ? () => setEditingDonorId(null) : undefined} submitLabel={editingDonor ? 'Save donor' : 'Create donor'} />
          <MutationState isError={createMutation.isError || updateMutation.isError || deleteMutation.isError} isSuccess={createMutation.isSuccess || updateMutation.isSuccess || deleteMutation.isSuccess} />
        </form>
      </Panel>
    </ModulePage>
  )
}

function CampaignsPage() {
  const queryClient = useQueryClient()
  const pagination = useListPagination()
  const donationsPagination = useListPagination('campaign_donations_')
  const [selectedCampaignId, setSelectedCampaignId] = useState<number | null>(null)
  const [editingCampaignId, setEditingCampaignId] = useState<number | null>(null)
  const campaigns = useQuery({ queryKey: ['campaigns', pagination.params], queryFn: () => getCampaignsPage(pagination.params) })
  const campaignDetail = useQuery({
    queryKey: ['campaign', selectedCampaignId],
    queryFn: () => getCampaign(selectedCampaignId as number),
    enabled: selectedCampaignId !== null,
  })
  const campaignDonations = useQuery({ queryKey: ['campaign-donations', selectedCampaignId, donationsPagination.params], queryFn: () => getDonationsPage({ ...donationsPagination.params, campaign_id: selectedCampaignId }), enabled: selectedCampaignId !== null })
  const editingCampaign = editingCampaignId ? campaignDetail.data ?? campaigns.data?.data.find((campaign) => campaign.id === editingCampaignId) ?? null : null

  function refreshCampaigns(id?: number | null) {
    void queryClient.invalidateQueries({ queryKey: ['campaigns'] })
    void queryClient.invalidateQueries({ queryKey: ['donations'] })

    if (id) {
      void queryClient.invalidateQueries({ queryKey: ['campaign', id] })
    }
  }

  const createMutation = useMutation({
    mutationFn: createCampaign,
    onSuccess: (campaign) => {
      setSelectedCampaignId(campaign.id)
      setEditingCampaignId(null)
      refreshCampaigns(campaign.id)
    },
  })
  const updateMutation = useMutation({
    mutationFn: ({ id, input }: { id: number; input: CampaignInput }) => updateCampaign(id, input),
    onSuccess: (campaign) => {
      setSelectedCampaignId(campaign.id)
      setEditingCampaignId(null)
      refreshCampaigns(campaign.id)
    },
  })
  const deleteMutation = useMutation({
    mutationFn: deleteCampaign,
    onSuccess: (_, id) => {
      if (selectedCampaignId === id) {
        setSelectedCampaignId(null)
        setEditingCampaignId(null)
      }

      refreshCampaigns(null)
    },
  })
  const workflowMutation = useMutation({
    mutationFn: ({ action, id }: { action: 'activate' | 'pause' | 'complete' | 'cancel'; id: number }) => {
      if (action === 'activate') {
        return activateCampaign(id)
      }

      if (action === 'pause') {
        return pauseCampaign(id)
      }

      if (action === 'complete') {
        return completeCampaign(id)
      }

      return cancelCampaign(id)
    },
    onSuccess: (campaign) => refreshCampaigns(campaign.id),
  })

  function buildCampaignInput(form: FormData): CampaignInput {
    return {
      title: formString(form, 'title'),
      slug: formString(form, 'slug'),
      description: formNullable(form, 'description'),
      goal_amount: Number(formString(form, 'goal_amount')),
      currency: formString(form, 'currency').toUpperCase(),
      start_date: formString(form, 'start_date'),
      end_date: formNullable(form, 'end_date'),
      status: formNullable(form, 'status') as CampaignInput['status'],
      visibility: formNullable(form, 'visibility') as CampaignInput['visibility'],
      cover_image: formNullable(form, 'cover_image'),
    }
  }

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    const input = buildCampaignInput(new FormData(event.currentTarget))

    if (editingCampaignId) {
      updateMutation.mutate({ id: editingCampaignId, input })
    } else {
      createMutation.mutate(input)
    }
  }

  return (
    <ModulePage description="Manage fundraising campaigns and visibility." title="Campaigns">
      <Panel icon={<Landmark size={20} />} title="Campaigns">
        <RecordList
          isError={campaigns.isError}
          isLoading={campaigns.isPending}
          items={campaigns.data?.data}
          label="campaigns"
          render={(campaign) => (
            <div className="flex flex-wrap items-center justify-between gap-2">
              <span>
                <StatusBadge status={campaign.status} /> {campaign.title} - {formatMoney(campaign.collected_amount, campaign.currency)}/{formatMoney(campaign.goal_amount, campaign.currency)} - {campaign.visibility}
              </span>
              <span className="flex flex-wrap gap-2">
                <SmallButton onClick={() => setSelectedCampaignId(campaign.id)}>Details</SmallButton>
                <SmallButton
                  onClick={() => {
                    setSelectedCampaignId(campaign.id)
                    setEditingCampaignId(campaign.id)
                  }}
                >
                  Edit
                </SmallButton>
                <SmallButton danger onClick={() => window.confirm(`Delete ${campaign.title}?`) && deleteMutation.mutate(campaign.id)}>
                  Delete
                </SmallButton>
              </span>
            </div>
          )}
        />
        <PaginationControls meta={campaigns.data?.meta} pagination={pagination} />
      </Panel>
      <Panel icon={<FileText size={20} />} title="Campaign Detail">
        {selectedCampaignId ? (
          campaignDetail.data ? (
            <div className="space-y-4">
              <KeyValueRows
                rows={[
                  ['Title', campaignDetail.data.title],
                  ['Slug', campaignDetail.data.slug],
                  ['Status', campaignDetail.data.status],
                  ['Visibility', campaignDetail.data.visibility],
                  ['Goal', formatMoney(campaignDetail.data.goal_amount, campaignDetail.data.currency)],
                  ['Collected', formatMoney(campaignDetail.data.collected_amount, campaignDetail.data.currency)],
                  ['Progress', `${campaignProgress(campaignDetail.data).toFixed(1)}%`],
                  ['Dates', `${campaignDetail.data.start_date}${campaignDetail.data.end_date ? ` to ${campaignDetail.data.end_date}` : ''}`],
                  ['Donations', String(campaignDetail.data.donations_count ?? campaignDetail.data.donations?.length ?? 0)],
                  ['Allocations', String(campaignDetail.data.allocations_count ?? 0)],
                ]}
              />
              <p className="text-sm leading-6 text-[#52645e]">{campaignDetail.data.description ?? 'No campaign description.'}</p>
              <div className="flex flex-wrap gap-2">
                {['draft', 'paused'].includes(campaignDetail.data.status) ? <SmallButton onClick={() => workflowMutation.mutate({ action: 'activate', id: campaignDetail.data.id })}>Activate</SmallButton> : null}
                {campaignDetail.data.status === 'active' ? <SmallButton onClick={() => workflowMutation.mutate({ action: 'pause', id: campaignDetail.data.id })}>Pause</SmallButton> : null}
                {['active', 'paused'].includes(campaignDetail.data.status) ? <SmallButton onClick={() => workflowMutation.mutate({ action: 'complete', id: campaignDetail.data.id })}>Complete</SmallButton> : null}
                {['draft', 'active', 'paused'].includes(campaignDetail.data.status) ? <SmallButton danger onClick={() => workflowMutation.mutate({ action: 'cancel', id: campaignDetail.data.id })}>Cancel</SmallButton> : null}
              </div>
              <RecordList isError={campaignDonations.isError} isLoading={campaignDonations.isPending} items={campaignDonations.data?.data} label="campaign donations" render={(donation) => `${donation.donation_number} - ${formatMoney(donation.amount, donation.currency)} - ${donation.payment_status}`} />
              <PaginationControls meta={campaignDonations.data?.meta} pagination={donationsPagination} />
            </div>
          ) : (
            <LoadingOrEmpty isError={campaignDetail.isError} isLoading={campaignDetail.isPending} label="Loading campaign" />
          )
        ) : (
          <EmptyState title="Select a campaign" />
        )}
      </Panel>
      <Panel icon={<Settings size={20} />} title={editingCampaign ? `Edit ${editingCampaign.title}` : 'Create Campaign'}>
        <form className="space-y-4" key={editingCampaign?.id ?? 'new-campaign'} onSubmit={handleSubmit}>
          <FormGrid>
            <TextField defaultValue={editingCampaign?.title ?? ''} label="Title" name="title" required />
            <TextField defaultValue={editingCampaign?.slug ?? ''} label="Slug" name="slug" required />
            <TextField defaultValue={editingCampaign?.goal_amount ?? ''} label="Goal amount" min={0} name="goal_amount" required step="0.01" type="number" />
            <TextField defaultValue={editingCampaign?.currency ?? 'EGP'} label="Currency" maxLength={3} name="currency" required />
            <TextField defaultValue={editingCampaign?.start_date ?? todayDate()} label="Start date" name="start_date" required type="date" />
            <TextField defaultValue={editingCampaign?.end_date ?? ''} label="End date" name="end_date" type="date" />
            <SelectField defaultValue={editingCampaign?.status ?? 'draft'} label={editingCampaign ? 'Current status' : 'Initial status'} name="status" options={['draft', 'active', 'paused', 'completed', 'cancelled']} />
            <SelectField defaultValue={editingCampaign?.visibility ?? 'private'} label="Visibility" name="visibility" options={['private', 'public']} />
          </FormGrid>
          <TextField defaultValue={editingCampaign?.cover_image ?? ''} label="Cover image URL/path" name="cover_image" />
          <TextAreaField defaultValue={editingCampaign?.description ?? ''} label="Description" name="description" />
          <FormFooter isPending={createMutation.isPending || updateMutation.isPending} onCancel={editingCampaignId ? () => setEditingCampaignId(null) : undefined} submitLabel={editingCampaign ? 'Save campaign' : 'Create campaign'} />
          <MutationState isError={createMutation.isError || updateMutation.isError || deleteMutation.isError || workflowMutation.isError} isSuccess={createMutation.isSuccess || updateMutation.isSuccess || deleteMutation.isSuccess || workflowMutation.isSuccess} />
        </form>
      </Panel>
    </ModulePage>
  )
}

function DonationsPage() {
  const queryClient = useQueryClient()
  const pagination = useListPagination()
  const allocationsPagination = useListPagination('allocations_')
  const [selectedDonationId, setSelectedDonationId] = useState<number | null>(null)
  const [editingDonationId, setEditingDonationId] = useState<number | null>(null)
  const [editingAllocation, setEditingAllocation] = useState<DonationAllocation | null>(null)
  const donations = useQuery({ queryKey: ['donations', pagination.params], queryFn: () => getDonationsPage(pagination.params) })
  const donors = useQuery({ queryKey: ['donors'], queryFn: getDonors })
  const campaigns = useQuery({ queryKey: ['campaigns'], queryFn: getCampaigns })
  const beneficiaries = useQuery({ queryKey: ['beneficiaries'], queryFn: getBeneficiaries })
  const caseFiles = useQuery({ queryKey: ['case-files'], queryFn: getCaseFiles })
  const donationDetail = useQuery({
    queryKey: ['donation', selectedDonationId],
    queryFn: () => getDonation(selectedDonationId as number),
    enabled: selectedDonationId !== null,
  })
  const donationAllocations = useQuery({ queryKey: ['donation-allocations', selectedDonationId, allocationsPagination.params], queryFn: () => getDonationAllocationsPage(selectedDonationId as number, allocationsPagination.params), enabled: selectedDonationId !== null })
  const editingDonation = editingDonationId ? donationDetail.data ?? donations.data?.data.find((donation) => donation.id === editingDonationId) ?? null : null
  const donationIsLocked = editingDonation ? isDonationLocked(editingDonation) : false
  const selectedDonationIsLocked = donationDetail.data ? isDonationLocked(donationDetail.data) : false

  useEffect(() => {
    setEditingAllocation(null)
  }, [selectedDonationId])

  function refreshDonations(id?: number | null) {
    void queryClient.invalidateQueries({ queryKey: ['donations'] })
    void queryClient.invalidateQueries({ queryKey: ['campaigns'] })
    void queryClient.invalidateQueries({ queryKey: ['donors'] })

    if (id) {
      void queryClient.invalidateQueries({ queryKey: ['donation', id] })
      void queryClient.invalidateQueries({ queryKey: ['donation-payment-transactions', id] })
    }
  }

  const createMutation = useMutation({
    mutationFn: createDonation,
    onSuccess: (donation) => {
      setSelectedDonationId(donation.id)
      setEditingDonationId(null)
      refreshDonations(donation.id)
    },
  })
  const updateMutation = useMutation({
    mutationFn: ({ id, input }: { id: number; input: DonationInput }) => updateDonation(id, input),
    onSuccess: (donation) => {
      setSelectedDonationId(donation.id)
      setEditingDonationId(null)
      refreshDonations(donation.id)
    },
  })
  const cancelMutation = useMutation({
    mutationFn: cancelDonation,
    onSuccess: (donation) => refreshDonations(donation.id),
  })
  const confirmMutation = useMutation({
    mutationFn: ({ id, input }: { id: number; input: ConfirmDonationInput }) => confirmDonation(id, input),
    onSuccess: (donation) => refreshDonations(donation.id),
  })
  const receiptMutation = useMutation({
    mutationFn: generateDonationReceipt,
    onSuccess: (_, id) => refreshDonations(id),
  })
  const createAllocationMutation = useMutation({
    mutationFn: ({ donationId, input }: { donationId: number; input: DonationAllocationInput }) => createDonationAllocation(donationId, input),
    onSuccess: (_, variables) => {
      setEditingAllocation(null)
      refreshDonations(variables.donationId)
    },
  })
  const updateAllocationMutation = useMutation({
    mutationFn: ({ allocationId, donationId, input }: { allocationId: number; donationId: number; input: DonationAllocationInput }) => updateDonationAllocation(donationId, allocationId, input),
    onSuccess: (_, variables) => {
      setEditingAllocation(null)
      refreshDonations(variables.donationId)
    },
  })
  const deleteAllocationMutation = useMutation({
    mutationFn: ({ allocationId, donationId }: { allocationId: number; donationId: number }) => deleteDonationAllocation(donationId, allocationId),
    onSuccess: (_, variables) => refreshDonations(variables.donationId),
  })

  function buildDonationInput(form: FormData, donation?: Donation | null): DonationInput {
    if (donation && isDonationLocked(donation)) {
      return { notes: formNullable(form, 'donation_notes') }
    }

    return {
      donor_id: formNullableNumber(form, 'donor_id'),
      campaign_id: formNullableNumber(form, 'campaign_id'),
      amount: Number(formString(form, 'amount')),
      currency: formString(form, 'currency').toUpperCase(),
      payment_method: formString(form, 'payment_method') as DonationInput['payment_method'],
      donation_status: formString(form, 'donation_status') as DonationInput['donation_status'],
      donated_at: formString(form, 'donated_at'),
      notes: formNullable(form, 'donation_notes'),
    }
  }

  function buildAllocationInput(form: FormData): DonationAllocationInput {
    return {
      allocation_type: formString(form, 'allocation_type') as DonationAllocationInput['allocation_type'],
      campaign_id: formNullableNumber(form, 'allocation_campaign_id'),
      beneficiary_id: formNullableNumber(form, 'allocation_beneficiary_id'),
      case_file_id: formNullableNumber(form, 'allocation_case_file_id'),
      amount: Number(formString(form, 'allocation_amount')),
      notes: formNullable(form, 'allocation_notes'),
    }
  }

  function handleDonationSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    const input = buildDonationInput(new FormData(event.currentTarget), editingDonation)

    if (editingDonationId) {
      updateMutation.mutate({ id: editingDonationId, input })
    } else {
      createMutation.mutate(input)
    }
  }

  function handleAllocationSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()

    if (!selectedDonationId) {
      return
    }

    const input = buildAllocationInput(new FormData(event.currentTarget))

    if (editingAllocation) {
      updateAllocationMutation.mutate({ allocationId: editingAllocation.id, donationId: selectedDonationId, input })
    } else {
      createAllocationMutation.mutate({ donationId: selectedDonationId, input })
    }
  }

  function runDonationConfirm(donation: Donation) {
    if (!window.confirm(`Confirm payment for ${donation.donation_number}?`)) {
      return
    }

    const notes = window.prompt('Confirmation notes (optional)')
    confirmMutation.mutate({
      id: donation.id,
      input: {
        provider: 'manual',
        paid_at: new Date().toISOString(),
        notes: notes?.trim() || null,
      },
    })
  }

  return (
    <ModulePage description="Record donations, manage allocations, confirm payments, and generate receipts." title="Donations">
      <Panel icon={<DollarSign size={20} />} title="Donations">
        <RecordList
          isError={donations.isError}
          isLoading={donations.isPending}
          items={donations.data?.data}
          label="donations"
          render={(donation) => (
            <div className="flex flex-wrap items-center justify-between gap-2">
              <span>
                <StatusBadge status={donation.payment_status} /> {donation.donation_number} - {donation.donor?.name ?? 'Anonymous'} - {formatMoney(donation.amount, donation.currency)} - {donation.donation_status}
              </span>
              <span className="flex flex-wrap gap-2">
                <SmallButton onClick={() => setSelectedDonationId(donation.id)}>Details</SmallButton>
                <SmallButton
                  onClick={() => {
                    setSelectedDonationId(donation.id)
                    setEditingDonationId(donation.id)
                  }}
                >
                  Edit
                </SmallButton>
                {!isDonationLocked(donation) ? <SmallButton danger onClick={() => window.confirm(`Cancel ${donation.donation_number}?`) && cancelMutation.mutate(donation.id)}>Cancel</SmallButton> : null}
              </span>
            </div>
          )}
        />
        <PaginationControls meta={donations.data?.meta} pagination={pagination} />
      </Panel>
      <Panel icon={<Receipt size={20} />} title="Donation Detail">
        {selectedDonationId ? (
          donationDetail.data ? (
            <div className="space-y-4">
              <KeyValueRows
                rows={[
                  ['Donation number', donationDetail.data.donation_number],
                  ['Donor', donationDetail.data.donor?.name ?? 'Anonymous'],
                  ['Campaign', donationDetail.data.campaign?.title ?? 'None'],
                  ['Amount', formatMoney(donationDetail.data.amount, donationDetail.data.currency)],
                  ['Allocated', formatMoney(donationAllocationTotal(donationDetail.data), donationDetail.data.currency)],
                  ['Remaining', formatMoney(donationRemainingAmount(donationDetail.data), donationDetail.data.currency)],
                  ['Payment method', donationDetail.data.payment_method],
                  ['Payment status', donationDetail.data.payment_status],
                  ['Donation status', donationDetail.data.donation_status],
                  ['Donated at', formatDate(donationDetail.data.donated_at)],
                  ['Confirmed at', donationDetail.data.confirmed_at ? formatDate(donationDetail.data.confirmed_at) : null],
                  ['Receipt', donationDetail.data.receipt?.receipt_number ?? null],
                ]}
              />
              <p className="text-sm leading-6 text-[#52645e]">{donationDetail.data.notes ?? 'No donation notes.'}</p>
              <div className="flex flex-wrap gap-2">
                {!selectedDonationIsLocked ? <SmallButton onClick={() => runDonationConfirm(donationDetail.data)}>Confirm payment</SmallButton> : null}
                {!selectedDonationIsLocked ? <SmallButton danger onClick={() => window.confirm(`Cancel ${donationDetail.data.donation_number}?`) && cancelMutation.mutate(donationDetail.data.id)}>Cancel</SmallButton> : null}
                {isDonationConfirmed(donationDetail.data) ? <SmallButton onClick={() => receiptMutation.mutate(donationDetail.data.id)}>{donationDetail.data.receipt ? 'Regenerate receipt' : 'Generate receipt'}</SmallButton> : null}
              </div>
            </div>
          ) : (
            <LoadingOrEmpty isError={donationDetail.isError} isLoading={donationDetail.isPending} label="Loading donation" />
          )
        ) : (
          <EmptyState title="Select a donation" />
        )}
      </Panel>
      <Panel icon={<Settings size={20} />} title={editingDonation ? `Edit ${editingDonation.donation_number}` : 'Create Donation'}>
        <form className="space-y-4" key={editingDonation?.id ?? 'new-donation'} onSubmit={handleDonationSubmit}>
          <FormGrid>
            <SelectField
              defaultValue={editingDonation?.donor_id ? String(editingDonation.donor_id) : ''}
              disabled={donationIsLocked}
              label="Donor"
              name="donor_id"
              optionLabels={{ '': 'Anonymous / no donor', ...(donors.data ?? []).reduce<Record<string, string>>((labels, donor) => ({ ...labels, [String(donor.id)]: `${donor.name} (${donor.donor_type})` }), {}) }}
              options={['', ...(donors.data ?? []).map((donor) => String(donor.id))]}
            />
            <SelectField
              defaultValue={editingDonation?.campaign_id ? String(editingDonation.campaign_id) : ''}
              disabled={donationIsLocked}
              label="Campaign"
              name="campaign_id"
              optionLabels={{ '': 'No campaign', ...(campaigns.data ?? []).reduce<Record<string, string>>((labels, campaign) => ({ ...labels, [String(campaign.id)]: `${campaign.title} (${campaign.status})` }), {}) }}
              options={['', ...(campaigns.data ?? []).map((campaign) => String(campaign.id))]}
            />
            <TextField defaultValue={editingDonation?.amount ?? ''} disabled={donationIsLocked} label="Amount" min={0.01} name="amount" required={!donationIsLocked} step="0.01" type="number" />
            <TextField defaultValue={editingDonation?.currency ?? 'EGP'} disabled={donationIsLocked} label="Currency" maxLength={3} name="currency" required={!donationIsLocked} />
            <SelectField defaultValue={editingDonation?.payment_method ?? 'cash'} disabled={donationIsLocked} label="Payment method" name="payment_method" options={['cash', 'bank_transfer', 'card', 'check', 'mobile_wallet', 'other']} required={!donationIsLocked} />
            <SelectField defaultValue={editingDonation?.donation_status ?? 'pending'} disabled={donationIsLocked} label="Donation status" name="donation_status" options={['draft', 'pending']} />
            <TextField defaultValue={dateTimeLocalValue(editingDonation?.donated_at)} disabled={donationIsLocked} label="Donated at" name="donated_at" required={!donationIsLocked} type="datetime-local" />
          </FormGrid>
          <TextAreaField defaultValue={editingDonation?.notes ?? ''} label="Notes" name="donation_notes" />
          <FormFooter isPending={createMutation.isPending || updateMutation.isPending} onCancel={editingDonationId ? () => setEditingDonationId(null) : undefined} submitLabel={editingDonation ? 'Save donation' : 'Create donation'} />
          <MutationState isError={createMutation.isError || updateMutation.isError || cancelMutation.isError || confirmMutation.isError || receiptMutation.isError} isSuccess={createMutation.isSuccess || updateMutation.isSuccess || cancelMutation.isSuccess || confirmMutation.isSuccess || receiptMutation.isSuccess} />
        </form>
      </Panel>
      <Panel icon={<ListChecks size={20} />} title="Allocation Builder">
        {donationDetail.data ? (
          <div className="space-y-4">
            <KeyValueRows
              rows={[
                ['Donation amount', formatMoney(donationDetail.data.amount, donationDetail.data.currency)],
                ['Allocated total', formatMoney(donationAllocationTotal(donationDetail.data), donationDetail.data.currency)],
                ['Remaining before confirmation', formatMoney(donationRemainingAmount(donationDetail.data), donationDetail.data.currency)],
              ]}
            />
            <RecordList
              isError={donationAllocations.isError}
              isLoading={donationAllocations.isPending}
              items={donationAllocations.data?.data}
              label="donation allocations"
              render={(allocation) => (
                <div className="flex flex-wrap items-center justify-between gap-2">
                  <span>
                    {allocation.allocation_type} - {allocationTargetLabel(allocation)} - {formatMoney(allocation.amount, donationDetail.data?.currency ?? 'EGP')}
                  </span>
                  {!selectedDonationIsLocked ? (
                    <span className="flex gap-2">
                      <SmallButton onClick={() => setEditingAllocation(allocation)}>Edit</SmallButton>
                      <SmallButton danger onClick={() => selectedDonationId && window.confirm('Delete this allocation?') && deleteAllocationMutation.mutate({ allocationId: allocation.id, donationId: selectedDonationId })}>
                        Delete
                      </SmallButton>
                    </span>
                  ) : null}
                </div>
              )}
            />
            <PaginationControls meta={donationAllocations.data?.meta} pagination={allocationsPagination} />
            {!selectedDonationIsLocked ? (
              <form className="space-y-4" key={editingAllocation?.id ?? `allocation-${selectedDonationId}`} onSubmit={handleAllocationSubmit}>
                <FormGrid>
                  <SelectField defaultValue={editingAllocation?.allocation_type ?? 'general_fund'} label="Allocation type" name="allocation_type" options={['general_fund', 'campaign', 'beneficiary', 'case_file', 'medical', 'education', 'food', 'emergency', 'inventory', 'other']} />
                  <TextField defaultValue={editingAllocation?.amount ?? ''} label="Amount" min={0.01} name="allocation_amount" required step="0.01" type="number" />
                  <SelectField
                    defaultValue={editingAllocation?.campaign_id ? String(editingAllocation.campaign_id) : ''}
                    label="Target campaign"
                    name="allocation_campaign_id"
                    optionLabels={{ '': 'No campaign target', ...(campaigns.data ?? []).reduce<Record<string, string>>((labels, campaign) => ({ ...labels, [String(campaign.id)]: `${campaign.title} (${campaign.status})` }), {}) }}
                    options={['', ...(campaigns.data ?? []).map((campaign) => String(campaign.id))]}
                  />
                  <SelectField
                    defaultValue={editingAllocation?.beneficiary_id ? String(editingAllocation.beneficiary_id) : ''}
                    label="Target beneficiary"
                    name="allocation_beneficiary_id"
                    optionLabels={{ '': 'No beneficiary target', ...(beneficiaries.data ?? []).reduce<Record<string, string>>((labels, beneficiary) => ({ ...labels, [String(beneficiary.id)]: `${beneficiary.code} - ${beneficiary.full_name} (${beneficiary.status})` }), {}) }}
                    options={['', ...(beneficiaries.data ?? []).map((beneficiary) => String(beneficiary.id))]}
                  />
                  <SelectField
                    defaultValue={editingAllocation?.case_file_id ? String(editingAllocation.case_file_id) : ''}
                    label="Target case file"
                    name="allocation_case_file_id"
                    optionLabels={{ '': 'No case target', ...(caseFiles.data ?? []).reduce<Record<string, string>>((labels, caseFile) => ({ ...labels, [String(caseFile.id)]: `${caseFile.case_number} (${caseFile.status})` }), {}) }}
                    options={['', ...(caseFiles.data ?? []).map((caseFile) => String(caseFile.id))]}
                  />
                </FormGrid>
                <TextAreaField defaultValue={editingAllocation?.notes ?? ''} label="Allocation notes" name="allocation_notes" />
                <FormFooter isPending={createAllocationMutation.isPending || updateAllocationMutation.isPending} onCancel={editingAllocation ? () => setEditingAllocation(null) : undefined} submitLabel={editingAllocation ? 'Save allocation' : 'Add allocation'} />
                <MutationState isError={createAllocationMutation.isError || updateAllocationMutation.isError || deleteAllocationMutation.isError} isSuccess={createAllocationMutation.isSuccess || updateAllocationMutation.isSuccess || deleteAllocationMutation.isSuccess} />
              </form>
            ) : (
              <EmptyState title="Allocations are locked" />
            )}
          </div>
        ) : (
          <EmptyState title="Select a donation" />
        )}
      </Panel>
    </ModulePage>
  )
}

function PaymentsPage() {
  const queryClient = useQueryClient()
  const pagination = useListPagination()
  const transactionsPagination = useListPagination('transactions_')
  const [selectedDonationId, setSelectedDonationId] = useState<number | null>(null)
  const [selectedTransactionId, setSelectedTransactionId] = useState<number | null>(null)
  const donations = useQuery({ queryKey: ['donations', pagination.params], queryFn: () => getDonationsPage(pagination.params) })
  const donationDetail = useQuery({
    queryKey: ['donation', selectedDonationId],
    queryFn: () => getDonation(selectedDonationId as number),
    enabled: selectedDonationId !== null,
  })
  const transactions = useQuery({
    queryKey: ['donation-payment-transactions', selectedDonationId, transactionsPagination.params],
    queryFn: () => getDonationPaymentTransactionsPage(selectedDonationId as number, transactionsPagination.params),
    enabled: selectedDonationId !== null,
  })
  const transactionDetail = useQuery({
    queryKey: ['payment-transaction', selectedTransactionId],
    queryFn: () => getPaymentTransaction(selectedTransactionId as number),
    enabled: selectedTransactionId !== null,
  })
  const receiptMutation = useMutation({
    mutationFn: generateDonationReceipt,
    onSuccess: (_, id) => {
      void queryClient.invalidateQueries({ queryKey: ['donation', id] })
      void queryClient.invalidateQueries({ queryKey: ['donations'] })
    },
  })

  return (
    <ModulePage description="Review payment transactions and issued receipts." title="Payments & Receipts">
      <Panel icon={<Receipt size={20} />} title="Donation Payments">
        <RecordList
          isError={donations.isError}
          isLoading={donations.isPending}
          items={donations.data?.data}
          label="donations"
          render={(donation) => (
            <div className="flex flex-wrap items-center justify-between gap-2">
              <span>
                <StatusBadge status={donation.payment_status} /> {donation.donation_number} - {formatMoney(donation.amount, donation.currency)} - {donation.payment_transactions_count ?? 0} transactions
              </span>
              <SmallButton
                onClick={() => {
                  setSelectedDonationId(donation.id)
                  setSelectedTransactionId(null)
                }}
              >
                View
              </SmallButton>
            </div>
          )}
        />
        <PaginationControls meta={donations.data?.meta} pagination={pagination} />
      </Panel>
      <Panel icon={<FileText size={20} />} title="Receipt">
        {selectedDonationId ? (
          donationDetail.data ? (
            <div className="space-y-4">
              <KeyValueRows
                rows={[
                  ['Donation', donationDetail.data.donation_number],
                  ['Status', donationDetail.data.donation_status],
                  ['Payment', donationDetail.data.payment_status],
                  ['Receipt number', donationDetail.data.receipt?.receipt_number ?? null],
                  ['Receipt status', donationDetail.data.receipt?.status ?? null],
                  ['Issued at', donationDetail.data.receipt?.issued_at ? formatDate(donationDetail.data.receipt.issued_at) : null],
                  ['Issued by', donationDetail.data.receipt?.issuer?.email ?? null],
                ]}
              />
              {isDonationConfirmed(donationDetail.data) ? <SmallButton onClick={() => receiptMutation.mutate(donationDetail.data.id)}>{donationDetail.data.receipt ? 'Regenerate receipt' : 'Generate receipt'}</SmallButton> : null}
              <MutationState isError={receiptMutation.isError} isSuccess={receiptMutation.isSuccess} />
            </div>
          ) : (
            <LoadingOrEmpty isError={donationDetail.isError} isLoading={donationDetail.isPending} label="Loading receipt" />
          )
        ) : (
          <EmptyState title="Select a donation" />
        )}
      </Panel>
      <Panel icon={<ListChecks size={20} />} title="Payment Transactions">
        {selectedDonationId ? (
          <><RecordList
            isError={transactions.isError}
            isLoading={transactions.isPending}
            items={transactions.data?.data}
            label="payment transactions"
            render={(transaction) => (
              <div className="flex flex-wrap items-center justify-between gap-2">
                <span>
                  <StatusBadge status={transaction.status} /> {transaction.provider} - {formatMoney(transaction.amount, transaction.currency)} - {transaction.paid_at ? formatDate(transaction.paid_at) : formatDate(transaction.created_at)}
                </span>
                <SmallButton onClick={() => setSelectedTransactionId(transaction.id)}>Details</SmallButton>
              </div>
            )}
          />
          <PaginationControls meta={transactions.data?.meta} pagination={transactionsPagination} />
          </>
        ) : (
          <EmptyState title="Select a donation" />
        )}
      </Panel>
      <Panel icon={<FileText size={20} />} title="Transaction Detail">
        {selectedTransactionId ? (
          transactionDetail.data ? (
            <PaymentTransactionDetail transaction={transactionDetail.data} />
          ) : (
            <LoadingOrEmpty isError={transactionDetail.isError} isLoading={transactionDetail.isPending} label="Loading payment transaction" />
          )
        ) : (
          <EmptyState title="Select a transaction" />
        )}
      </Panel>
    </ModulePage>
  )
}

function WarehousesPage() {
  const { me } = useOutletContext<{ me: CurrentUser }>()
  const queryClient = useQueryClient()
  const pagination = useListPagination()
  const warehouses = useQuery({ queryKey: ['warehouses', pagination.params], queryFn: () => getWarehousesPage(pagination.params) })
  const branches = useQuery({ queryKey: ['branches'], queryFn: getBranches })
  const users = useQuery({ queryKey: ['users'], queryFn: getUsers })
  const [editing, setEditing] = useState<WarehouseRecord | null>(null)
  const save = useMutation({
    mutationFn: ({ id, input }: { id?: number; input: WarehouseInput }) => (id ? updateWarehouse(id, input) : createWarehouse(input)),
    onSuccess: () => { setEditing(null); void queryClient.invalidateQueries({ queryKey: ['warehouses'] }) },
  })
  const remove = useMutation({ mutationFn: deleteWarehouse, onSuccess: () => { setEditing(null); void queryClient.invalidateQueries({ queryKey: ['warehouses'] }) } })

  function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    const form = new FormData(event.currentTarget)
    save.mutate({ id: editing?.id, input: { branch_id: formNullableNumber(form, 'branch_id'), name: formString(form, 'name'), code: formString(form, 'code'), address: formNullable(form, 'address'), manager_user_id: formNullableNumber(form, 'manager_user_id'), status: formString(form, 'status') } })
  }

  return (
    <ModulePage description="Manage warehouses, branch assignment, status, managers, and stock activity." title="Warehouses">
      <Panel icon={<Warehouse size={20} />} title="Warehouses">
        <RecordList isError={warehouses.isError} isLoading={warehouses.isPending} items={warehouses.data?.data} label="warehouses" render={(warehouse) => <button className="w-full text-left" onClick={() => setEditing(warehouse)} type="button"><StatusBadge status={warehouse.status} /> {warehouse.code} - {warehouse.name} - {warehouse.stock_lots_count ?? 0} lots / {warehouse.stock_movements_count ?? 0} movements</button>} />
        <PaginationControls meta={warehouses.data?.meta} pagination={pagination} />
      </Panel>
      {me.permissions?.some((p) => ['warehouses.create', 'warehouses.update'].includes(p)) ? <Panel icon={<Warehouse size={20} />} title={editing ? `Edit ${editing.code}` : 'Create Warehouse'}>
        <form className="space-y-4" key={editing?.id ?? 'new'} onSubmit={submit}>
          <FormGrid><TextField defaultValue={editing?.name} label="Name" name="name" required /><TextField defaultValue={editing?.code} label="Code" name="code" required />
            <SelectField defaultValue={String(editing?.branch_id ?? '')} label="Branch" name="branch_id" options={['', ...(branches.data ?? []).map((b) => String(b.id))]} optionLabels={Object.fromEntries((branches.data ?? []).map((b) => [String(b.id), `${b.code} - ${b.name}`]))} />
            <SelectField defaultValue={String(editing?.manager_user_id ?? '')} label="Manager" name="manager_user_id" options={['', ...(users.data ?? []).map((u) => String(u.id))]} optionLabels={Object.fromEntries((users.data ?? []).map((u) => [String(u.id), u.name]))} />
            <SelectField defaultValue={editing?.status ?? 'active'} label="Status" name="status" options={['active', 'inactive']} /></FormGrid>
          <TextAreaField defaultValue={editing?.address ?? ''} label="Address" name="address" />
          <FormFooter isPending={save.isPending} onCancel={editing ? () => setEditing(null) : undefined} submitLabel={editing ? 'Save warehouse' : 'Create warehouse'} /><MutationState isError={save.isError || remove.isError} isSuccess={save.isSuccess || remove.isSuccess} />
          {editing && me.permissions?.includes('warehouses.delete') ? <SmallButton danger onClick={() => { if (confirm('Delete this warehouse?')) remove.mutate(editing.id) }}>Delete warehouse</SmallButton> : null}
        </form>
      </Panel> : null}
    </ModulePage>
  )
}

function InventoryItemsPage() {
  const { me } = useOutletContext<{ me: CurrentUser }>()
  const queryClient = useQueryClient()
  const pagination = useListPagination()
  const inventoryItems = useQuery({ queryKey: ['inventory-items', pagination.params], queryFn: () => getInventoryItemsPage(pagination.params) })
  const [editing, setEditing] = useState<InventoryItem | null>(null)
  const save = useMutation({ mutationFn: ({ id, input }: { id?: number; input: InventoryItemInput }) => id ? updateInventoryItem(id, input) : createInventoryItem(input), onSuccess: () => { setEditing(null); void queryClient.invalidateQueries({ queryKey: ['inventory-items'] }); void queryClient.invalidateQueries({ queryKey: ['stock-summary'] }) } })
  const remove = useMutation({ mutationFn: deleteInventoryItem, onSuccess: () => { setEditing(null); void queryClient.invalidateQueries({ queryKey: ['inventory-items'] }) } })

  function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault(); const form = new FormData(event.currentTarget)
    save.mutate({ id: editing?.id, input: { sku: formString(form, 'sku'), name: formString(form, 'name'), category: formString(form, 'category'), unit: formString(form, 'unit'), description: formNullable(form, 'description'), minimum_stock_level: Number(formString(form, 'minimum_stock_level') || 0), track_expiry: form.get('track_expiry') === 'on', status: formString(form, 'status') } })
  }

  return (
    <ModulePage description="Manage catalog items, units, categories, minimum stock, and expiry tracking." title="Inventory Items">
      <Panel icon={<Package size={20} />} title="Inventory Items">
        <RecordList isError={inventoryItems.isError} isLoading={inventoryItems.isPending} items={inventoryItems.data?.data} label="inventory items" render={(item) => <button className="w-full text-left" onClick={() => setEditing(item)} type="button"><StatusBadge status={item.status} /> {item.sku} - {item.name} - {item.category} - min {item.minimum_stock_level} {item.unit}</button>} />
        <PaginationControls meta={inventoryItems.data?.meta} pagination={pagination} />
      </Panel>
      {me.permissions?.some((p) => ['inventory_items.create', 'inventory_items.update'].includes(p)) ? <Panel icon={<Package size={20} />} title={editing ? `Edit ${editing.sku}` : 'Create Inventory Item'}>
        <form className="space-y-4" key={editing?.id ?? 'new'} onSubmit={submit}><FormGrid>
          <TextField defaultValue={editing?.sku} label="SKU" name="sku" required /><TextField defaultValue={editing?.name} label="Name" name="name" required /><TextField defaultValue={editing?.category} label="Category" name="category" required /><TextField defaultValue={editing?.unit} label="Unit" name="unit" required /><TextField defaultValue={editing?.minimum_stock_level ?? '0'} label="Minimum stock" min="0" name="minimum_stock_level" step="0.001" type="number" /><SelectField defaultValue={editing?.status ?? 'active'} label="Status" name="status" options={['active', 'inactive']} /></FormGrid>
          <TextAreaField defaultValue={editing?.description ?? ''} label="Description" name="description" /><label className="flex items-center gap-2 text-sm"><input defaultChecked={editing?.track_expiry} name="track_expiry" type="checkbox" /> Track expiry dates</label>
          <FormFooter isPending={save.isPending} onCancel={editing ? () => setEditing(null) : undefined} submitLabel={editing ? 'Save item' : 'Create item'} /><MutationState isError={save.isError || remove.isError} isSuccess={save.isSuccess || remove.isSuccess} />
          {editing && me.permissions?.includes('inventory_items.delete') ? <SmallButton danger onClick={() => { if (confirm('Delete this inventory item?')) remove.mutate(editing.id) }}>Delete item</SmallButton> : null}
        </form>
      </Panel> : null}
    </ModulePage>
  )
}

function StockSummaryPage() {
  const pagination = useListPagination()
  const stockSummary = useQuery({ queryKey: ['stock-summary', pagination.params], queryFn: () => getStockSummaryPage(pagination.params) })

  return (
    <ModulePage description="Review available and reserved quantities by item." title="Stock Summary" planned={['Add filters by warehouse/category.', 'Link rows to item and movement detail pages.']}>
      <Panel icon={<Boxes size={20} />} title="Stock Summary">
        <RecordList isError={stockSummary.isError} isLoading={stockSummary.isPending} items={stockSummary.data?.data} label="stock summary rows" render={(row) => `${row.sku} - ${row.available_quantity} ${row.unit} available - ${row.reserved_quantity} reserved - ${row.low_stock ? 'low stock' : 'healthy'}`} />
        <PaginationControls meta={stockSummary.data?.meta} pagination={pagination} />
      </Panel>
    </ModulePage>
  )
}

function StockLotsPage() {
  const pagination = useListPagination()
  const stockLots = useQuery({ queryKey: ['stock-lots', pagination.params], queryFn: () => getStockLotsPage(pagination.params) })

  return (
    <ModulePage description="Review stock lots, remaining quantities, reservations, sources, and expiry dates." title="Stock Lots" planned={['Add receive stock form route.', 'Add lot detail view.']}>
      <Panel icon={<Boxes size={20} />} title="Stock Lots">
        <RecordList
          isError={stockLots.isError}
          isLoading={stockLots.isPending}
          items={stockLots.data?.data}
          label="stock lots"
          render={(lot) =>
            `${lot.inventory_item?.sku ?? 'Unknown item'} - ${lot.remaining_quantity} ${lot.inventory_item?.unit ?? ''} remaining - ${lot.warehouse?.code ?? 'No warehouse'} - ${lot.source_type} #${lot.source_id ?? '-'} - expires ${lot.expiry_date ?? '-'}`
          }
        />
        <PaginationControls meta={stockLots.data?.meta} pagination={pagination} />
      </Panel>
    </ModulePage>
  )
}

function StockMovementsPage() {
  const { me } = useOutletContext<{ me: CurrentUser }>()
  const queryClient = useQueryClient()
  const pagination = useListPagination()
  const stockMovements = useQuery({ queryKey: ['stock-movements', pagination.params], queryFn: () => getStockMovementsPage(pagination.params) })
  const warehouses = useQuery({ queryKey: ['warehouses'], queryFn: getWarehouses })
  const items = useQuery({ queryKey: ['inventory-items'], queryFn: getInventoryItems })
  const lots = useQuery({ queryKey: ['stock-lots'], queryFn: getStockLots })
  const receive = useMutation({ mutationFn: receiveStock, onSuccess: refreshStock })
  const adjust = useMutation({ mutationFn: adjustStock, onSuccess: refreshStock })

  function refreshStock() {
    void queryClient.invalidateQueries({ queryKey: ['stock-movements'] }); void queryClient.invalidateQueries({ queryKey: ['stock-lots'] }); void queryClient.invalidateQueries({ queryKey: ['stock-summary'] }); void queryClient.invalidateQueries({ queryKey: ['stock-low-stock'] })
  }
  function receiveSubmit(event: FormEvent<HTMLFormElement>) { event.preventDefault(); const f = new FormData(event.currentTarget); receive.mutate({ warehouse_id: Number(formString(f, 'warehouse_id')), inventory_item_id: Number(formString(f, 'inventory_item_id')), quantity: Number(formString(f, 'quantity')), source_type: formString(f, 'source_type'), source_id: formNullableNumber(f, 'source_id'), expiry_date: formNullable(f, 'expiry_date'), received_at: formString(f, 'received_at'), notes: formNullable(f, 'notes') }) }
  function adjustSubmit(event: FormEvent<HTMLFormElement>) { event.preventDefault(); const f = new FormData(event.currentTarget); adjust.mutate({ warehouse_id: Number(formString(f, 'warehouse_id')), inventory_item_id: Number(formString(f, 'inventory_item_id')), stock_lot_id: formNullableNumber(f, 'stock_lot_id'), movement_type: formString(f, 'movement_type'), quantity: Number(formString(f, 'quantity')), expiry_date: formNullable(f, 'expiry_date'), notes: formString(f, 'notes') }) }
  const warehouseLabels = Object.fromEntries((warehouses.data ?? []).map((w) => [String(w.id), `${w.code} - ${w.name}`]))
  const itemLabels = Object.fromEntries((items.data ?? []).map((item) => [String(item.id), `${item.sku} - ${item.name}`]))

  return (
    <ModulePage description="Review stock activity, receive new lots, and record controlled adjustments." title="Stock Movements">
      <Panel icon={<ListChecks size={20} />} title="Stock Movements">
        <RecordList
          isError={stockMovements.isError}
          isLoading={stockMovements.isPending}
          items={stockMovements.data?.data}
          label="stock movements"
          render={(movement) => `${movement.movement_type} - ${movement.inventory_item?.sku ?? 'Unknown item'} - ${movement.quantity} ${movement.inventory_item?.unit ?? ''} - ${movement.warehouse?.code ?? 'No warehouse'} - ${formatDate(movement.created_at)}`}
        />
        <PaginationControls meta={stockMovements.data?.meta} pagination={pagination} />
      </Panel>
      {me.permissions?.includes('stock_lots.receive') ? <Panel icon={<Boxes size={20} />} title="Receive Stock"><form className="space-y-4" onSubmit={receiveSubmit}><FormGrid><SelectField label="Warehouse" name="warehouse_id" options={(warehouses.data ?? []).map((w) => String(w.id))} optionLabels={warehouseLabels} required /><SelectField label="Inventory item" name="inventory_item_id" options={(items.data ?? []).map((i) => String(i.id))} optionLabels={itemLabels} required /><TextField label="Quantity" min="0.001" name="quantity" step="0.001" type="number" required /><SelectField label="Source" name="source_type" options={['opening_balance', 'purchase', 'donation_in_kind', 'transfer', 'adjustment', 'other']} /><TextField label="Source ID" min="1" name="source_id" type="number" /><TextField label="Expiry date" name="expiry_date" type="date" /><TextField defaultValue={new Date().toISOString().slice(0, 16)} label="Received at" name="received_at" type="datetime-local" required /></FormGrid><TextAreaField label="Notes" name="notes" /><FormFooter isPending={receive.isPending} submitLabel="Receive stock" /><MutationState isError={receive.isError} isSuccess={receive.isSuccess} /></form></Panel> : null}
      {me.permissions?.includes('stock_movements.adjust') ? <Panel icon={<ListChecks size={20} />} title="Adjust Stock"><form className="space-y-4" onSubmit={adjustSubmit}><FormGrid><SelectField label="Warehouse" name="warehouse_id" options={(warehouses.data ?? []).map((w) => String(w.id))} optionLabels={warehouseLabels} required /><SelectField label="Inventory item" name="inventory_item_id" options={(items.data ?? []).map((i) => String(i.id))} optionLabels={itemLabels} required /><SelectField label="Stock lot (required for stock out)" name="stock_lot_id" options={['', ...(lots.data ?? []).map((l) => String(l.id))]} optionLabels={Object.fromEntries((lots.data ?? []).map((l) => [String(l.id), `#${l.id} - ${l.inventory_item?.sku} - ${l.remaining_quantity}`]))} /><SelectField label="Movement" name="movement_type" options={['adjustment_in', 'adjustment_out', 'damaged', 'expired']} /><TextField label="Quantity" min="0.001" name="quantity" step="0.001" type="number" required /><TextField label="Expiry date (new inbound lot)" name="expiry_date" type="date" /></FormGrid><TextAreaField label="Reason / notes" name="notes" required /><FormFooter isPending={adjust.isPending} submitLabel="Record adjustment" /><MutationState isError={adjust.isError} isSuccess={adjust.isSuccess} /></form></Panel> : null}
    </ModulePage>
  )
}

function LowStockPage() {
  const pagination = useListPagination()
  const lowStock = useQuery({ queryKey: ['stock-low-stock', pagination.params], queryFn: () => getLowStockPage(pagination.params) })

  return (
    <ModulePage description="Review items under configured minimum stock levels." title="Low Stock" planned={['Add links to receive stock and item detail pages.']}>
      <Panel icon={<ClipboardCheck size={20} />} title="Low Stock">
        <RecordList isError={lowStock.isError} isLoading={lowStock.isPending} items={lowStock.data?.data} label="low stock rows" render={(row) => `${row.sku} - ${row.available_quantity}/${row.minimum_stock_level} ${row.unit}`} />
        <PaginationControls meta={lowStock.data?.meta} pagination={pagination} />
      </Panel>
    </ModulePage>
  )
}

function ExpiringStockPage() {
  const pagination = useListPagination()
  const expiringStock = useQuery({ queryKey: ['stock-expiring', pagination.params], queryFn: () => getExpiringStockPage(pagination.params) })

  return (
    <ModulePage description="Review stock lots expiring soon." title="Expiring Stock" planned={['Add expiry window filter.', 'Add links to affected lots and warehouses.']}>
      <Panel icon={<CalendarClock size={20} />} title="Expiring Stock">
        <RecordList
          isError={expiringStock.isError}
          isLoading={expiringStock.isPending}
          items={expiringStock.data?.data}
          label="expiring stock lots"
          render={(lot) => `${lot.inventory_item?.sku ?? 'Unknown item'} - ${lot.remaining_quantity} ${lot.inventory_item?.unit ?? ''} - ${lot.warehouse?.code ?? 'No warehouse'} - expires ${lot.expiry_date ?? '-'}`}
        />
        <PaginationControls meta={expiringStock.data?.meta} pagination={pagination} />
      </Panel>
    </ModulePage>
  )
}

function AidBatchesPage() {
  const { me } = useOutletContext<{ me: CurrentUser }>()
  const queryClient = useQueryClient()
  const pagination = useListPagination()
  const distributionsPagination = useListPagination('distributions_')
  const eligiblePagination = useListPagination('eligible_')
  const aidBatches = useQuery({ queryKey: ['aid-batches', pagination.params], queryFn: () => getAidBatchesPage(pagination.params) })
  const branches = useQuery({ queryKey: ['branches'], queryFn: getBranches })
  const warehouses = useQuery({ queryKey: ['warehouses'], queryFn: getWarehouses })
  const campaigns = useQuery({ queryKey: ['campaigns'], queryFn: getCampaigns })
  const cases = useQuery({ queryKey: ['case-files'], queryFn: getCaseFiles })
  const [selectedId, setSelectedId] = useState<number | null>(null)
  const selected = useQuery({ queryKey: ['aid-batch', selectedId], queryFn: () => getAidBatch(selectedId!), enabled: selectedId !== null })
  const distributions = useQuery({ queryKey: ['aid-batch-distributions', selectedId, distributionsPagination.params], queryFn: () => getAidBatchDistributionsPage(selectedId!, distributionsPagination.params), enabled: selectedId !== null })
  const eligible = useQuery({ queryKey: ['aid-batch-eligible', selectedId, eligiblePagination.params], queryFn: () => getEligibleBeneficiariesPage(selectedId!, eligiblePagination.params), enabled: selectedId !== null })
  const stockCheck = useQuery({ queryKey: ['aid-batch-stock-check', selectedId], queryFn: () => getAidBatchStockCheck(selectedId!), enabled: false })
  const save = useMutation({ mutationFn: ({ id, input }: { id?: number; input: AidBatchInput }) => id ? updateAidBatch(id, input) : createAidBatch(input), onSuccess: (batch) => { setSelectedId(batch.id); refresh() } })
  const remove = useMutation({ mutationFn: deleteAidBatch, onSuccess: () => { setSelectedId(null); refresh() } })
  const workflow = useMutation({ mutationFn: ({ id, action }: { id: number; action: 'submit-approval' | 'approve' | 'cancel' | 'complete' }) => runAidBatchAction(id, action), onSuccess: refresh })
  const addDistribution = useMutation({ mutationFn: ({ batchId, input }: { batchId: number; input: Parameters<typeof createAidDistribution>[1] }) => createAidDistribution(batchId, input), onSuccess: refresh })

  function refresh() { void queryClient.invalidateQueries({ queryKey: ['aid-batches'] }); void queryClient.invalidateQueries({ queryKey: ['aid-batch', selectedId] }); void queryClient.invalidateQueries({ queryKey: ['aid-batch-eligible', selectedId] }); void queryClient.invalidateQueries({ queryKey: ['aid-batch-distributions', selectedId] }) }
  function batchSubmit(event: FormEvent<HTMLFormElement>) { event.preventDefault(); const f = new FormData(event.currentTarget); save.mutate({ id: selected.data?.status === 'draft' ? selected.data.id : undefined, input: { branch_id: formNullableNumber(f, 'branch_id'), warehouse_id: formNullableNumber(f, 'warehouse_id'), title: formString(f, 'title'), description: formNullable(f, 'description'), campaign_id: formNullableNumber(f, 'campaign_id'), scheduled_date: formNullable(f, 'scheduled_date') } }) }
  function distributionSubmit(event: FormEvent<HTMLFormElement>) { event.preventDefault(); if (!selectedId) return; const f = new FormData(event.currentTarget); addDistribution.mutate({ batchId: selectedId, input: { beneficiary_id: Number(formString(f, 'beneficiary_id')), case_file_id: formNullableNumber(f, 'case_file_id'), scheduled_at: formNullable(f, 'scheduled_at'), delivery_method: formString(f, 'delivery_method'), notes: formNullable(f, 'notes') } }) }
  const selectLabels = (records: { id: number; code?: string; name?: string; title?: string }[]) => Object.fromEntries(records.map((r) => [String(r.id), r.title ?? `${r.code ?? ''} ${r.name ?? ''}`.trim()]))

  return (
    <ModulePage description="Plan aid batches, add eligible beneficiaries and items, verify stock, approve, and complete deliveries." title="Aid Batches">
      <Panel icon={<Truck size={20} />} title="Aid Batches">
        <RecordList
          isError={aidBatches.isError}
          isLoading={aidBatches.isPending}
          items={aidBatches.data?.data}
          label="aid batches"
          render={(batch) => (
            <button className="w-full text-left" onClick={() => setSelectedId(batch.id)} type="button">
              <StatusBadge status={batch.status} /> {batch.batch_number} - {batch.title} - {batch.distributions_count ?? 0} distributions - {batch.warehouse?.code ?? 'No warehouse'}
            </button>
          )}
        />
        <PaginationControls meta={aidBatches.data?.meta} pagination={pagination} />
      </Panel>
      {me.permissions?.some((p) => ['aid_batches.create', 'aid_batches.update'].includes(p)) ? <Panel icon={<Truck size={20} />} title={selected.data?.status === 'draft' ? `Edit ${selected.data.batch_number}` : 'Create Aid Batch'}><form className="space-y-4" key={selected.data?.id ?? 'new'} onSubmit={batchSubmit}><FormGrid><TextField defaultValue={selected.data?.status === 'draft' ? selected.data.title : ''} label="Title" name="title" required /><TextField defaultValue={selected.data?.status === 'draft' ? selected.data.scheduled_date ?? '' : ''} label="Scheduled date" name="scheduled_date" type="date" /><SelectField defaultValue={String(selected.data?.branch_id ?? '')} label="Branch" name="branch_id" options={['', ...(branches.data ?? []).map((x) => String(x.id))]} optionLabels={selectLabels(branches.data ?? [])} /><SelectField defaultValue={String(selected.data?.warehouse_id ?? '')} label="Warehouse" name="warehouse_id" options={['', ...(warehouses.data ?? []).map((x) => String(x.id))]} optionLabels={selectLabels(warehouses.data ?? [])} /><SelectField defaultValue={String(selected.data?.campaign_id ?? '')} label="Campaign" name="campaign_id" options={['', ...(campaigns.data ?? []).map((x) => String(x.id))]} optionLabels={selectLabels(campaigns.data ?? [])} /></FormGrid><TextAreaField defaultValue={selected.data?.status === 'draft' ? selected.data.description ?? '' : ''} label="Description" name="description" /><FormFooter isPending={save.isPending} onCancel={selected.data ? () => setSelectedId(null) : undefined} submitLabel={selected.data?.status === 'draft' ? 'Save batch' : 'Create batch'} /><MutationState isError={save.isError || remove.isError} isSuccess={save.isSuccess || remove.isSuccess} />{selected.data?.status === 'draft' && me.permissions?.includes('aid_batches.delete') ? <SmallButton danger onClick={() => { if (confirm('Delete this draft batch?')) remove.mutate(selected.data!.id) }}>Delete batch</SmallButton> : null}</form></Panel> : null}
      {selected.data ? <Panel icon={<ListChecks size={20} />} title={`${selected.data.batch_number} Workflow`}><div className="space-y-4"><KeyValueRows rows={[[ 'Status', selected.data.status ], ['Scheduled', selected.data.scheduled_date], ['Distributions', String(selected.data.distributions_count ?? selected.data.distributions?.length ?? 0)], ['Reservations', String(selected.data.reservations_count ?? 0)]]} /><div className="flex flex-wrap gap-2">{selected.data.status === 'draft' && me.permissions?.includes('aid_batches.submit_approval') ? <SmallButton onClick={() => workflow.mutate({ id: selected.data!.id, action: 'submit-approval' })}>Submit approval</SmallButton> : null}{selected.data.status === 'pending_approval' && me.permissions?.includes('aid_batches.approve') ? <SmallButton onClick={() => workflow.mutate({ id: selected.data!.id, action: 'approve' })}>Approve & reserve stock</SmallButton> : null}{!['cancelled', 'completed'].includes(selected.data.status) && me.permissions?.includes('aid_batches.cancel') ? <SmallButton danger onClick={() => { if (confirm('Cancel this batch?')) workflow.mutate({ id: selected.data!.id, action: 'cancel' }) }}>Cancel</SmallButton> : null}{selected.data.status === 'in_progress' && me.permissions?.includes('aid_batches.complete') ? <SmallButton onClick={() => workflow.mutate({ id: selected.data!.id, action: 'complete' })}>Complete</SmallButton> : null}<SmallButton onClick={() => void stockCheck.refetch()}>Run stock check</SmallButton></div>{stockCheck.data ? <JsonBlock label="Stock check" value={stockCheck.data} /> : null}<MutationState isError={workflow.isError || stockCheck.isError} isSuccess={workflow.isSuccess} /></div></Panel> : null}
      {selected.data && ['draft', 'pending_approval'].includes(selected.data.status) && me.permissions?.includes('aid_distributions.create') ? <Panel icon={<Users size={20} />} title="Add Distribution"><form className="space-y-4" onSubmit={distributionSubmit}><FormGrid><SelectField label="Eligible beneficiary" name="beneficiary_id" options={(eligible.data?.data ?? []).map((b) => String(b.id))} optionLabels={Object.fromEntries((eligible.data?.data ?? []).map((b) => [String(b.id), `${b.code} - ${b.full_name}`]))} required /><SelectField label="Case file" name="case_file_id" options={['', ...(cases.data ?? []).map((c) => String(c.id))]} optionLabels={Object.fromEntries((cases.data ?? []).map((c) => [String(c.id), c.case_number]))} /><TextField label="Scheduled at" name="scheduled_at" type="datetime-local" /><SelectField label="Delivery method" name="delivery_method" options={['pickup', 'home_delivery', 'field_visit', 'partner_delivery', 'other']} /></FormGrid><TextAreaField label="Notes" name="notes" /><FormFooter isPending={addDistribution.isPending} submitLabel="Add distribution" /><MutationState isError={addDistribution.isError} isSuccess={addDistribution.isSuccess} /></form><PaginationControls meta={eligible.data?.meta} pagination={eligiblePagination} /></Panel> : null}
      {(distributions.data?.data ?? []).map((distribution) => <DistributionControl batch={selected.data!} distribution={distribution} key={distribution.id} onChanged={refresh} />)}
      {selected.data ? <div className="lg:col-span-2"><PaginationControls meta={distributions.data?.meta} pagination={distributionsPagination} /></div> : null}
    </ModulePage>
  )
}

function AidDistributionsPage() {
  const batches = useQuery({ queryKey: ['aid-batches'], queryFn: getAidBatches })
  const pagination = useListPagination()
  const [batchId, setBatchId] = useState<number | null>(null)
  const batch = useQuery({ queryKey: ['aid-batch', batchId], queryFn: () => getAidBatch(batchId!), enabled: batchId !== null })
  const distributions = useQuery({ queryKey: ['aid-batch-distributions', batchId, pagination.params], queryFn: () => getAidBatchDistributionsPage(batchId!, pagination.params), enabled: batchId !== null })
  return (
    <ModulePage description="Select a batch to manage its delivery records, items, failures, rescheduling, and proof." title="Aid Distributions"><Panel icon={<Truck size={20} />} title="Select Batch"><SelectField defaultValue="" label="Aid batch" name="batch" onChange={(event) => setBatchId(Number(event.target.value) || null)} options={['', ...(batches.data ?? []).map((b) => String(b.id))]} optionLabels={Object.fromEntries((batches.data ?? []).map((b) => [String(b.id), `${b.batch_number} - ${b.title}`]))} /></Panel>{(distributions.data?.data ?? []).map((distribution) => <DistributionControl batch={batch.data!} distribution={distribution} key={distribution.id} onChanged={() => void distributions.refetch()} />)}{batch.data ? <div className="lg:col-span-2"><PaginationControls meta={distributions.data?.meta} pagination={pagination} /></div> : null}</ModulePage>
  )
}

function DistributionControl({ batch, distribution, onChanged }: { batch: AidBatch; distribution: AidDistribution; onChanged: () => void }) {
  const { me } = useOutletContext<{ me: CurrentUser }>()
  const itemsPagination = useListPagination(`items_${distribution.id}_`)
  const items = useQuery({ queryKey: ['inventory-items'], queryFn: getInventoryItems })
  const lots = useQuery({ queryKey: ['stock-lots'], queryFn: getStockLots })
  const detail = useQuery({ queryKey: ['aid-distribution', distribution.id], queryFn: () => getAidDistribution(distribution.id) })
  const distributionItems = useQuery({ queryKey: ['distribution-items', distribution.id, itemsPagination.params], queryFn: () => getDistributionItemsPage(distribution.id, itemsPagination.params) })
  const addItem = useMutation({ mutationFn: (input: Parameters<typeof createDistributionItem>[1]) => createDistributionItem(distribution.id, input), onSuccess: changed })
  const removeItem = useMutation({ mutationFn: (id: number) => deleteDistributionItem(distribution.id, id), onSuccess: changed })
  const removeDistribution = useMutation({ mutationFn: () => deleteAidDistribution(batch.id, distribution.id), onSuccess: onChanged })
  const failed = useMutation({ mutationFn: ({ reason, notes }: { reason: string; notes?: string }) => markDistributionFailed(distribution.id, reason, notes), onSuccess: changed })
  const reschedule = useMutation({ mutationFn: ({ date, notes }: { date: string; notes?: string }) => rescheduleDistribution(distribution.id, date, notes), onSuccess: changed })
  const proof = useMutation({ mutationFn: ({ form, delivered }: { form: FormData; delivered: boolean }) => submitDistributionProof(distribution.id, form, delivered), onSuccess: changed })
  function changed() { void detail.refetch(); void distributionItems.refetch(); onChanged() }
  function itemSubmit(event: FormEvent<HTMLFormElement>) { event.preventDefault(); const f = new FormData(event.currentTarget); addItem.mutate({ inventory_item_id: formNullableNumber(f, 'inventory_item_id'), stock_lot_id: formNullableNumber(f, 'stock_lot_id'), quantity: formNullableNumber(f, 'quantity'), cash_amount: formNullableNumber(f, 'cash_amount'), currency: formNullable(f, 'currency'), notes: formNullable(f, 'notes') }) }
  function deliverySubmit(event: FormEvent<HTMLFormElement>) { event.preventDefault(); const f = new FormData(event.currentTarget); const action = formString(f, 'action'); if (action === 'failed') failed.mutate({ reason: formString(f, 'failure_reason'), notes: formString(f, 'notes') }); else if (action === 'reschedule') reschedule.mutate({ date: formString(f, 'scheduled_at'), notes: formString(f, 'notes') }); else proof.mutate({ form: f, delivered: action === 'delivered' }) }
  const current = detail.data ?? distribution
  return <Panel icon={<ClipboardCheck size={20} />} title={`${current.distribution_number} — ${current.beneficiary?.full_name ?? 'Beneficiary'}`}><div className="space-y-4"><KeyValueRows rows={[[ 'Status', current.status ], ['Method', current.delivery_method], ['Scheduled', current.scheduled_at ? formatDate(current.scheduled_at) : null], ['Delivered', current.delivered_at ? formatDate(current.delivered_at) : null], ['Failure', current.failure_reason]]} />
    <RecordList isError={distributionItems.isError} isLoading={distributionItems.isPending} items={distributionItems.data?.data} label="distribution items" render={(item) => <div className="flex items-center justify-between gap-2"><span>{item.inventory_item ? `${item.inventory_item.sku} — ${item.quantity} ${item.inventory_item.unit}` : `${item.cash_amount} ${item.currency}`}</span>{me.permissions?.includes('aid_distributions.update') && ['draft', 'pending_approval'].includes(current.status) ? <SmallButton danger onClick={() => removeItem.mutate(item.id)}>Remove</SmallButton> : null}</div>} />
    <PaginationControls meta={distributionItems.data?.meta} pagination={itemsPagination} />
    {me.permissions?.includes('aid_distributions.update') && ['draft', 'pending_approval'].includes(current.status) ? <form className="space-y-3 rounded-md border border-[#d9e1de] p-3" onSubmit={itemSubmit}><p className="text-sm font-semibold">Add inventory or cash item</p><FormGrid><SelectField label="Inventory item" name="inventory_item_id" options={['', ...(items.data ?? []).map((i) => String(i.id))]} optionLabels={Object.fromEntries((items.data ?? []).map((i) => [String(i.id), `${i.sku} - ${i.name}`]))} /><SelectField label="Preferred stock lot" name="stock_lot_id" options={['', ...(lots.data ?? []).map((l) => String(l.id))]} optionLabels={Object.fromEntries((lots.data ?? []).map((l) => [String(l.id), `#${l.id} - ${l.inventory_item?.sku} (${l.remaining_quantity})`]))} /><TextField label="Quantity" min="0.001" name="quantity" step="0.001" type="number" /><TextField label="Cash amount" min="0.01" name="cash_amount" step="0.01" type="number" /><TextField label="Currency" maxLength={3} name="currency" /></FormGrid><TextField label="Notes" name="notes" /><FormFooter isPending={addItem.isPending} submitLabel="Add item" /></form> : null}
    {['approved', 'scheduled', 'in_progress'].includes(current.status) && me.permissions?.some((p) => ['aid_distributions.deliver', 'aid_distributions.fail', 'aid_distributions.reschedule', 'delivery_proofs.upload'].includes(p)) ? <form className="space-y-3 rounded-md border border-[#d9e1de] p-3" onSubmit={deliverySubmit}><p className="text-sm font-semibold">Delivery action</p><FormGrid><SelectField label="Action" name="action" options={['delivered', 'proof', 'failed', 'reschedule']} /><SelectField label="Proof type" name="proof_type" options={['manual', 'photo', 'signature', 'otp', 'qr']} /><TextField accept=".jpg,.jpeg,.png,.pdf" label="Proof file" name="file" type="file" /><TextField label="OTP code" name="otp_code" /><TextField label="Failure reason" name="failure_reason" /><TextField label="New schedule" name="scheduled_at" type="datetime-local" /></FormGrid><TextAreaField label="Notes" name="notes" /><FormFooter isPending={failed.isPending || reschedule.isPending || proof.isPending} submitLabel="Apply delivery action" /></form> : null}
    {['draft', 'pending_approval'].includes(current.status) && me.permissions?.includes('aid_distributions.delete') ? <SmallButton danger onClick={() => { if (confirm('Delete this distribution?')) removeDistribution.mutate() }}>Delete distribution</SmallButton> : null}<MutationState isError={addItem.isError || removeItem.isError || removeDistribution.isError || failed.isError || reschedule.isError || proof.isError} isSuccess={addItem.isSuccess || failed.isSuccess || reschedule.isSuccess || proof.isSuccess} /></div></Panel>
}

function ReportsPage() {
  const queryClient = useQueryClient()
  const pagination = useListPagination()
  const [reportType, setReportType] = useState<ReportType>('donations')
  const [filters, setFilters] = useState<Record<string, string>>({})
  const report = useQuery({ queryKey: ['report', reportType, filters], queryFn: () => getReport(reportType, filters) })
  const exports = useQuery({ queryKey: ['exports', pagination.params], queryFn: () => getExportsPage(pagination.params) })
  const exportMutation = useMutation({ mutationFn: () => createExport(reportType, filters), onSuccess: () => void queryClient.invalidateQueries({ queryKey: ['exports'] }) })

  function filterSubmit(event: FormEvent<HTMLFormElement>) { event.preventDefault(); const f = new FormData(event.currentTarget); setFilters(Object.fromEntries(['date_from', 'date_to', 'status'].map((key) => [key, formString(f, key)]).filter(([, value]) => value))) }

  return (
    <ModulePage description="Run filtered operational reports, inspect their complete payload, and create downloadable CSV exports." title="Reports & Exports">
      <Panel icon={<FileBarChart size={20} />} title="Report Runner"><form className="space-y-4" onSubmit={filterSubmit}><SelectField defaultValue={reportType} label="Report" name="report_type" onChange={(e) => setReportType(e.target.value as ReportType)} options={[...reportTypes]} optionLabels={{ 'case-files': 'Case files', 'audit-logs': 'Audit logs' }} /><FormGrid><TextField label="From" name="date_from" type="date" /><TextField label="To" name="date_to" type="date" /><TextField label="Status" name="status" /></FormGrid><FormFooter isPending={report.isFetching} submitLabel="Run report" /><SmallButton onClick={() => exportMutation.mutate()}>Create CSV export</SmallButton><MutationState isError={report.isError || exportMutation.isError} isSuccess={exportMutation.isSuccess} /></form>
      </Panel>
      <Panel icon={<ListChecks size={20} />} title={`${reportType} Report`}>{report.data ? <JsonBlock label="Report data" value={report.data} /> : <LoadingOrEmpty isError={report.isError} isLoading={report.isPending} label="Loading report" />}</Panel>
      <Panel icon={<FileText size={20} />} title="Exports"><RecordList isError={exports.isError} isLoading={exports.isPending} items={exports.data?.data} label="exports" render={(record) => <div className="flex items-center justify-between gap-2"><span><StatusBadge status={record.status} /> {record.report_type} — {formatDate(record.created_at)}</span>{record.status === 'completed' ? <SmallButton onClick={() => void downloadExport(record)}>Download</SmallButton> : null}</div>} /><PaginationControls meta={exports.data?.meta} pagination={pagination} /></Panel>
    </ModulePage>
  )
}

function PublicPortalSettingsPage() {
  const { me } = useOutletContext<{ me: CurrentUser }>()
  const settings = useQuery({ queryKey: ['public-portal-settings'], queryFn: getPublicPortalSettings })
  const queryClient = useQueryClient()
  const save = useMutation({ mutationFn: updatePublicPortalSettings, onSuccess: () => void queryClient.invalidateQueries({ queryKey: ['public-portal-settings'] }) })
  function submit(event: FormEvent<HTMLFormElement>) { event.preventDefault(); const f = new FormData(event.currentTarget); save.mutate({ enabled: f.get('enabled') === 'on', show_donation_totals: f.get('show_donation_totals') === 'on', show_campaign_progress: f.get('show_campaign_progress') === 'on', show_completed_campaigns: f.get('show_completed_campaigns') === 'on', show_contact_info: f.get('show_contact_info') === 'on', donations_enabled: f.get('donations_enabled') === 'on', reports_enabled: f.get('reports_enabled') === 'on', contact_email: formNullable(f, 'contact_email'), contact_phone: formNullable(f, 'contact_phone'), about: formNullable(f, 'about') }) }
  if (settings.isPending) return <LoadingState label="Loading public portal settings" />
  return <ModulePage description="Control exactly what the public transparency portal exposes." title="Public Portal Settings"><Panel icon={<Settings size={20} />} title="Visibility & Contact"><form className="space-y-4" key={String(settings.data?.enabled)} onSubmit={submit}>
    <div className="grid gap-2 md:grid-cols-2">{['enabled', 'show_donation_totals', 'show_campaign_progress', 'show_completed_campaigns', 'show_contact_info', 'donations_enabled', 'reports_enabled'].map((key) => <label className="flex items-center gap-2 rounded-md border border-[#d9e1de] p-3 text-sm" key={key}><input defaultChecked={Boolean(settings.data?.[key as keyof typeof settings.data])} name={key} type="checkbox" /> {key.replaceAll('_', ' ')}</label>)}</div><FormGrid><TextField defaultValue={settings.data?.contact_email ?? ''} label="Contact email" name="contact_email" type="email" /><TextField defaultValue={settings.data?.contact_phone ?? ''} label="Contact phone" name="contact_phone" /></FormGrid><TextAreaField defaultValue={settings.data?.about ?? ''} label="About" name="about" />{me.permissions?.includes('public_portal_settings.update') ? <FormFooter isPending={save.isPending} submitLabel="Save public settings" /> : null}<MutationState isError={save.isError} isSuccess={save.isSuccess} /><a className="inline-block text-sm font-medium text-[#236b55] underline" href="/public" target="_blank" rel="noreferrer">Open public portal preview</a></form></Panel></ModulePage>
}

function NotificationsPage() {
  const { me } = useOutletContext<{ me: CurrentUser }>()
  const queryClient = useQueryClient()
  const pagination = useListPagination()
  const notifications = useQuery({ queryKey: ['notifications', pagination.params], queryFn: () => getNotificationsPage(pagination.params) })
  const preferences = useQuery({ queryKey: ['notification-preferences'], queryFn: getNotificationPreferences })
  const markRead = useMutation({ mutationFn: markNotificationRead, onSuccess: refresh })
  const markAll = useMutation({ mutationFn: markAllNotificationsRead, onSuccess: refresh })
  const savePreferences = useMutation({ mutationFn: updateNotificationPreferences, onSuccess: refresh })
  function refresh() { void queryClient.invalidateQueries({ queryKey: ['notifications'] }); void queryClient.invalidateQueries({ queryKey: ['notification-preferences'] }); void queryClient.invalidateQueries({ queryKey: ['notifications-unread-count'] }) }
  function preferenceSubmit(event: FormEvent<HTMLFormElement>) { event.preventDefault(); const f = new FormData(event.currentTarget); savePreferences.mutate((preferences.data ?? []).map((p) => ({ category: p.category, database_enabled: f.get(`${p.category}.database`) === 'on', email_enabled: f.get(`${p.category}.email`) === 'on' }))) }

  return (
    <ModulePage description="Review operational notifications, mark them read, and manage channel preferences." title="Notifications">
      <Panel icon={<Bell size={20} />} title="Recent Notifications">
        {me.permissions?.includes('notifications.update') ? <div className="mb-3"><SmallButton onClick={() => markAll.mutate()}>Mark all read</SmallButton></div> : null}
        <RecordList
          isError={notifications.isError}
          isLoading={notifications.isPending}
          items={notifications.data?.data}
          label="notifications"
          render={(notification) => (
            <div className="flex items-start justify-between gap-2"><span>
              <StatusBadge status={notification.severity} /> {notification.title} - {notification.category} - {notification.read_at ? 'read' : 'unread'}
            </span>{!notification.read_at && me.permissions?.includes('notifications.update') ? <SmallButton onClick={() => markRead.mutate(notification.id)}>Mark read</SmallButton> : null}</div>
          )}
        />
        <PaginationControls meta={notifications.data?.meta} pagination={pagination} />
      </Panel>
      <Panel icon={<Settings size={20} />} title="Notification Preferences">
        {preferences.isPending ? <LoadingState label="Loading notification preferences" /> : <form className="space-y-3" onSubmit={preferenceSubmit}>{(preferences.data ?? []).map((p) => <div className="grid grid-cols-3 items-center gap-2 rounded-md border border-[#d9e1de] p-3 text-sm" key={p.category}><span className="font-medium">{p.category}</span><label><input defaultChecked={p.database_enabled} name={`${p.category}.database`} type="checkbox" /> In-app</label><label><input defaultChecked={p.email_enabled} name={`${p.category}.email`} type="checkbox" /> Email</label></div>)}{me.permissions?.includes('notification_preferences.update') ? <FormFooter isPending={savePreferences.isPending} submitLabel="Save preferences" /> : null}<MutationState isError={savePreferences.isError} isSuccess={savePreferences.isSuccess} /></form>}
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
  const { me } = useOutletContext<{ me: CurrentUser }>()
  const location = useLocation()
  const writePermissions = panelWritePermissions(location.pathname, title)

  if (writePermissions && !canAccessAny(me.permissions, writePermissions)) {
    return null
  }

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

export type ListPaginationState = {
  page: number
  perPage: number
  search: string
  params: PaginationParams
  setPage: (value: number) => void
  setPerPage: (value: number) => void
  setSearch: (value: string) => void
  setFilter: (key: string, value: string) => void
}

function useListPagination(prefix = ''): ListPaginationState {
  const [searchParams, setSearchParams] = useSearchParams()
  const pageKey = `${prefix}page`
  const perPageKey = `${prefix}per_page`
  const searchKey = `${prefix}search`
  const { page, perPage, search } = readPagination(searchParams, prefix)
  const filterKeys = ['status', 'branch_id', 'warehouse_id', 'campaign_id', 'category', 'date_from', 'date_to', 'severity', 'movement_type']
  const filters = Object.fromEntries(filterKeys.map((key) => [key, searchParams.get(`${prefix}${key}`)]).filter(([, value]) => value))
  const params: PaginationParams = { page, per_page: perPage, ...(search ? { search } : {}), ...filters }
  const update = (next: { page?: number; perPage?: number; search?: string }) => {
    setSearchParams((current) => {
      const updated = new URLSearchParams(current)
      updated.set(pageKey, String(next.page ?? page))
      updated.set(perPageKey, String(next.perPage ?? perPage))
      if (next.search !== undefined) {
        if (next.search) updated.set(searchKey, next.search)
        else updated.delete(searchKey)
      }
      return updated
    })
  }

  const setFilter = (key: string, value: string) => setSearchParams((current) => {
    const updated = new URLSearchParams(current)
    if (value) updated.set(`${prefix}${key}`, value)
    else updated.delete(`${prefix}${key}`)
    updated.set(pageKey, '1')
    return updated
  })

  return { page, perPage, search, params, setPage: (value: number) => update({ page: value }), setPerPage: (value: number) => update({ page: 1, perPage: value }), setSearch: (value: string) => update({ page: 1, search: value }), setFilter }
}

export function PaginationControls({ meta, pagination }: { meta: PaginationMeta | undefined; pagination: ListPaginationState }) {
  const [searchValue, setSearchValue] = useState(pagination.search)

  useEffect(() => setSearchValue(pagination.search), [pagination.search])
  useEffect(() => {
    if (meta && meta.last_page > 0 && pagination.page > meta.last_page) pagination.setPage(meta.last_page)
  }, [meta, pagination])

  return (
    <nav aria-label="Pagination" className="mt-4 space-y-3 border-t border-[#edf1ef] pt-4 text-sm">
      <form className="flex gap-2" onSubmit={(event) => { event.preventDefault(); pagination.setSearch(searchValue.trim()) }}><input aria-label="Search records" className="min-w-0 flex-1 rounded-md border border-[#c8d4cf] bg-white px-3 py-2" onChange={(event) => setSearchValue(event.target.value)} placeholder="Search records" value={searchValue} /><button className="rounded-md border border-[#c8d4cf] bg-white px-3 py-2 font-medium text-[#29483d]" type="submit">Search</button>{pagination.search ? <button className="rounded-md border border-[#c8d4cf] bg-white px-3 py-2 text-[#52645e]" onClick={() => { setSearchValue(''); pagination.setSearch('') }} type="button">Clear</button> : null}</form>
      {meta && meta.total > 0 ? <div className="flex flex-wrap items-center justify-between gap-3"><p className="text-[#52645e]">{meta.from ?? 0}-{meta.to ?? 0} of {meta.total}</p>
      <div className="flex flex-wrap items-center gap-2">
        <label className="flex items-center gap-2 text-[#52645e]">Rows
          <select aria-label="Rows per page" className="rounded-md border border-[#c8d4cf] bg-white px-2 py-1.5" onChange={(event) => pagination.setPerPage(Number(event.target.value))} value={pagination.perPage}>
            {[15, 25, 50, 100].map((size) => <option key={size} value={size}>{size}</option>)}
          </select>
        </label>
        <button className="rounded-md border border-[#c8d4cf] px-2 py-1.5 disabled:opacity-40" disabled={meta.current_page <= 1} onClick={() => pagination.setPage(1)} type="button">First</button>
        <button className="rounded-md border border-[#c8d4cf] px-2 py-1.5 disabled:opacity-40" disabled={meta.current_page <= 1} onClick={() => pagination.setPage(meta.current_page - 1)} type="button">Previous</button>
        <span className="min-w-24 text-center text-[#29483d]">Page {meta.current_page} of {meta.last_page}</span>
        <button className="rounded-md border border-[#c8d4cf] px-2 py-1.5 disabled:opacity-40" disabled={meta.current_page >= meta.last_page} onClick={() => pagination.setPage(meta.current_page + 1)} type="button">Next</button>
        <button className="rounded-md border border-[#c8d4cf] px-2 py-1.5 disabled:opacity-40" disabled={meta.current_page >= meta.last_page} onClick={() => pagination.setPage(meta.last_page)} type="button">Last</button>
      </div></div> : <p className="text-[#52645e]">No matching records</p>}
    </nav>
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
  const { me } = useOutletContext<{ me: CurrentUser }>()
  const location = useLocation()
  const permission = formPermission(location.pathname, submitLabel)

  if (permission && !me.permissions?.includes(permission)) {
    return null
  }

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

function formPermission(path: string, label: string) {
  const module = path.split('/').filter(Boolean)[0]
  if (module === 'organization') return 'organization.update'
  if (/family member/i.test(label)) return 'beneficiary_family.manage'
  if (/note/i.test(label)) return 'case_notes.create'
  if (/document/i.test(label)) return 'case_documents.upload'
  if (/allocation/i.test(label)) return 'donation_allocations.manage'
  const action = /^(Create|Invite|Add|Upload)/i.test(label) ? 'create' : 'update'
  const map: Record<string, string> = { branches: 'branches', users: 'users', roles: 'roles', beneficiaries: 'beneficiaries', 'case-files': 'case_files', donors: 'donors', campaigns: 'campaigns', donations: 'donations', warehouses: 'warehouses', 'inventory-items': 'inventory_items', 'aid-batches': 'aid_batches' }
  return map[module] ? `${map[module]}.${action}` : undefined
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
  const { me } = useOutletContext<{ me: CurrentUser }>()
  const location = useLocation()
  const permission = buttonPermission(location.pathname, typeof children === 'string' ? children : '')

  if (permission && !me.permissions?.includes(permission)) {
    return null
  }

  return (
    <button className={`rounded-md border px-3 py-1.5 text-xs font-medium ${danger ? 'border-[#e8b8b0] bg-[#fff1ef] text-[#a44a3f]' : 'border-[#c8d4cf] bg-white text-[#29483d]'}`} onClick={onClick} type="button">
      {children}
    </button>
  )
}

function panelWritePermissions(path: string, title: string) {
  if (!/^(Create|Edit) /.test(title)) return null
  const module = path.split('/').filter(Boolean)[0]
  const map: Record<string, string[]> = {
    organization: ['organization.update'],
    branches: ['branches.create', 'branches.update'], users: ['users.create', 'users.update'], roles: ['roles.create', 'roles.update'],
    beneficiaries: ['beneficiaries.create', 'beneficiaries.update'], 'case-files': ['case_files.create', 'case_files.update'],
    donors: ['donors.create', 'donors.update'], campaigns: ['campaigns.create', 'campaigns.update'], donations: ['donations.create', 'donations.update'],
    warehouses: ['warehouses.create', 'warehouses.update'], 'inventory-items': ['inventory_items.create', 'inventory_items.update'], 'aid-batches': ['aid_batches.create', 'aid_batches.update'],
  }
  if (title === 'Allocation Builder') return ['donation_allocations.manage']
  return map[module] ?? null
}

function buttonPermission(path: string, label: string) {
  const module = path.split('/').filter(Boolean)[0]
  const updateMap: Record<string, string> = { branches: 'branches.update', users: 'users.update', roles: 'roles.update', beneficiaries: 'beneficiaries.update', 'case-files': 'case_files.update', donors: 'donors.update', campaigns: 'campaigns.update', donations: 'donations.update' }
  const deleteMap: Record<string, string> = { branches: 'branches.delete', roles: 'roles.delete', beneficiaries: 'beneficiaries.delete', 'case-files': 'case_files.delete', donors: 'donors.delete', campaigns: 'campaigns.delete' }
  if (label === 'Edit') return updateMap[module]
  if (label.startsWith('Delete')) return deleteMap[module]
  const exact: Record<string, string> = {
    Enable: 'users.update', Disable: 'users.disable', 'Submit review': module === 'beneficiaries' ? 'beneficiaries.submit_review' : 'case_files.review',
    Approve: module === 'beneficiaries' ? 'beneficiaries.approve' : 'case_files.approve', Reject: module === 'beneficiaries' ? 'beneficiaries.reject' : 'case_files.reject',
    Suspend: module === 'beneficiaries' ? 'beneficiaries.suspend' : 'case_files.suspend', Reactivate: 'beneficiaries.reactivate', Close: 'case_files.close', Reopen: 'case_files.reopen',
    Activate: 'campaigns.activate', Pause: 'campaigns.pause', Complete: 'campaigns.complete', Cancel: module === 'campaigns' ? 'campaigns.cancel' : 'donations.cancel',
    'Confirm payment': 'donations.confirm', 'Generate receipt': 'receipts.generate', 'Regenerate receipt': 'receipts.generate', Download: module === 'case-files' ? 'case_documents.download' : 'exports.download',
    'Create CSV export': 'reports.export',
  }
  return exact[label]
}

function JsonBlock({ label, value }: { label: string; value: Record<string, unknown> | null }) {
  return (
    <div>
      <h4 className="mb-2 font-semibold text-[#10201a]">{label}</h4>
      <pre className="max-h-72 overflow-auto rounded-md border border-[#d9e1de] bg-[#fbfcfb] p-3 text-xs leading-5 text-[#29483d]">{JSON.stringify(value ?? {}, null, 2)}</pre>
    </div>
  )
}

function PaymentTransactionDetail({ transaction }: { transaction: PaymentTransaction }) {
  return (
    <div className="space-y-4 text-sm">
      <KeyValueRows
        rows={[
          ['Provider', transaction.provider],
          ['Provider transaction ID', transaction.provider_transaction_id],
          ['Idempotency key', transaction.idempotency_key],
          ['Amount', formatMoney(transaction.amount, transaction.currency)],
          ['Status', transaction.status],
          ['Paid at', transaction.paid_at ? formatDate(transaction.paid_at) : null],
          ['Failed at', transaction.failed_at ? formatDate(transaction.failed_at) : null],
          ['Created', formatDate(transaction.created_at)],
        ]}
      />
      <JsonBlock label="Request payload" value={transaction.request_payload} />
      <JsonBlock label="Response payload" value={transaction.response_payload} />
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
  return canAccessAny(user.permissions, item.permissions)
}

function PermissionGate({ children, permissions }: { children: ReactNode; permissions: string[] }) {
  const { me } = useOutletContext<{ me: CurrentUser }>()

  return canAccessAny(me.permissions, permissions) ? children : <UnauthorizedState title="You do not have permission to view this page" />
}

function guarded(element: ReactNode, ...permissions: string[]) {
  return <PermissionGate permissions={permissions}>{element}</PermissionGate>
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

function moneyNumber(value: string | number | null | undefined) {
  const number = Number(value ?? 0)

  return Number.isNaN(number) ? 0 : number
}

function formatMoney(value: string | number | null | undefined, currency: string) {
  return `${moneyNumber(value).toLocaleString(undefined, { maximumFractionDigits: 2, minimumFractionDigits: 2 })} ${currency}`
}

function donationAllocationTotal(donation: Donation) {
  return (donation.allocations ?? []).reduce((total, allocation) => total + moneyNumber(allocation.amount), 0)
}

function donationRemainingAmount(donation: Donation) {
  return moneyNumber(donation.amount) - donationAllocationTotal(donation)
}

function isDonationConfirmed(donation: Donation) {
  return donation.payment_status === 'paid' && donation.donation_status === 'confirmed'
}

function isDonationLocked(donation: Donation) {
  return isDonationConfirmed(donation) || ['cancelled', 'refunded'].includes(donation.donation_status) || ['cancelled', 'refunded'].includes(donation.payment_status)
}

function allocationTargetLabel(allocation: DonationAllocation) {
  if (allocation.campaign) {
    return allocation.campaign.title
  }

  if (allocation.beneficiary) {
    return `${allocation.beneficiary.code} - ${allocation.beneficiary.full_name}`
  }

  if (allocation.case_file) {
    return allocation.case_file.case_number
  }

  return 'No specific target'
}

function campaignProgress(campaign: Campaign) {
  const goal = moneyNumber(campaign.goal_amount)

  return goal > 0 ? Math.min((moneyNumber(campaign.collected_amount) / goal) * 100, 100) : 0
}

function formatPreferences(value: Donor['communication_preferences'] | undefined) {
  if (Array.isArray(value)) {
    return value.join(', ')
  }

  if (value && typeof value === 'object') {
    return Object.values(value).map(String).join(', ')
  }

  return ''
}

function todayDate() {
  return new Date().toISOString().slice(0, 10)
}

function dateTimeLocalValue(value?: string | null) {
  return value ? value.slice(0, 16) : new Date().toISOString().slice(0, 16)
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
      { path: 'dashboard', element: guarded(<DashboardPage />, 'dashboard.view') },
      { path: 'organization', element: guarded(<OrganizationPage />, 'organization.view') },
      { path: 'branches', element: guarded(<BranchesPage />, 'branches.view') },
      { path: 'users', element: guarded(<UsersPage />, 'users.view') },
      { path: 'roles', element: guarded(<RolesPage />, 'roles.view', 'permissions.view') },
      { path: 'audit-logs', element: guarded(<AuditLogsPage />, 'audit_logs.view') },
      { path: 'beneficiaries', element: guarded(<BeneficiariesPage />, 'beneficiaries.view') },
      { path: 'case-files', element: guarded(<CaseFilesPage />, 'case_files.view') },
      { path: 'donors', element: guarded(<DonorsPage />, 'donors.view') },
      { path: 'campaigns', element: guarded(<CampaignsPage />, 'campaigns.view') },
      { path: 'donations', element: guarded(<DonationsPage />, 'donations.view') },
      { path: 'finance/payments', element: guarded(<PaymentsPage />, 'payment_transactions.view', 'receipts.view') },
      { path: 'warehouses', element: guarded(<WarehousesPage />, 'warehouses.view') },
      { path: 'inventory-items', element: guarded(<InventoryItemsPage />, 'inventory_items.view') },
      { path: 'stock/summary', element: guarded(<StockSummaryPage />, 'stock_reports.view') },
      { path: 'stock/lots', element: guarded(<StockLotsPage />, 'stock_lots.view') },
      { path: 'stock/movements', element: guarded(<StockMovementsPage />, 'stock_movements.view') },
      { path: 'stock/low-stock', element: guarded(<LowStockPage />, 'stock_reports.view') },
      { path: 'stock/expiring', element: guarded(<ExpiringStockPage />, 'stock_reports.view') },
      { path: 'aid-batches', element: guarded(<AidBatchesPage />, 'aid_batches.view') },
      { path: 'aid-distributions', element: guarded(<AidDistributionsPage />, 'aid_distributions.view') },
      { path: 'reports', element: guarded(<ReportsPage />, 'reports.donations.view', 'reports.campaigns.view', 'reports.beneficiaries.view', 'reports.case_files.view', 'reports.distributions.view', 'reports.inventory.view', 'reports.audit_logs.view', 'exports.view') },
      { path: 'settings/public-portal', element: guarded(<PublicPortalSettingsPage />, 'public_portal_settings.view') },
      { path: 'notifications', element: guarded(<NotificationsPage />, 'notifications.view') },
      { path: 'system', element: guarded(<SystemPage />, 'system.queue.view', 'system.scheduler.view') },
      { path: '*', element: <Navigate replace to="/dashboard" /> },
    ],
  },
])

export function AppRouter() {
  return <RouterProvider router={router} />
}
