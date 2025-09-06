export type UserPublic = {
  id: number
  slug: string
  name: string
  avatar?: string | null
  avatar_color?: string | null
  entity_type: 'person' | 'party' | string
  verified?: boolean
  followers_count?: number
  followings_count?: number
  location?: string | null
  founded_year?: number | null
  cover?: string | null
  website?: string | null
}
