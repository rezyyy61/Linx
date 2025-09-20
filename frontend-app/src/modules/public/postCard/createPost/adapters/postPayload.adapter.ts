// import type { Post, PostPayload, MediaItem } from '@/stores/post/Post'
//
// export type PostFormInput = {
//   content: string | null
//   visibility: 'public' | 'private' | 'friends'
//   status: 'draft' | 'published'
//   media: { id: number; order?: number }[] | null
// }
//
// export function toPayload(form: PostFormInput): PostPayload {
//   return {
//     content: form.content ?? null,
//     visibility: form.visibility,
//     status: form.status,
//     media: form.media ? form.media.map(m => ({ id: m.id, order: m.order })) : null,
//   }
// }
//
// export function toForm(post: Post): PostFormInput {
//   const media = (post.media ?? []).map((m: MediaItem, i: number) => ({ id: m.id, order: typeof m.order === 'number' ? m.order : i }))
//   return {
//     content: post.content ?? null,
//     visibility: post.visibility,
//     status: post.status === 'archived' ? 'draft' : post.status,
//     media,
//   }
// }
