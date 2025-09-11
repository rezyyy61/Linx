import { reactive, toRefs } from 'vue'

type User = { id: string; name: string; username: string; avatarUrl?: string }
export type Comment = {
  id: string
  postId: string
  author: User
  text: string
  createdAt: string
  parentId?: string | null
  likes?: number
}

type State = {
  byPost: Record<string, Comment[]>
  me: User
}

const state: State = reactive({
  byPost: {},
  me: { id: 'me', name: 'You', username: 'you', avatarUrl: 'https://i.pravatar.cc/80?u=me' }
})

function seedIfNeeded(postId: string) {
  if (state.byPost[postId]) return
  const u1: User = { id: 'u1', name: 'Ava Martin', username: 'ava', avatarUrl: 'https://i.pravatar.cc/80?img=1' }
  const u2: User = { id: 'u2', name: 'Leo Schmidt', username: 'leo', avatarUrl: 'https://i.pravatar.cc/80?img=2' }
  const u3: User = { id: 'u3', name: 'Mia Rossi', username: 'mia', avatarUrl: 'https://i.pravatar.cc/80?img=3' }

  const base: Comment[] = [
    { id: `${postId}-c1`, postId, author: u1, text: 'Love this!', createdAt: new Date(Date.now() - 3600e3).toISOString(), likes: 3 },
    { id: `${postId}-c2`, postId, author: u2, text: 'Nice work 👏', createdAt: new Date(Date.now() - 1800e3).toISOString(), likes: 5 },
    { id: `${postId}-c3`, postId, author: u3, text: 'Following', createdAt: new Date(Date.now() - 1200e3).toISOString(), likes: 1 },
    { id: `${postId}-c2-r1`, postId, author: u1, text: 'Totally agree', createdAt: new Date(Date.now() - 900e3).toISOString(), parentId: `${postId}-c2`, likes: 1 },
    { id: `${postId}-c1-r1`, postId, author: u3, text: 'Same here!', createdAt: new Date(Date.now() - 600e3).toISOString(), parentId: `${postId}-c1`, likes: 0 }
  ]
  state.byPost[postId] = base
}

export type CommentSort = 'newest' | 'top'

export function useComments() {
  function me() {
    return state.me
  }
  function all(postId: string) {
    seedIfNeeded(postId)
    return state.byPost[postId]
  }
  function roots(postId: string) {
    return all(postId).filter(c => !c.parentId)
  }
  function replies(postId: string, parentId: string) {
    return all(postId).filter(c => c.parentId === parentId)
  }
  function add(postId: string, text: string, author?: User) {
    seedIfNeeded(postId)
    const a = author || state.me
    const c: Comment = { id: `${postId}-c${Date.now()}`, postId, author: a, text, createdAt: new Date().toISOString(), likes: 0 }
    state.byPost[postId].unshift(c)
    return c
  }
  function reply(postId: string, parentId: string, text: string, author?: User) {
    seedIfNeeded(postId)
    const a = author || state.me
    const c: Comment = { id: `${postId}-r${Date.now()}`, postId, author: a, text, createdAt: new Date().toISOString(), parentId, likes: 0 }
    state.byPost[postId].unshift(c)
    return c
  }
  function like(commentId: string, postId: string, delta = 1) {
    seedIfNeeded(postId)
    const item = state.byPost[postId].find(c => c.id === commentId)
    if (!item) return
    item.likes = (item.likes || 0) + delta
  }
  function update(postId: string, commentId: string, text: string) {
    seedIfNeeded(postId)
    const item = state.byPost[postId].find(c => c.id === commentId)
    if (!item) return
    item.text = text
  }
  function remove(postId: string, commentId: string) {
    seedIfNeeded(postId)
    state.byPost[postId] = state.byPost[postId].filter(c => c.id !== commentId && c.parentId !== commentId)
  }
  function sort(items: Comment[], by: CommentSort) {
    if (by === 'newest') return [...items].sort((a, b) => +new Date(b.createdAt) - +new Date(a.createdAt))
    return [...items].sort((a, b) => (b.likes || 0) - (a.likes || 0) || (+new Date(b.createdAt) - +new Date(a.createdAt)))
  }
  function paginate(items: Comment[], page: number, pageSize: number) {
    const start = (page - 1) * pageSize
    return items.slice(start, start + pageSize)
  }
  return { ...toRefs(state), me, all, roots, replies, add, reply, like, update, remove, sort, paginate }
}
