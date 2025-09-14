import type { UserSummary } from '../types/post.types'

export type ServerMiniUser = {
  id: number | string
  name?: string | null
  slug?: string | null
  avatar?: string | null
  avatarColor?: string | null
  verified?: boolean | null
}

export function mapServerMiniUser(u: ServerMiniUser): UserSummary {
  return {
    id: String(u.id ?? ''),
    name: u.name ?? 'Unknown',
    username: u.slug ?? '',
    avatarUrl: u.avatar ?? null,
    avatarColor: u.avatarColor ?? null,
    verified: !!u.verified,
  }
}
