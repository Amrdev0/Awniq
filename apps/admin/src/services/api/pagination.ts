export type PaginationParams = {
  page?: number
  per_page?: number
  search?: string
  [key: string]: string | number | boolean | null | undefined
}

export type PaginationMeta = {
  current_page: number
  from: number | null
  last_page: number
  path: string
  per_page: number
  to: number | null
  total: number
}

export type PaginatedResponse<T> = {
  data: T[]
  links: {
    first: string | null
    last: string | null
    prev: string | null
    next: string | null
  }
  meta: PaginationMeta
}

export function paginatedPath(path: string, params: PaginationParams = {}) {
  const query = new URLSearchParams()

  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') query.set(key, String(value))
  })

  return query.size ? `${path}?${query}` : path
}

export function readPagination(searchParams: URLSearchParams, prefix = '') {
  const rawPage = Number(searchParams.get(`${prefix}page`) ?? 1)
  const rawPerPage = Number(searchParams.get(`${prefix}per_page`) ?? 15)

  return {
    page: Number.isInteger(rawPage) && rawPage > 0 ? rawPage : 1,
    perPage: [15, 25, 50, 100].includes(rawPerPage) ? rawPerPage : 15,
    search: searchParams.get(`${prefix}search`) ?? '',
  }
}
