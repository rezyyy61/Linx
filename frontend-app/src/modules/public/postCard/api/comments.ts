import { api, ensureCsrfCookie } from '@/lib/http'
import { mapServerComment } from '../adapters/comment.adapter'
import type { Comment } from '../types/comment.types'

type ServerComment = Parameters<typeof mapServerComment>[0]

function pickArray(payload: any): any[] {
  if (Array.isArray(payload)) return payload
  if (payload && Array.isArray(payload.data)) return payload.data
  return []
}
function pickMeta(payload: any): any { return payload?.meta ?? {} }
function pickLinks(payload: any): any { return payload?.links ?? {} }

export async function list(postId: string, input?: { cursor?: string; per_page?: number }) {
  const params: Record<string, any> = {}
  if (input?.per_page) params.per_page = input.per_page
  if (input?.cursor) params.cursor = input.cursor
  const res = await api.get(`/public/v1/posts/${postId}/comments`, { params, headers: { Accept: 'application/json' } })
  const payload: any = res.data
  const rows = pickArray(payload) as ServerComment[]
  const items: Comment[] = rows.map(mapServerComment)
  return { items, links: pickLinks(payload), meta: pickMeta(payload) }
}

export async function create(postId: string, body: string, parentId?: string | null): Promise<Comment> {
  await ensureCsrfCookie()
  const payload: any = { body }
  if (parentId) payload.parent_id = parentId
  const { data } = await api.post(`/public/v1/posts/${postId}/comments`, payload, { headers: { Accept: 'application/json' } })
  const raw: any = data && data.data ? data.data : data
  return mapServerComment(raw)
}

export async function update(postId: string, commentId: string, body: string): Promise<Comment> {
  await ensureCsrfCookie()
  const { data } = await api.patch(`/public/v1/posts/${postId}/comments/${commentId}`, { body }, {
    headers: { Accept: 'application/json' },
  })
  const raw: any = data && data.data ? data.data : data
  return mapServerComment(raw)
}


export async function remove(postId: string, commentId: string): Promise<void> {
  await ensureCsrfCookie()
  try {
    await api.delete(`/public/v1/posts/${postId}/comments/${commentId}`, {
      headers: { Accept: 'application/json' },
    })
  } catch (err) {
    console.error('[comments.remove] FAILED', { postId, commentId, err })
    throw err
  }
}
export async function toggleLike(postId: string, commentId: string): Promise<{ liked: boolean; likes: number }> {
  await ensureCsrfCookie()
  const { data } = await api.post(`/public/v1/posts/${postId}/comments/${commentId}/like`)
  return data as { liked: boolean; likes: number }
}

export type MiniUser = {
  id: string
  name: string
  slug?: string | null
  avatar?: string | null
  avatarColor?: string | null
}

export async function listLikers(
  postId: string,
  commentId: string,
  input?: { cursor?: string; per_page?: number }
): Promise<{ users: MiniUser[]; meta: any; links: any }> {
  const params: Record<string, any> = {}
  if (input?.per_page) params.per_page = input.per_page
  if (input?.cursor) params.cursor = input.cursor

  const res = await api.get(`/public/v1/posts/${postId}/comments/${commentId}/likes`, {
    params,
    headers: { Accept: 'application/json' },
  })
  const payload: any = res.data
  const rows = pickArray(payload)
  const users: MiniUser[] = rows.map((u: any) => ({
    id: String(u.id ?? ''),
    name: String(u.name ?? 'Unknown'),
    slug: u.slug ?? null,
    avatar: u.avatar ?? null,
    avatarColor: u.avatarColor ?? null,
  }))
  return { users, meta: pickMeta(payload), links: pickLinks(payload) }
}
