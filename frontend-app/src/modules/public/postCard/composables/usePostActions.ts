import { reactive, toRefs } from 'vue'
import type { Post } from '../types/post.types'

type Counts = { likes: number; comments: number; shares: number; saves: number; views?: number }

type State = {
  liked: Record<string, boolean>
  saved: Record<string, boolean>
  counts: Record<string, Counts>
}

const state: State = reactive({
  liked: {},
  saved: {},
  counts: {},
})

export function usePostActions() {
  function ensure(post: Post) {
    if (!state.counts[post.id]) {
      state.counts[post.id] = {
        likes: post.counts?.likes ?? 0,
        comments: post.counts?.comments ?? 0,
        shares: post.counts?.shares ?? 0,
        saves: post.counts?.saves ?? 0,
        views: post.counts?.views ?? 0,
      }
    }
    if (state.liked[post.id] === undefined) state.liked[post.id] = !!post.liked
    if (state.saved[post.id] === undefined) state.saved[post.id] = !!(post as any).saved

  }

  async function toggleLike(post: Post) {
    ensure(post)
    const prev = state.liked[post.id]
    state.liked[post.id] = !prev
    state.counts[post.id].likes += state.liked[post.id] ? 1 : -1
    try {
      const { toggleLike } = await import('../api/posts')
      const res = await toggleLike(post.id)
      state.liked[post.id] = res.liked
      state.counts[post.id].likes = res.likes
    } catch {
      state.liked[post.id] = prev
      state.counts[post.id].likes += state.liked[post.id] ? 1 : -1
    }
  }

  async function toggleSave(post: Post) {
    ensure(post)
    const prev = state.saved[post.id]
    state.saved[post.id] = !prev
    state.counts[post.id].saves += state.saved[post.id] ? 1 : -1
    try {
      const { toggleSave: apiToggleSave } = await import('../api/posts')
      const res = await apiToggleSave(post.id)
      state.saved[post.id] = res.saved
      state.counts[post.id].saves = res.saves
    } catch {
      state.saved[post.id] = prev
      state.counts[post.id].saves += state.saved[post.id] ? 1 : -1
    }
  }

  function addShare(post: Post) {
    ensure(post)
    state.counts[post.id].shares += 1
  }

  function addView(post: Post) {
    ensure(post)
    state.counts[post.id].views = (state.counts[post.id].views || 0) + 1
  }

  function addComment(post: Post, delta = 1) {
    ensure(post)
    state.counts[post.id].comments += delta
  }

  function setCounts(postId: string, partial: Partial<Counts>) {
    if (!state.counts[postId]) {
      state.counts[postId] = { likes: 0, comments: 0, shares: 0, saves: 0, views: 0 }
    }
    state.counts[postId] = { ...state.counts[postId], ...partial }
  }

  function setLiked(postId: string, liked: boolean) {
    state.liked[postId] = liked
  }

  return {
    ...toRefs(state),
    ensure,
    toggleLike,
    toggleSave,
    addShare,
    addView,
    addComment,
    setCounts,
    setLiked,
  }
}
