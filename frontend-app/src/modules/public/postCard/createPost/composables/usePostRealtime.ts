// import { ref } from 'vue'
// import { subscribePrivate, unsubscribe } from '@/lib/echo'
// import { usePostStore } from '@/stores/post/Post'
// import type { Post } from '@/stores/post/Post'
//
// type Handlers = {
//   onCreated?: (p: Post) => void
//   onUpdated?: (p: Post) => void
//   onDeleted?: (id: number) => void
// }
//
// export function usePostRealtime(channel: string, handlers: Handlers = {}) {
//   const bound = ref(false)
//   const name = ref<string | null>(null)
//   const postStore = usePostStore()
//
//   async function start() {
//     if (bound.value) return
//     const ch = await subscribePrivate(channel)
//
//     ch.unbind('PublicPostCreated')
//     ch.unbind('PublicPostUpdated')
//     ch.unbind('PublicPostDeleted')
//
//     ch.bind('PublicPostCreated', async (p: any) => {
//       const id = Number(p?.id ?? p?.data?.id ?? p?.post_id)
//       if (!Number.isFinite(id)) return
//       const post = await postStore.fetchOne(id)
//       handlers.onCreated?.(post)
//     })
//
//     ch.bind('PublicPostUpdated', async (p: any) => {
//       const id = Number(p?.id ?? p?.data?.id ?? p?.post_id)
//       if (!Number.isFinite(id)) return
//       const post = await postStore.fetchOne(id)
//       handlers.onUpdated?.(post)
//     })
//
//     ch.bind('PublicPostDeleted', (p: any) => {
//       const id = Number(p?.id ?? p?.data?.id ?? p?.post_id)
//       if (!Number.isFinite(id)) return
//       delete postStore.byId[id]
//       postStore.ids = postStore.ids.filter((i) => i !== id)
//       handlers.onDeleted?.(id)
//     })
//
//     name.value = channel
//     bound.value = true
//   }
//
//   async function stop() {
//     if (!bound.value || !name.value) return
//     try { await unsubscribe(name.value) } catch { /* empty */ }
//     name.value = null
//     bound.value = false
//   }
//
//   return { start, stop, bound }
// }
