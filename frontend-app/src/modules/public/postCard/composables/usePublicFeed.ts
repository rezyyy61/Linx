import { ref, computed } from 'vue'
import * as postsApi from '../api/posts'
import type { Post } from '../types/post.types'

export function usePublicFeed() {
  const items = ref<Post[]>([])
  const loading = ref(false)
  const cursor = ref<string | undefined>(undefined)
  const error = ref<string | null>(null)

  const hasMore = computed(() => typeof cursor.value === 'string' && cursor.value.length > 0)

  function has(id: string) {
    return items.value.some(p => p.id === id)
  }

  function upsert(post: Post, opts: { prepend?: boolean } = {}) {
    const prepend = opts.prepend ?? true
    const i = items.value.findIndex(x => x.id === post.id)
    if (i >= 0) {
      const prev = items.value[i]
      items.value[i] = {
        ...prev,
        ...post,
        counts: post.counts ?? prev.counts,
        media: post.media ?? prev.media,
      }
    } else {
      if (prepend) items.value.unshift(post)
      else items.value.push(post)
    }
  }

  function dedupePush(list: Post[]) {
    if (!Array.isArray(list) || !list.length) return
    const seen = new Set(items.value.map(x => x.id))
    for (const p of list) {
      if (!seen.has(p.id)) {
        items.value.push(p)
        seen.add(p.id)
      }
    }
  }

  async function loadMore() {
    if (loading.value) return
    loading.value = true
    error.value = null
    try {
      const { items: list, meta } = await postsApi.list({ cursor: cursor.value, per_page: 20 })
      dedupePush(Array.isArray(list) ? list : [])
      const next =
        typeof meta?.next_cursor === 'string'
          ? meta.next_cursor
          : Array.isArray(meta?.next_cursor)
            ? meta.next_cursor.find((x: unknown) => typeof x === 'string' && x.length > 0)
            : undefined
      cursor.value = next
    } catch (e: any) {
      error.value = e?.message ?? 'Failed to load posts'
    } finally {
      loading.value = false
    }
  }

  function reset() {
    items.value = []
    cursor.value = undefined
    error.value = null
  }

  return { items, loading, hasMore, error, loadMore, has, upsert, reset }
}
