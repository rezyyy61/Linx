import type { Comment } from '../types/comment.types'

type ServerAuthor = {
  id: number | string
  name?: string | null
  slug?: string | null
  avatar?: string | null
  avatarColor?: string | null
}

type ServerComment = {
  id: number | string
  post_id: number | string
  parent_id?: number | string | null
  body: string
  created_at?: string | null
  updated_at?: string | null
  author?: ServerAuthor
  counts?: { likes?: number }
  liked?: boolean
}

export function mapServerComment(c: ServerComment): Comment {
  const a: ServerAuthor = c.author ?? {
    id: '',
    name: 'Unknown',
    slug: '',
    avatar: null,
    avatarColor: null,
  }

  return {
    id: String(c.id),
    postId: String(c.post_id),
    parentId: c.parent_id == null ? null : String(c.parent_id),
    body: c.body ?? '',
    createdAt: c.created_at ?? new Date().toISOString(),
    updatedAt: c.updated_at ?? null,
    author: {
      id: String(a.id ?? ''),
      name: a.name ?? 'Unknown',
      username: a.slug ?? '',
      avatarUrl: a.avatar ?? null,
      avatarColor: a.avatarColor ?? null,
      verified: false,
    },
    likes: Number(c.counts?.likes ?? 0),
    liked: Boolean(c.liked ?? false),
  }
}
