import type { Post } from '../types/post.types'

type AnyObj = Record<string, any>

export function attachRepostFields(post: Post, raw: AnyObj): Post {
  const originalId =
    Number(
      raw?.repost_of_id ??
      raw?.repostOfId ??
      raw?.original_id ??
      raw?.originalId ??
      raw?.original?.id ??
      0
    ) || undefined

  return {
    ...(post as any),
    repostOfId: originalId,
    original: raw?.original ?? (post as any).original,
  } as Post
}
