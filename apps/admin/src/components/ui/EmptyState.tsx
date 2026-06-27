type EmptyStateProps = {
  title: string
  description?: string
}

export function EmptyState({ title, description }: EmptyStateProps) {
  return (
    <div className="rounded-md border border-dashed border-[#c8d4cf] bg-white p-4">
      <p className="text-sm font-medium text-[#10201a]">{title}</p>
      {description ? <p className="mt-1 text-sm text-[#52645e]">{description}</p> : null}
    </div>
  )
}
