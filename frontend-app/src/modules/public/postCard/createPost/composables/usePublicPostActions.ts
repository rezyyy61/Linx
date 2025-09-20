// import { ref } from 'vue'
// import { storeToRefs } from 'pinia'
// import { usePostStore, type Post } from '@/stores/post/Post'
// import type { PostFormInput } from '../adapters/postPayload.adapter'
// import { toPayload } from '../adapters/postPayload.adapter'
//
// export function usePublicPostActions() {
//   const postStore = usePostStore()
//   const { saving, deleting } = storeToRefs(postStore)
//   const error = ref<string | null>(null)
//
//   async function createPost(form: PostFormInput): Promise<Post | null> {
//     error.value = null
//     try {
//       const p = await postStore.create(toPayload(form))
//       return p
//     } catch (e: any) {
//       error.value = e?.response?.data?.message || e?.message || 'create_failed'
//       return null
//     }
//   }
//
//   async function updatePost(id: number, form: PostFormInput): Promise<Post | null> {
//     error.value = null
//     try {
//       const p = await postStore.update(id, toPayload(form))
//       return p
//     } catch (e: any) {
//       error.value = e?.response?.data?.message || e?.message || 'update_failed'
//       return null
//     }
//   }
//
//   async function deletePost(id: number): Promise<boolean> {
//     error.value = null
//     try {
//       await postStore.remove(id)
//       return true
//     } catch (e: any) {
//       error.value = e?.response?.data?.message || e?.message || 'delete_failed'
//       return false
//     }
//   }
//
//   return { createPost, updatePost, deletePost, saving, deleting, error }
// }
