export type LocationType = 'online' | 'venue'

export interface EventDocument {
  id: number
  title: string
  url: string
  mime: string
  size?: number
}

export interface EventSettings {
  type: 'in_person' | 'online' | 'hybrid'
  visibility: 'public' | 'unlisted' | 'private'
  join_url?: string | null
  join_platform?: string | null
  join_passcode?: string | null
  join_instructions?: string | null
  join_visible_minutes_before?: number | null
  access_code?: string | null
  og_title?: string | null
  og_description?: string | null
}

export interface EventPublic {
  id: number
  slug: string
  title: string
  description: string
  starts_at: string
  ends_at: string
  timezone: string

  location_type: LocationType
  location: string
  venue?: {
    name: string
    city?: string
    address?: string
    lat?: number
    lng?: number
  }

  join?: {
    url?: string
    platform?: 'zoom' | 'meet' | 'custom'
    passcode?: string
    visible_minutes_before?: number
  }

  capacity?: number
  going_count: number
  is_published: boolean
  cover_url?: string
  tags: string[]
  price?: number

  organizer: {
    id: number
    name: string
    avatar?: string
    slug: string
  }

  og?: {
    title?: string
    description?: string
  }

  documents?: EventDocument[]

  settings?: EventSettings | null
}
