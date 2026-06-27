import { createBrowserRouter, RouterProvider } from 'react-router'
import { Activity, Server, ShieldCheck } from 'lucide-react'
import { useQuery } from '@tanstack/react-query'
import { appConfig } from '../app/config'
import { EmptyState } from '../components/ui/EmptyState'
import { LoadingState } from '../components/ui/LoadingState'
import { UnauthorizedState } from '../components/ui/UnauthorizedState'
import { getHealth } from '../services/api/health'

function FoundationPage() {
  const health = useQuery({
    queryKey: ['health'],
    queryFn: getHealth,
    retry: false,
  })

  return (
    <main className="min-h-svh bg-[#f6f8f7] text-[#172026]">
      <div className="mx-auto flex min-h-svh w-full max-w-6xl flex-col px-6 py-8">
        <header className="flex flex-wrap items-center justify-between gap-4 border-b border-[#d9e1de] pb-5">
          <div>
            <p className="text-sm font-medium uppercase tracking-wide text-[#4b635b]">
              Aid operations platform
            </p>
            <h1 className="mt-2 text-3xl font-semibold tracking-normal text-[#10201a]">
              Awniq Admin
            </h1>
          </div>
          <div className="rounded-md border border-[#c8d4cf] bg-white px-3 py-2 text-sm text-[#42534d]">
            Phase 01 foundation
          </div>
        </header>

        <section className="grid flex-1 gap-4 py-8 md:grid-cols-[240px_1fr]">
          <nav className="rounded-md border border-[#d9e1de] bg-white p-3">
            <a className="flex items-center gap-2 rounded-md bg-[#e8f1ed] px-3 py-2 text-sm font-medium text-[#10201a]" href="/">
              <Activity size={16} aria-hidden="true" />
              Foundation
            </a>
          </nav>

          <div className="space-y-4">
            <section className="rounded-md border border-[#d9e1de] bg-white p-5">
              <div className="flex items-start gap-3">
                <Server className="mt-1 text-[#236b55]" size={22} aria-hidden="true" />
                <div>
                  <h2 className="text-xl font-semibold text-[#10201a]">API connection</h2>
                  <p className="mt-1 text-sm text-[#52645e]">
                    The admin shell reads its backend URL from <code>VITE_API_BASE_URL</code>.
                  </p>
                </div>
              </div>

              <div className="mt-5 rounded-md border border-[#d9e1de] bg-[#fbfcfb] p-4">
                {health.isPending ? <LoadingState label="Checking API health" /> : null}
                {health.isError ? (
                  <EmptyState
                    title="API not reachable"
                    description={`Start the API or update VITE_API_BASE_URL. Current URL: ${appConfig.apiBaseUrl}`}
                  />
                ) : null}
                {health.data ? (
                  <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                      <p className="text-sm font-medium text-[#10201a]">Status: {health.data.status}</p>
                      <p className="text-sm text-[#52645e]">Service: {health.data.service}</p>
                    </div>
                    <span className="rounded-md bg-[#dff3e9] px-3 py-1 text-sm font-medium text-[#176044]">
                      Connected
                    </span>
                  </div>
                ) : null}
              </div>
            </section>

            <section className="rounded-md border border-[#d9e1de] bg-white p-5">
              <div className="flex items-start gap-3">
                <ShieldCheck className="mt-1 text-[#236b55]" size={22} aria-hidden="true" />
                <div>
                  <h2 className="text-xl font-semibold text-[#10201a]">Access layer placeholder</h2>
                  <p className="mt-1 text-sm text-[#52645e]">
                    Authentication, roles, permissions, and route guards are implemented in Phase 02.
                  </p>
                </div>
              </div>
              <div className="mt-5">
                <UnauthorizedState />
              </div>
            </section>
          </div>
        </section>
      </div>
    </main>
  )
}

const router = createBrowserRouter([
  {
    path: '/',
    element: <FoundationPage />,
  },
])

export function AppRouter() {
  return <RouterProvider router={router} />
}
