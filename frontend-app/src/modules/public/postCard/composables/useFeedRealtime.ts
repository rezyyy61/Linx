import { onMounted, onBeforeUnmount } from 'vue'
import type { Ref } from 'vue'
import { subscribePublic, unsubscribeExact } from '@/lib/echo'
import type { Post } from '../types/post.types'
import { mapServerPost } from '../adapters/post.adapter'
import * as postsApi from '../api/posts'

type ServerPostPayload = { post: any }
type ServerCountsPayload = { id: number | string; counts: { likes?: number; comments?: number; shares?: number; saves?: number; views?: number } }
type Options = {
  channelName?: string
  prependNew?: boolean
  maxItems?: number
  accept?: (p: Post) => boolean
  onUpsert?: (p: Post) => void
  onCounts?: (id: string, counts: ServerCountsPayload['counts']) => void
}

export function useFeedRealtime(items: Ref<Post[]>, opts: Options = {}) {
  const channel = opts.channelName ?? 'public.posts'
  const prependNew = opts.prependNew ?? true
  const maxItems = opts.maxItems ?? 200
  const accept = opts.accept ?? ((p: Post) => p.visibility === 'public')
  const onUpsert = opts.onUpsert
  const onCounts = opts.onCounts

  let chan: any | null = null
  let subscribedName = channel

  function upsert(post: Post) {
    const idx = items.value.findIndex(x => x.id === post.id)
    if (idx >= 0) {
      const prev = items.value[idx]
      items.value[idx] = { ...prev, ...post, counts: post.counts ?? prev.counts, media: post.media ?? prev.media }
    } else if (accept(post)) {
      if (prependNew) items.value.unshift(post)
      else items.value.push(post)
      if (items.value.length > maxItems) items.value.length = maxItems
    }
    onUpsert?.(post)
  }

  function patchCounts(payload: ServerCountsPayload) {
    const id = String(payload.id)
    const idx = items.value.findIndex(x => x.id === id)
    if (idx === -1) return
    const prev = items.value[idx]
    const nextCounts = { ...prev.counts, ...(payload.counts || {}) }
    items.value[idx] = { ...prev, counts: nextCounts }
    onCounts?.(id, payload.counts || {})
  }

  async function ensureFromApi(data: any) {
    const id =
      (typeof data?.post === 'object' && data.post?.id != null) ? String(data.post.id)
        : (typeof data?.id !== 'undefined') ? String(data.id)
          : (typeof data?.post === 'string' || typeof data?.post === 'number') ? String(data.post)
            : null
    if (!id) return
    try {
      const full = await postsApi.get(id)
      upsert(full)
    } catch { /* empty */ }
  }

  async function start() {
    if (chan) return
    chan = await subscribePublic(channel)
    subscribedName = chan?.name || channel

    const onCreated = (data: ServerPostPayload) => {
      try { upsert(mapServerPost(data.post)) } catch { void ensureFromApi(data) }
    }
    const onUpdated = (data: ServerPostPayload) => {
      try { upsert(mapServerPost(data.post)) } catch { void ensureFromApi(data) }
    }
    const onDeleted = (data: { id: number | string }) => {
      const id = String(data.id)
      const i = items.value.findIndex(x => x.id === id)
      if (i >= 0) items.value.splice(i, 1)
    }

    chan.bind('public.post.created', onCreated)
    chan.bind('public.post.updated', onUpdated)
    chan.bind('public.post.deleted', onDeleted)
    chan.bind('public.post.counts.updated', (d: ServerCountsPayload) => patchCounts(d))
  }

  async function stop() {
    if (!chan) return
    try {
      chan.unbind_all && chan.unbind_all()
      await unsubscribeExact(subscribedName)
    } finally { chan = null }
  }

  onMounted(() => { void start() })
  onBeforeUnmount(() => { void stop() })

  return { start, stop }
}
