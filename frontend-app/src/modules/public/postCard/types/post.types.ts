import type { AnyMedia } from './media.types'

export type Visibility = 'public' | 'private' | 'unlisted' | 'friends' | 'followers'

export interface UserSummary {
  id: string
  name: string
  username: string
  avatarUrl?: string | null
  avatarColor?: string | null
  verified?: boolean
  isFollowing?: boolean
}

export interface PostCounts {
  likes: number
  comments: number
  shares: number
  saves: number
  views?: number
}

export interface Post {
  id: string
  author: UserSummary
  createdAt: string
  editedAt?: string | null
  visibility: Visibility
  text?: string | null
  media?: AnyMedia[] | null
  counts: PostCounts
  isPinned?: boolean
  isRepost?: boolean
  originalPostId?: string | null
  liked?: boolean
  postableAlias?: string | null
  postableSlug?: string | null
  postableType?: string | null
  postableId?: number | null
  postable?: any | null
}
