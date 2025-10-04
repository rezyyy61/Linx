export type CampaignVisibility = "public" | "members" | "private"
export type CampaignStatus = "draft" | "published" | "paused" | "completed" | "failed" | "archived"
export type CampaignKind = "fundraising" | "petition" | "volunteer" | "awareness"

export type CampaignMeta = {
  goal_amount?: number | null
  goal_currency?: string | null
  raised_amount?: number | null
  signature_goal?: number | null
  signatures_count?: number | null
  needed_slots?: number | null
  filled_slots?: number | null
  target_reach?: number | null
  current_reach?: number | null
}

export type CampaignRow = {
  id: number
  owner_id: number
  title: string
  slug: string
  excerpt?: string | null
  description?: string | null
  kind: CampaignKind
  status: CampaignStatus
  visibility: CampaignVisibility
  starts_at?: string | null
  ends_at?: string | null
  publish_at?: string | null
  cover_id?: number | null
  cover_url?: string | null
  documents?: Array<{ id: number; url: string | null }>
  meta?: CampaignMeta
  created_at?: string | null
  updated_at?: string | null
}

export type CampaignQuery = {
  q?: string
  kind?: CampaignKind | ""
  status?: CampaignStatus | ""
  visibility?: CampaignVisibility | ""
  order_by?: "publish_at" | "created_at" | "updated_at" | "title" | "status"
  order_dir?: "asc" | "desc"
  page?: number
  per_page?: number
}

export type Paged<T> = { data: T[]; meta: { page: number; per_page: number; total: number; last_page: number } }

export type CreateCampaignPayload = Omit<CampaignRow, "id"|"created_at"|"updated_at"|"slug"|"owner_id"> & {
  owner_id?: number
}

export type UpdateCampaignPayload = Partial<CreateCampaignPayload>
