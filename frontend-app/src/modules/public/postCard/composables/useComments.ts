import { reactive, computed } from 'vue'
import * as api from '../api/comments'
import type { Comment } from '../types/comment.types'
import { usePostActions } from './usePostActions'
import { useAuthStore } from "@/stores/auth/auth";

type Bucket = {
  items: Comment[]
  loading: boolean
  nextCursor: string | null
}

const store = reactive<Record<string, Bucket>>({})


function toParentId(v: unknown): string | null {
  if (v == null) return null
  const s = String(v).trim()
  if (s === '' || s === '0' || s.toLowerCase() === 'null' || s.toLowerCase() === 'undefined') return null
  return s
}

function ensureBucket(postId: string): Bucket {
  const key = String(postId)
  if (!store[key]) store[key] = { items: [], loading: false, nextCursor: null }
  return store[key]
}

function normalize(c: Comment): Comment {
  return {
    ...c,
    id: String(c.id),
    postId: String(c.postId),
    parentId: toParentId((c as any).parentId ?? (c as any).parent_id),
    likes: Number((c as any).likes ?? 0),
    liked: Boolean((c as any).liked ?? false),
  }
}

function mergeUnique(b: Bucket, incoming: Comment[]) {
  const idxMap = new Map(b.items.map((x, i) => [String(x.id), i]))
  for (const raw of incoming) {
    const it = normalize(raw as Comment)
    const id = String(it.id)
    if (idxMap.has(id)) {
      const i = idxMap.get(id)!
      b.items[i] = { ...b.items[i], ...it }
    } else {
      idxMap.set(id, b.items.length)
      b.items.push(it)
    }
  }
}

function promoteParentToTop(b: Bucket, parentId: string): number {
  const idx = b.items.findIndex(x => String(x.id) === parentId)
  if (idx === -1) return -1
  const [parent] = b.items.splice(idx, 1)
  b.items.unshift(parent)
  return 0
}

function findRootId(b: Bucket, startingParentId: string): string {
  let currentId: string | null = startingParentId
  while (currentId) {
    const node = b.items.find(x => String(x.id) === String(currentId))
    const next = node?.parentId != null ? String(node.parentId) : null
    if (!next) break
    currentId = next
  }
  return String(currentId)
}

function insertAfterParent(b: Bucket, c: Comment) {
  const existingIdx = b.items.findIndex(x => String(x.id) === String(c.id))
  if (existingIdx >= 0) {
    b.items[existingIdx] = { ...b.items[existingIdx], ...normalize(c) }
    return
  }
  const item = normalize(c)
  const pid = toParentId(item.parentId)
  if (!pid) {
    b.items.unshift(item)
    return
  }
  const rootId = findRootId(b, pid)
  promoteParentToTop(b, rootId)
  const parentIdx = b.items.findIndex(x => String(x.id) === String(pid))
  if (parentIdx >= 0) {
    b.items.splice(parentIdx + 1, 0, item)
  } else {
    b.items.unshift(item)
  }
}

export type CommentSort = 'newest' | 'top'

export function useComments(postIdArg?: string) {
  const { addComment, setCounts } = usePostActions()
  if (postIdArg) ensureBucket(postIdArg)

  function bucket(id: string) { return ensureBucket(id) }

  function roots(postId: string) {
    const b = bucket(postId)
    const ids = new Set(b.items.map(x => String(x.id)))
    return b.items.filter(c => {
      const pid = toParentId(c.parentId)
      return pid == null || !ids.has(pid)
    })
  }

  function replies(postId: string, parentId: string) {
    const pid = String(parentId)
    const b = bucket(postId)
    return b.items.filter(c => c.parentId != null && String(c.parentId) === pid)
  }

  function sort(list: Comment[], by: CommentSort) {
    if (by === 'top') return [...list]
    return [...list].sort((a, b) => (a.createdAt < b.createdAt ? 1 : -1))
  }

  async function loadFirst(postId: string, perPage = 20) {
    const b = bucket(postId)
    if (b.loading) return
    b.loading = true
    try {
      const { items, meta } = await api.list(String(postId), { per_page: perPage })
      b.items = []
      mergeUnique(b, items as unknown as Comment[])
      b.nextCursor = meta?.next_cursor ?? null
      setCounts(String(postId), { comments: b.items.length })
    } finally { b.loading = false }
  }

  async function loadMore(postId: string) {
    const b = bucket(postId)
    if (b.loading || !b.nextCursor) return
    b.loading = true
    try {
      const { items, meta } = await api.list(String(postId), { cursor: b.nextCursor })
      mergeUnique(b, items as unknown as Comment[])
      b.nextCursor = meta?.next_cursor ?? null
      setCounts(String(postId), { comments: b.items.length })
    } finally { b.loading = false }
  }

  async function add(postId: string, text: string, parentId?: string | null) {
    const b = bucket(postId)
    const created = await api.create(String(postId), text, parentId ?? null)
    const fixed: Comment = normalize({
      ...created,
      parentId: parentId != null ? String(parentId) : (created as any).parentId ?? (created as any).parent_id,
    } as Comment)
    insertAfterParent(b, fixed)
    addComment({ id: String(postId) } as any, 1)
    setCounts(String(postId), { comments: b.items.length })
    return fixed
  }

  async function reply(postId: string, parentId: string, text: string) {
    return add(postId, text, parentId)
  }

  async function remove(postId: string, commentId: string) {
    const b = bucket(postId)
    try {
      await api.remove(String(postId), String(commentId))
      const i = b.items.findIndex(x => String(x.id) === String(commentId))
      if (i >= 0) b.items.splice(i, 1)
      addComment({ id: String(postId) } as any, -1)
      setCounts(String(postId), { comments: b.items.length })
    } catch (e) {
      console.error('[useComments.remove] server failed', e)
      alert('Deleting comment failed.')
    }
  }

  async function update(postId: string, commentId: string, body: string) {
    const b = bucket(postId)
    const i = b.items.findIndex(x => String(x.id) === String(commentId))
    if (i < 0) return
    const prev = b.items[i]
    b.items[i] = { ...prev, body }
    try {
      const saved = await api.update(String(postId), String(commentId), body)
      const fixed = normalize(saved)
      b.items[i] = { ...b.items[i], ...fixed }
    } catch { b.items[i] = prev }
  }

  async function like(commentId: string, postId: string) {
    const b = bucket(postId)
    const i = b.items.findIndex(x => String(x.id) === String(commentId))
    if (i < 0) return
    const prevLiked = !!b.items[i].liked
    const prevLikes = Number(b.items[i].likes || 0)
    const nextLiked = !prevLiked
    const nextLikes = prevLikes + (nextLiked ? 1 : -1)
    b.items[i] = { ...b.items[i], liked: nextLiked, likes: Math.max(0, nextLikes) }
    try {
      const res = await api.toggleLike(String(postId), String(commentId))
      b.items[i] = { ...b.items[i], liked: res.liked, likes: res.likes }
      console.debug('[comment-like] OK', { postId, commentId, res })
    } catch (err) {
      b.items[i] = { ...b.items[i], liked: prevLiked, likes: prevLikes }
      console.error('[comment-like] FAILED', { postId, commentId, err })
    }
  }

  function upsertFromRealtime(c: Comment) {
    const normalized = normalize(c)
    const b = bucket(normalized.postId)
    mergeUnique(b, [normalized])
    setCounts(normalized.postId, { comments: b.items.length })
  }

  function deleteFromRealtime(commentId: string, postId?: string) {
    if (!postId) return
    const b = bucket(String(postId))
    const i = b.items.findIndex(x => String(x.id) === String(commentId))
    if (i >= 0) b.items.splice(i, 1)
    setCounts(String(postId), { comments: b.items.length })
  }

  function setCommentCounts(postId: string, commentId: string, partial: { likes?: number }) {
    const b = bucket(postId)
    const i = b.items.findIndex(x => String(x.id) === String(commentId))
    if (i >= 0) b.items[i] = { ...b.items[i], ...(partial.likes != null ? { likes: partial.likes } : {}) }
  }
  function setCommentLiked(postId: string, commentId: string, liked: boolean) {
    const b = bucket(postId)
    const i = b.items.findIndex(x => String(x.id) === String(commentId))
    if (i >= 0) b.items[i] = { ...b.items[i], liked }
  }

  function onCountsRealtime(commentId: string, postId: string, counts: { likes?: number }) {
    setCommentCounts(String(postId), String(commentId), counts)
  }

  function me() {
    const auth = useAuthStore()
    return { id: auth.user ? String(auth.user.id) : '' }
  }

  return {
    items: (id: string) => computed(() => bucket(id).items),
    loading: (id: string) => computed(() => bucket(id).loading),
    nextCursor: (id: string) => computed(() => bucket(id).nextCursor),
    loadFirst,
    loadMore,
    add,
    reply,
    remove,
    update,
    like,
    roots,
    replies,
    sort,
    me,
    upsertFromRealtime,
    deleteFromRealtime,
    onCountsRealtime,
    setCommentCounts,
    setCommentLiked,
  }
}
