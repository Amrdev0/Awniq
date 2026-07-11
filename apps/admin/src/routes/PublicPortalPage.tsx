import { useState } from 'react'
import type { FormEvent, ReactNode } from 'react'
import { Link, useParams, useSearchParams } from 'react-router'
import { useMutation, useQuery } from '@tanstack/react-query'
import { ArrowLeft, ArrowRight, BarChart3, Globe2, HeartHandshake, Mail, Phone } from 'lucide-react'
import { EmptyState } from '../components/ui/EmptyState'
import { LoadingState } from '../components/ui/LoadingState'
import { ApiError } from '../services/api/apiClient'
import {
  createPublicDonationIntent,
  getPublicCampaign,
  getPublicCampaignsPage,
  getPublicOrganization,
  getPublicStats,
  type PublicCampaign,
  type PublicOrganization,
  type PublicStats,
} from '../services/api/publicPortal'

export function PublicPortalPage() {
  const [searchParams, setSearchParams] = useSearchParams()
  const page = Math.max(Number(searchParams.get('page') ?? 1) || 1, 1)
  const organization = useQuery({ queryKey: ['public-organization'], queryFn: getPublicOrganization, retry: false })
  const campaigns = useQuery({ queryKey: ['public-campaigns', page], queryFn: () => getPublicCampaignsPage({ page, per_page: 12 }), enabled: Boolean(organization.data), retry: false })
  const stats = useQuery({ queryKey: ['public-stats'], queryFn: getPublicStats, enabled: Boolean(organization.data), retry: false })

  if (organization.isPending) {
    return <PublicShell content={<LoadingState label="Loading public portal" />} />
  }

  if (isNotFound(organization.error)) {
    return <PublicShell content={<PortalDisabledState />} />
  }

  if (organization.isError) {
    return <PublicShell content={<EmptyState title="Public portal unavailable" />} />
  }

  return (
    <PublicShell
      organization={organization.data}
      content={
        <div className="space-y-8">
          <section className="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
            <div>
              <p className="text-sm font-medium uppercase text-[#5f6f68]">Public transparency</p>
              <h1 className="mt-3 text-4xl font-semibold text-[#10201a]">{organization.data.name}</h1>
              <p className="mt-4 max-w-3xl text-base leading-7 text-[#43534d]">
                {organization.data.about ?? `${organization.data.name} publishes public campaign progress and aggregate impact data through Awniq.`}
              </p>
              <div className="mt-5 flex flex-wrap gap-3 text-sm text-[#43534d]">
                {organization.data.city || organization.data.country ? (
                  <span className="inline-flex items-center gap-2 rounded-md border border-[#d8e0dc] bg-white px-3 py-2">
                    <Globe2 size={16} aria-hidden="true" />
                    {[organization.data.city, organization.data.country].filter(Boolean).join(', ')}
                  </span>
                ) : null}
                {organization.data.contact.email ? (
                  <span className="inline-flex items-center gap-2 rounded-md border border-[#d8e0dc] bg-white px-3 py-2">
                    <Mail size={16} aria-hidden="true" />
                    {organization.data.contact.email}
                  </span>
                ) : null}
                {organization.data.contact.phone ? (
                  <span className="inline-flex items-center gap-2 rounded-md border border-[#d8e0dc] bg-white px-3 py-2">
                    <Phone size={16} aria-hidden="true" />
                    {organization.data.contact.phone}
                  </span>
                ) : null}
              </div>
            </div>

            <ImpactPanel stats={stats.data} currency={organization.data.default_currency} isLoading={stats.isPending} />
          </section>

          <section>
            <SectionHeading eyebrow="Campaigns" title="Public Campaigns" />
            {campaigns.data ? (
              <CampaignGrid campaigns={campaigns.data.data} />
            ) : (
              <LoadingOrEmpty isLoading={campaigns.isPending} label="Loading public campaigns" />
            )}
            {campaigns.data?.meta.total ? <div className="mt-5 flex items-center justify-between gap-3 text-sm"><span>{campaigns.data.meta.from}-{campaigns.data.meta.to} of {campaigns.data.meta.total}</span><div className="flex gap-2"><button className="rounded-md border border-[#c8d4cf] bg-white px-3 py-2 disabled:opacity-40" disabled={page <= 1} onClick={() => setSearchParams({ page: String(page - 1) })} type="button">Previous</button><span className="px-2 py-2">Page {page} of {campaigns.data.meta.last_page}</span><button className="rounded-md border border-[#c8d4cf] bg-white px-3 py-2 disabled:opacity-40" disabled={page >= campaigns.data.meta.last_page} onClick={() => setSearchParams({ page: String(page + 1) })} type="button">Next</button></div></div> : null}
          </section>

          <section className="grid gap-6 lg:grid-cols-[0.8fr_1.2fr]">
            <DonationPanel organization={organization.data} campaigns={campaigns.data?.data ?? []} />
            <ContactPanel organization={organization.data} />
          </section>
        </div>
      }
    />
  )
}

export function PublicCampaignDetailsPage() {
  const { slug } = useParams()
  const organization = useQuery({ queryKey: ['public-organization'], queryFn: getPublicOrganization, retry: false })
  const campaign = useQuery({
    queryKey: ['public-campaign', slug],
    queryFn: () => getPublicCampaign(slug ?? ''),
    enabled: Boolean(slug),
    retry: false,
  })

  if (organization.isPending || campaign.isPending) {
    return <PublicShell content={<LoadingState label="Loading public campaign" />} />
  }

  if (isNotFound(organization.error) || isNotFound(campaign.error)) {
    return <PublicShell content={<EmptyState title="Campaign not available" />} />
  }

  if (organization.isError || campaign.isError) {
    return <PublicShell content={<EmptyState title="Public campaign unavailable" />} />
  }

  return (
    <PublicShell
      organization={organization.data}
      content={
        <div className="space-y-8">
          <Link className="inline-flex items-center gap-2 text-sm font-medium text-[#236b55]" to="/public">
            <ArrowLeft size={16} aria-hidden="true" />
            Public portal
          </Link>

          <article className="grid gap-6 lg:grid-cols-[1fr_360px]">
            <div>
              <StatusBadge status={campaign.data.status} />
              <h1 className="mt-4 text-4xl font-semibold text-[#10201a]">{campaign.data.title}</h1>
              <p className="mt-4 max-w-3xl text-base leading-7 text-[#43534d]">{campaign.data.description ?? 'No public campaign description is available yet.'}</p>
            </div>

            <CampaignProgressPanel campaign={campaign.data} />
          </article>
        </div>
      }
    />
  )
}

function PublicShell({ organization, content }: { organization?: PublicOrganization; content: ReactNode }) {
  return (
    <main className="min-h-svh bg-[#f6f8f7] text-[#172026]">
      <div className="mx-auto w-full max-w-6xl px-5 py-6 sm:px-6 lg:py-8">
        <header className="mb-8 flex flex-wrap items-center justify-between gap-4 border-b border-[#d8e0dc] pb-5">
          <Link className="inline-flex items-center gap-2 text-lg font-semibold text-[#10201a]" to="/public">
            <HeartHandshake className="text-[#a44a3f]" size={24} aria-hidden="true" />
            {organization?.name ?? 'Awniq Public'}
          </Link>
          <Link className="inline-flex items-center gap-2 rounded-md border border-[#c8d4cf] bg-white px-3 py-2 text-sm text-[#24332e]" to="/">
            Admin
            <ArrowRight size={16} aria-hidden="true" />
          </Link>
        </header>
        {content}
      </div>
    </main>
  )
}

export function PortalDisabledState() {
  return (
    <section className="grid min-h-[52vh] place-items-center">
      <div className="max-w-md rounded-md border border-[#d8e0dc] bg-white p-6 text-center">
        <HeartHandshake className="mx-auto text-[#a44a3f]" size={32} aria-hidden="true" />
        <h1 className="mt-4 text-2xl font-semibold text-[#10201a]">Public portal is not available</h1>
        <p className="mt-3 text-sm leading-6 text-[#52645e]">This organization has not enabled public transparency access.</p>
      </div>
    </section>
  )
}

function ImpactPanel({ stats, currency, isLoading }: { stats: PublicStats | undefined; currency: string; isLoading: boolean }) {
  if (!stats) {
    return (
      <section className="rounded-md border border-[#d8e0dc] bg-white p-5">
        <LoadingOrEmpty isLoading={isLoading} label="Loading impact stats" />
      </section>
    )
  }

  const rows = [
    ['Beneficiaries helped', String(stats.total_beneficiaries_helped)],
    ['Aid distributions', String(stats.total_aid_distributions)],
    ['Aid items distributed', stats.total_aid_items_distributed],
    ['Active campaigns', String(stats.active_campaigns)],
    ['Completed campaigns', String(stats.completed_campaigns)],
    ['Confirmed donations', stats.total_confirmed_donations_collected ? `${stats.total_confirmed_donations_collected} ${stats.currency}` : `Hidden ${currency}`],
  ]

  return (
    <section className="rounded-md border border-[#d8e0dc] bg-white p-5">
      <div className="mb-4 flex items-center gap-2 text-[#10201a]">
        <BarChart3 className="text-[#245a7a]" size={20} aria-hidden="true" />
        <h2 className="text-lg font-semibold">Impact Snapshot</h2>
      </div>
      <dl className="grid gap-3 sm:grid-cols-2">
        {rows.map(([label, value]) => (
          <div className="rounded-md border border-[#edf1ef] bg-[#fbfcfb] p-3" key={label}>
            <dt className="text-xs font-medium uppercase text-[#66766f]">{label}</dt>
            <dd className="mt-1 text-xl font-semibold text-[#10201a]">{value}</dd>
          </div>
        ))}
      </dl>
    </section>
  )
}

export function CampaignGrid({ campaigns }: { campaigns: PublicCampaign[] }) {
  if (campaigns.length === 0) {
    return <EmptyState title="No public campaigns found" />
  }

  return (
    <div className="grid gap-4 md:grid-cols-2">
      {campaigns.map((campaign) => (
        <article className="rounded-md border border-[#d8e0dc] bg-white p-5" key={campaign.slug}>
          <div className="flex items-start justify-between gap-3">
            <h3 className="text-xl font-semibold text-[#10201a]">{campaign.title}</h3>
            <StatusBadge status={campaign.status} />
          </div>
          <p className="mt-3 line-clamp-3 text-sm leading-6 text-[#52645e]">{campaign.description ?? 'No public campaign description is available yet.'}</p>
          <CampaignProgress campaign={campaign} />
          <Link className="mt-4 inline-flex items-center gap-2 text-sm font-medium text-[#236b55]" to={`/public/campaigns/${campaign.slug}`}>
            Details
            <ArrowRight size={16} aria-hidden="true" />
          </Link>
        </article>
      ))}
    </div>
  )
}

function CampaignProgressPanel({ campaign }: { campaign: PublicCampaign }) {
  return (
    <aside className="rounded-md border border-[#d8e0dc] bg-white p-5">
      <h2 className="text-lg font-semibold text-[#10201a]">Campaign Progress</h2>
      <CampaignProgress campaign={campaign} />
      <dl className="mt-5 space-y-3 text-sm">
        <KeyValue label="Start date" value={campaign.start_date ?? '-'} />
        <KeyValue label="End date" value={campaign.end_date ?? '-'} />
        <KeyValue label="Currency" value={campaign.currency} />
        <KeyValue label="Donation intake" value={campaign.donations_enabled ? 'Enabled' : 'Disabled'} />
      </dl>
    </aside>
  )
}

function CampaignProgress({ campaign }: { campaign: PublicCampaign }) {
  if (campaign.progress_percentage === null) {
    return <p className="mt-4 text-sm text-[#66766f]">Progress is not publicly shown.</p>
  }

  return (
    <div className="mt-4">
      <div className="mb-2 flex justify-between gap-4 text-sm">
        <span className="font-medium text-[#10201a]">{campaign.progress_percentage}% funded</span>
        <span className="text-[#66766f]">
          {campaign.collected_amount ?? '-'} / {campaign.goal_amount ?? '-'} {campaign.currency}
        </span>
      </div>
      <div className="h-2 overflow-hidden rounded-full bg-[#e4ebe7]">
        <div className="h-full bg-[#236b55]" style={{ width: `${Math.min(campaign.progress_percentage, 100)}%` }} />
      </div>
    </div>
  )
}

function DonationPanel({ organization, campaigns }: { organization: PublicOrganization; campaigns: PublicCampaign[] }) {
  const [campaignSlug, setCampaignSlug] = useState(campaigns.find((campaign) => campaign.donations_enabled)?.slug ?? '')
  const [donorName, setDonorName] = useState('')
  const [donorEmail, setDonorEmail] = useState('')
  const [amount, setAmount] = useState(100)
  const donationMutation = useMutation({
    mutationFn: createPublicDonationIntent,
  })

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    donationMutation.mutate({
      campaign_slug: campaignSlug || undefined,
      donor_name: donorName || undefined,
      donor_email: donorEmail || undefined,
      amount,
      currency: organization.default_currency,
    })
  }

  return (
    <section className="rounded-md border border-[#d8e0dc] bg-white p-5">
      <SectionHeading eyebrow="Donation" title="Donation Intent" />
      {!organization.settings.donations_enabled ? (
        <p className="text-sm leading-6 text-[#52645e]">Public donation intake is currently disabled.</p>
      ) : (
        <form className="mt-4 space-y-4" onSubmit={handleSubmit}>
          <label className="block text-sm">
            <span className="mb-1 block font-medium text-[#10201a]">Campaign</span>
            <select className="w-full rounded-md border border-[#c8d4cf] bg-white px-3 py-2" value={campaignSlug} onChange={(event) => setCampaignSlug(event.target.value)}>
              <option value="">General support</option>
              {campaigns
                .filter((campaign) => campaign.donations_enabled)
                .map((campaign) => (
                  <option key={campaign.slug} value={campaign.slug}>
                    {campaign.title}
                  </option>
                ))}
            </select>
          </label>
          <label className="block text-sm">
            <span className="mb-1 block font-medium text-[#10201a]">Name</span>
            <input className="w-full rounded-md border border-[#c8d4cf] px-3 py-2" value={donorName} onChange={(event) => setDonorName(event.target.value)} />
          </label>
          <label className="block text-sm">
            <span className="mb-1 block font-medium text-[#10201a]">Email</span>
            <input className="w-full rounded-md border border-[#c8d4cf] px-3 py-2" type="email" value={donorEmail} onChange={(event) => setDonorEmail(event.target.value)} />
          </label>
          <label className="block text-sm">
            <span className="mb-1 block font-medium text-[#10201a]">Amount ({organization.default_currency})</span>
            <input className="w-full rounded-md border border-[#c8d4cf] px-3 py-2" min={1} type="number" value={amount} onChange={(event) => setAmount(Number(event.target.value))} />
          </label>
          <button className="rounded-md bg-[#236b55] px-4 py-2 text-sm font-medium text-white disabled:opacity-60" disabled={donationMutation.isPending} type="submit">
            {donationMutation.isPending ? 'Submitting...' : 'Submit intent'}
          </button>
          {donationMutation.data ? <p className="text-sm text-[#236b55]">Reference: {donationMutation.data.reference}</p> : null}
          {donationMutation.isError ? <p className="text-sm text-[#a44a3f]">Donation intent could not be submitted.</p> : null}
        </form>
      )}
    </section>
  )
}

function ContactPanel({ organization }: { organization: PublicOrganization }) {
  return (
    <section className="rounded-md border border-[#d8e0dc] bg-white p-5">
      <SectionHeading eyebrow="Contact" title="Public Contact" />
      <dl className="mt-4 space-y-3 text-sm">
        <KeyValue label="Email" value={organization.contact.email ?? '-'} />
        <KeyValue label="Phone" value={organization.contact.phone ?? '-'} />
        <KeyValue label="Website" value={organization.website ?? '-'} />
        <KeyValue label="Location" value={[organization.city, organization.country].filter(Boolean).join(', ') || '-'} />
      </dl>
    </section>
  )
}

function SectionHeading({ eyebrow, title }: { eyebrow: string; title: string }) {
  return (
    <div className="mb-4">
      <p className="text-xs font-medium uppercase text-[#66766f]">{eyebrow}</p>
      <h2 className="mt-1 text-2xl font-semibold text-[#10201a]">{title}</h2>
    </div>
  )
}

function KeyValue({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex justify-between gap-4 border-b border-[#edf1ef] pb-2">
      <dt className="text-[#66766f]">{label}</dt>
      <dd className="font-medium text-[#10201a]">{value}</dd>
    </div>
  )
}

function StatusBadge({ status }: { status: string }) {
  const colors: Record<string, string> = {
    active: 'border-[#b7d8c8] bg-[#edf8f1] text-[#236b55]',
    paused: 'border-[#e8d6ad] bg-[#fff8e8] text-[#6f541e]',
    completed: 'border-[#bbd5e7] bg-[#eef7fc] text-[#245a7a]',
  }

  return <span className={`rounded-md border px-2 py-1 text-xs font-medium capitalize ${colors[status] ?? 'border-[#d8e0dc] bg-white text-[#52645e]'}`}>{status}</span>
}

function LoadingOrEmpty({ isLoading, label }: { isLoading: boolean; label: string }) {
  return isLoading ? <LoadingState label={label} /> : <EmptyState title="No data available" />
}

function isNotFound(error: unknown) {
  return error instanceof ApiError && error.status === 404
}
