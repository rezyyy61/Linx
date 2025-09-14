import { api, ensureCsrfCookie } from '@/lib/http'
import { mapServerPost } from '../adapters/post.adapter'
import { mapServerMiniUser, type ServerMiniUser } from '../adapters/user.adapter'
import type { Post } from '../types/post.types'

type ServerPost = Parameters<typeof mapServerPost>[0]
type ListResponse = { data?: ServerPost[]; links?: any; meta?: any } | ServerPost[]

function pickArray(payload: any): ServerPost[] {
  if (Array.isArray(payload)) return payload
  if (payload && Array.isArray(payload.data)) return payload.data
  return []
}
function pickMeta(payload: any): any { return payload?.meta ?? {} }
function pickLinks(payload: any): any { return payload?.links ?? {} }

export async function list(input?: { cursor?: string; per_page?: number }) {
  const params: Record<string, any> = {}
  if (input?.per_page) params.per_page = input.per_page
  if (input?.cursor) params.cursor = input.cursor
  const res = await api.get<ListResponse>('/public/v1/posts', {
    params,
    headers: { Accept: 'application/json' },
  })
  const payload: any = res.data
  const rows = pickArray(payload)
  const items: Post[] = rows.map(mapServerPost)
  return { items, links: pickLinks(payload), meta: pickMeta(payload) }
}

export async function get(id: string) {
  const res = await api.get<{ data?: ServerPost } | ServerPost>(`/public/v1/posts/${id}`, {
    headers: { Accept: 'application/json' },
  })
  const payload: any = res.data
  const raw: ServerPost = (payload && payload.data) ? payload.data : payload
  return mapServerPost(raw)
}

export async function toggleLike(id: string): Promise<{ liked: boolean; likes: number }> {
  await ensureCsrfCookie()
  const { data } = await api.post(`/public/v1/posts/${id}/like`)
  return data as { liked: boolean; likes: number }
}

type LikesPreviewResponse =
  | { total?: number; users?: { data?: ServerMiniUser[] } | ServerMiniUser[] }
  | { total?: number; data?: ServerMiniUser[] }

export async function getLikesPreview(postId: string): Promise<{
  total: number
  users: ReturnType<typeof mapServerMiniUser>[]
}> {
  const { data } = await api.get<LikesPreviewResponse>(`/public/v1/posts/${postId}/likes/preview`, {
    headers: { Accept: 'application/json' },
  })
  const total = Number((data as any)?.total ?? 0)
  const rawUsers =
    Array.isArray((data as any)?.users)
      ? (data as any).users
      : Array.isArray((data as any)?.users?.data)
        ? (data as any).users.data
        : Array.isArray((data as any)?.data)
          ? (data as any).data
          : []
  const users = (rawUsers as ServerMiniUser[]).map(mapServerMiniUser)
  return { total, users }
}

type LikesListResponse = {
  data?: ServerMiniUser[]
  next_cursor?: string | null
  prev_cursor?: string | null
}

export async function getLikesList(postId: string, opts?: {
  limit?: number
  cursor?: string | null
}) {
  const params: Record<string, any> = {}
  if (opts?.limit) params.limit = opts.limit
  if (opts?.cursor) params.cursor = opts.cursor
  const { data } = await api.get<LikesListResponse>(`/public/v1/posts/${postId}/likes`, {
    params,
    headers: { Accept: 'application/json' },
  })
  const items = (data?.data ?? []).map(mapServerMiniUser)
  return {
    items,
    nextCursor: data?.next_cursor ?? null,
    prevCursor: data?.prev_cursor ?? null,
  }
}

export async function toggleSave(id: string): Promise<{ saved: boolean; saves: number }> {
  await ensureCsrfCookie()
  const { data } = await api.post(`/public/v1/posts/${id}/save`)
  return data as { saved: boolean; saves: number }
}
