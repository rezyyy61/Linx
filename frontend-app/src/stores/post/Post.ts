// src/stores/post/Post.ts
import { defineStore } from 'pinia'
import { api, ensureCsrfCookie } from '@/lib/http'

export type MediaItem = {
  id: number
  url?: string
  mime_type?: string
  width?: number | null
  height?: number | null
  order?: number
  [k: string]: unknown
}

export type Post = {
  id: number
  user_id: number
  content: string | null
  visibility: 'public' | 'private' | 'friends'
  status: 'draft' | 'published' | 'archived'
  published_at: string | null
  created_at?: string
  updated_at?: string
  media: MediaItem[]
}

export type PostPayload = {
  content?: string | null
  visibility?: 'public' | 'private' | 'friends'
  status?: 'draft' | 'published'
  media?: { id: number; order?: number }[] | null
}

type ListFilters = {
  q?: string
  visibility?: 'public' | 'private' | 'friends'
  status?: 'draft' | 'published' | 'archived' | 'all'
  date_from?: string
  date_to?: string
  sort?: 'newest' | 'oldest'
}

function normalizePost(raw: any): Post {
  const media: MediaItem[] = Array.isArray(raw?.media)
    ? raw.media.map((m: any, i: number) => ({
      id: Number(m.id),
      url: m.url ?? m.original_url ?? m.preview_url,
      mime_type: m.mime_type ?? m.mime ?? m.type,
      width: m.width ?? null,
      height: m.height ?? null,
      order: typeof m.order === 'number' ? m.order : i,
    }))
    : []

  media.sort((a, b) => (a.order ?? 0) - (b.order ?? 0) || a.id - b.id)

  return {
    id: Number(raw.id),
    user_id: Number(raw.user_id),
    content: raw.content ?? null,
    visibility: raw.visibility ?? 'public',
    status: (raw.status as Post['status']) ?? 'draft',
    published_at: raw.published_at ?? null,
    created_at: raw.created_at,
    updated_at: raw.updated_at,
    media,
  }
}

function extractCursorFromLink(link?: string | null): string | null {
  if (!link) return null
  try {
    const u = new URL(link)
    return u.searchParams.get('cursor')
  } catch {
    return null
  }
}

export const usePostStore = defineStore('post', {
  state: () => ({
    byId: {} as Record<number, Post>,
    ids: [] as number[],
    loadingList: false,
    loadingOne: false,
    saving: false,
    deleting: false,
    nextCursor: null as string | null,
    prevCursor: null as string | null,
  }),

  getters: {
    list: (s): Post[] => s.ids.map((id) => s.byId[id]).filter(Boolean),
    hasMore: (s): boolean => !!s.nextCursor,
  },

  actions: {
    async fetchList(
      opts: { userId?: number; cursor?: string | null; limit?: number; replace?: boolean } & ListFilters = {}
    ) {
      this.loadingList = true
      try {
        const { userId, cursor, limit, replace = true, ...filters } = opts

        const params: Record<string, any> = { ...filters }
        if (userId) params.user_id = userId
        if (cursor) params.cursor = cursor
        if (limit) params.per_page = limit

        const { data } = await api.get('posts', { params })

        const items: any[] = Array.isArray(data?.data) ? data.data : []
        const links = data?.links ?? {}
        const meta = data?.meta ?? {}

        const incomingIds: number[] = []
        for (const raw of items) {
          const p = normalizePost(raw)
          this.byId[p.id] = p
          incomingIds.push(p.id)
        }

        if (replace) {
          this.ids = incomingIds
        } else {
          for (const id of incomingIds) if (!this.ids.includes(id)) this.ids.push(id)
        }

        this.nextCursor = meta?.next_cursor ?? extractCursorFromLink(links?.next)
        this.prevCursor = meta?.prev_cursor ?? extractCursorFromLink(links?.prev)
        return this.list
      } finally {
        this.loadingList = false
      }
    },

    async fetchOne(id: number) {
      this.loadingOne = true
      try {
        const { data } = await api.get(`posts/${id}`)
        const raw = data?.data ?? data
        const p = normalizePost(raw)
        this.byId[p.id] = p
        if (!this.ids.includes(p.id)) this.ids.unshift(p.id)
        return p
      } finally {
        this.loadingOne = false
      }
    },

    async create(payload: PostPayload) {
      this.saving = true
      try {
        await ensureCsrfCookie()
        const { data } = await api.post('posts', payload)
        const raw = data?.data ?? data
        const p = normalizePost(raw)
        this.byId[p.id] = p
        this.ids.unshift(p.id)
        return p
      } finally {
        this.saving = false
      }
    },

    async update(id: number, payload: PostPayload) {
      this.saving = true
      try {
        await ensureCsrfCookie()
        const { data } = await api.put(`posts/${id}`, payload)
        const raw = data?.data ?? data
        const p = normalizePost(raw)
        this.byId[p.id] = p
        return p
      } finally {
        this.saving = false
      }
    },

    async remove(id: number) {
      this.deleting = true
      try {
        await ensureCsrfCookie()
        await api.delete(`posts/${id}`)
        delete this.byId[id]
        this.ids = this.ids.filter((i) => i !== id)
      } finally {
        this.deleting = false
      }
    },

    reset() {
      this.byId = {}
      this.ids = []
      this.loadingList = false
      this.loadingOne = false
      this.saving = false
      this.deleting = false
      this.nextCursor = null
      this.prevCursor = null
    },
  },
})
