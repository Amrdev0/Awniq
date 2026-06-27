type LoadingStateProps = {
  label?: string
}

export function LoadingState({ label = 'Loading' }: LoadingStateProps) {
  return (
    <div className="flex items-center gap-3 text-sm text-[#52645e]" role="status" aria-live="polite">
      <span className="h-4 w-4 animate-spin rounded-full border-2 border-[#b7c8c0] border-t-[#236b55]" />
      {label}
    </div>
  )
}
