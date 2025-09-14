import { reactive, toRefs } from 'vue'
import type { UserSummary } from '../types/post.types'
import { getLikesPreview, getLikesList } from '../api/posts'
import { subscribePublic, unsubscribeExact } from '@/lib/echo'

type Preview = { total: number; users: UserSummary[] }
type ListState = {
  items: UserSummary[]
  nextCursor: string | null
  prevCursor: string | null
  loading: boolean
  error: string | null
}

const state = reactive<{
  previewByPost: Record<string, Preview | undefined>
  listByPost: Record<string, ListState | undefined>
}>({
  previewByPost: {},
  listByPost: {},
})

const timers: Record<string, any> = {}
let chan: any | null = null
let subscribedName = 'public.posts'
let subCount = 0

export function usePostLikes() {
  async function fetchPreview(postId: string) {
    const res = await getLikesPreview(postId)
    state.previewByPost[postId] = { total: res.total, users: res.users }
    return state.previewByPost[postId]
  }

  function scheduleRefresh(postId: string, delay = 250) {
    clearTimeout(timers[postId])
    timers[postId] = setTimeout(() => { void fetchPreview(postId) }, delay)
  }

  async function initList(postId: string, limit = 30) {
    state.listByPost[postId] = {
      items: [],
      nextCursor: null,
      prevCursor: null,
      loading: true,
      error: null,
    }
    try {
      const { items, nextCursor, prevCursor } = await getLikesList(postId, { limit })
      state.listByPost[postId] = {
        items,
        nextCursor,
        prevCursor,
        loading: false,
        error: null,
      }
    } catch (e: any) {
      state.listByPost[postId] = {
        items: [],
        nextCursor: null,
        prevCursor: null,
        loading: false,
        error: e?.message ?? 'Failed to load likes',
      }
    }
  }

  async function loadMore(postId: string, limit = 30) {
    const s = state.listByPost[postId]
    if (!s || s.loading || !s.nextCursor) return
    s.loading = true
    try {
      const { items, nextCursor } = await getLikesList(postId, { limit, cursor: s.nextCursor })
      s.items.push(...items)
      s.nextCursor = nextCursor
      s.loading = false
    } catch (e: any) {
      s.error = e?.message ?? 'Failed to load more'
      s.loading = false
    }
  }

  function resetList(postId: string) {
    delete state.listByPost[postId]
  }

  async function startRealtime() {
    subCount += 1
    if (chan) return
    chan = await subscribePublic('public.posts')
    subscribedName = chan?.name || 'public.posts'
    chan.bind('public.post.counts.updated', (d: { id: string|number; counts: { likes?: number } }) => {
      const id = String(d?.id ?? '')
      if (!id) return
      const p = state.previewByPost[id]
      if (p) {
        const next = { ...p, total: typeof d.counts?.likes === 'number' ? d.counts.likes : p.total }
        state.previewByPost[id] = next
        scheduleRefresh(id, 200)
      }
    })
  }

  async function stopRealtime() {
    subCount = Math.max(0, subCount - 1)
    if (subCount > 0) return
    if (!chan) return
    try {
      chan.unbind_all && chan.unbind_all()
      await unsubscribeExact(subscribedName)
    } finally {
      chan = null
    }
  }

  return {
    ...toRefs(state),
    fetchPreview,
    initList,
    loadMore,
    resetList,
    startRealtime,
    stopRealtime,
  }
}
