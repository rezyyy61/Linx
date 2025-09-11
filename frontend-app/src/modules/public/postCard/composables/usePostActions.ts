import { reactive, toRefs } from 'vue'
import type { Post } from '../types/post.types'

type State = {
  liked: Record<string, boolean>
  saved: Record<string, boolean>
  counts: Record<string, { likes: number; comments: number; shares: number; saves: number; views?: number }>
}

const state: State = reactive({
  liked: {},
  saved: {},
  counts: {}
})

export function usePostActions() {
  function ensure(post: Post) {
    if (!state.counts[post.id]) {
      state.counts[post.id] = {
        likes: post.counts.likes,
        comments: post.counts.comments,
        shares: post.counts.shares,
        saves: post.counts.saves,
        views: post.counts.views
      }
    }
    if (state.liked[post.id] === undefined) state.liked[post.id] = false
    if (state.saved[post.id] === undefined) state.saved[post.id] = false
  }

  function toggleLike(post: Post) {
    ensure(post)
    const prev = state.liked[post.id]
    state.liked[post.id] = !prev
    state.counts[post.id].likes += state.liked[post.id] ? 1 : -1
  }

  function toggleSave(post: Post) {
    ensure(post)
    const prev = state.saved[post.id]
    state.saved[post.id] = !prev
    state.counts[post.id].saves += state.saved[post.id] ? 1 : -1
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

  return {
    ...toRefs(state),
    ensure,
    toggleLike,
    toggleSave,
    addShare,
    addView,
    addComment
  }
}
