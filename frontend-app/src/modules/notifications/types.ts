export type NotificationKind = "follow.accepted" | "follow.rejected" | "follow.unfollowed" | string

export interface ActorLite {
  id: number
  slug?: string | null
  username?: string | null
  avatar?: string | null
}

export interface NotificationRecord {
  id: number
  type: string
  data: any
  read_at: string | null
  created_at: string
}

export interface PaginatedResponse<T> {
  data: T[]
  current_page: number
  per_page: number
  total: number
  last_page: number
}

export interface NotificationVM {
  id: number
  kind: NotificationKind
  title: string
  body: string
  avatar?: string | null
  href?: string | null
  handle?: string | null
  createdAt: string
  read: boolean
}
