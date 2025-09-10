export type Id = number

export type ProfileLite = {
  id: Id
  name: string | null
  slug: string | null
  avatar: string | null
  avatar_color?: string | null
}

export type Paginated<T> = {
  data: T[]
  meta: { current_page: number; per_page: number; total: number; last_page: number }
}

export type FollowRequestItem = {
  id: Id
  actor_id?: Id
  target_id?: Id
  status: 'pending' | 'accepted' | 'rejected'
  created_at: string
  actor?: ProfileLite
  target?: ProfileLite
}

/* ---------- Members ---------- */

export enum MembershipStatus {
  PENDING = 'pending',
  ACCEPTED = 'accepted',
  REJECTED = 'rejected',
  BLOCKED = 'blocked'
}

export type Membership = {
  id: Id
  owner_id: Id
  member_id: Id | null
  email: string | null
  contact_info?: Record<string, any> | null
  meta?: Record<string, any> | null
  status: MembershipStatus
  consent_at?: string | null
  created_at?: string
  member?: ProfileLite
}

/* ---------- Member Content ---------- */

export enum ContentType {
  NEWSLETTER = 'newsletter',
  EVENT = 'event',
  CAMPAIGN = 'campaign',
  SURVEY = 'survey'
}

export enum ContentStatus {
  DRAFT = 'draft',
  SCHEDULED = 'scheduled',
  SENDING = 'sending',
  SENT = 'sent',
  FAILED = 'failed'
}

export type MemberContent = {
  id: Id
  owner_id: Id
  type: ContentType
  title: string | null
  body: string | null
  options?: Record<string, any> | null
  status: ContentStatus
  scheduled_at?: string | null
  sent_at?: string | null
  created_at?: string
}

/* ---------- Content Targets ---------- */

export enum TargetStatus {
  PENDING = 'pending',
  SENT = 'sent',
  FAILED = 'failed',
  OPENED = 'opened',
  CLICKED = 'clicked'
}

export type ContentTarget = {
  id: Id
  content_id: Id
  membership_id: Id
  channel: 'email' | 'sms' | 'push' | string
  status: TargetStatus
  sent_at?: string | null
  opened_at?: string | null
  clicked_at?: string | null
  error?: string | null
}
