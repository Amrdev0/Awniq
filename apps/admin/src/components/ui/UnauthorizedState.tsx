export function UnauthorizedState({ title = 'Protected screen unavailable' }: { title?: string }) {
  return (
    <div className="rounded-md border border-[#efd8bb] bg-[#fff8ed] p-4 text-sm text-[#6c4b1f]">
      {title}
    </div>
  )
}
