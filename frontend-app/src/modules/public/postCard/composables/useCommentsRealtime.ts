import { onMounted, onBeforeUnmount } from 'vue'
import { subscribePublic, unsubscribeExact } from '@/lib/echo'
import { mapServerComment } from '../adapters/comment.adapter'
import type { Comment } from '../types/comment.types'

type CreatedPayload = { comment: any }
type UpdatedPayload = { comment: any }
type DeletedPayload = { id: string | number; post_id: string | number }
type CountsPayload  = { id: string | number; post_id: string | number; counts: { likes?: number } }

type Options = {
  onCreated?: (c: Comment) => void
  onUpdated?: (c: Comment) => void
  onDeleted?: (id: string, postId?: string) => void
  onCounts?: (id: string, postId: string, counts: { likes?: number }) => void
}

export function useCommentsRealtime(opts: Options = {}) {
  const onCreated = opts.onCreated
  const onUpdated = opts.onUpdated
  const onDeleted = opts.onDeleted
  const onCounts  = opts.onCounts

  let chan: any | null = null
  let subscribedName = 'public.posts'

  async function start() {
    if (chan) return
    chan = await subscribePublic('public.posts')
    subscribedName = chan?.name || 'public.posts'

    chan.bind('public.comment.created', (data: CreatedPayload) => {
      try { onCreated?.(mapServerComment(data.comment)) } catch { /* empty */ }
    })

    chan.bind('public.comment.updated', (data: UpdatedPayload) => {
      try { onUpdated?.(mapServerComment(data.comment)) } catch { /* empty */ }
    })

    chan.bind('public.comment.deleted', (data: DeletedPayload) => {
      onDeleted?.(String(data.id), data?.post_id ? String(data.post_id) : undefined)
    })

    chan.bind('public.comment.counts.updated', (data: CountsPayload) => {
      onCounts?.(String(data.id), String(data.post_id), data.counts || {})
    })
  }

  async function stop() {
    if (!chan) return
    try {
      chan.unbind_all && chan.unbind_all()
      await unsubscribeExact(subscribedName)
    } finally {
      chan = null
    }
  }

  onMounted(() => { void start() })
  onBeforeUnmount(() => { void stop() })

  return { start, stop }
}
