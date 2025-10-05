import { api } from '@/lib/http'
import type { Publication, Paginated, PublicationOrderBy, DateRange } from '../types'

export type PublicationQuery = {
  page?: number
  per_page?: number
  q?: string
  language?: string
  date_range?: DateRange
  order_by?: PublicationOrderBy
  order_dir?: 'asc' | 'desc'
  published_from?: string
  published_to?: string
}

function cleanParams<T extends Record<string, any>>(obj?: T): Record<string, any> {
  const out: Record<string, any> = {}
  if (!obj) return out
  for (const [k, v] of Object.entries(obj)) {
    if (v === undefined || v === null || v === '') continue
    out[k] = v
  }
  return out
}

export async function fetchPublications(query: PublicationQuery = {}): Promise<Paginated<Publication>> {
  const params = cleanParams(query)
  const res = await api.get('/public/v1/publications', { params })
  const payload = res.data as {
    data: Publication[]
    meta?: { current_page: number; per_page: number; total: number; last_page: number }
  }

  const meta = payload.meta
    ? {
      current_page: payload.meta.current_page,
      per_page: payload.meta.per_page,
      total: payload.meta.total,
      last_page: payload.meta.last_page,
    }
    : {
      current_page: 1,
      per_page: payload.data?.length ?? 0,
      total: payload.data?.length ?? 0,
      last_page: 1,
    }

  return { data: payload.data || [], meta }
}

export async function fetchPublication(slug: string): Promise<Publication | null> {
  const res = await api.get(`/public/v1/publications/${encodeURIComponent(slug)}`)
  const payload = res.data as any
  return (payload?.data ?? payload) as Publication
}
