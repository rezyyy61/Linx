export type CampaignVisibility = "public" | "members" | "private"
export type CampaignStatus = "draft" | "published" | "paused" | "completed" | "failed" | "archived"
export type CampaignKind = "fundraising" | "petition" | "volunteer" | "awareness"
export type CampaignOwner = { id: number; name: string; slug?: string | null; avatar?: string | null }

export type DateRange = "all" | "today" | "this_week" | "this_month"

type BaseCampaign = {
  id: number
  slug: string
  title: string
  excerpt?: string | null
  description?: string | null
  kind: CampaignKind
  status: CampaignStatus
  visibility: CampaignVisibility
  starts_at?: string | null
  ends_at?: string | null
  publish_at?: string | null
  cover_url?: string | null
  owner?: CampaignOwner | null
  created_at?: string | null
  updated_at?: string | null
}

export type FundraisingCampaign = BaseCampaign & {
  kind: "fundraising"
  goal_amount?: number | null
  goal_currency?: string | null
  raised_amount?: number | null
}

export type PetitionCampaign = BaseCampaign & {
  kind: "petition"
  signature_goal?: number | null
  signatures_count?: number | null
}

export type VolunteerCampaign = BaseCampaign & {
  kind: "volunteer"
  needed_slots?: number | null
  filled_slots?: number | null
}

export type AwarenessCampaign = BaseCampaign & {
  kind: "awareness"
  target_reach?: number | null
  current_reach?: number | null
}

export type CampaignPublic =
  | FundraisingCampaign
  | PetitionCampaign
  | VolunteerCampaign
  | AwarenessCampaign

export type CampaignQuery = {
  q?: string
  kind?: CampaignKind | ""
  status?: CampaignStatus | ""
  visibility?: CampaignVisibility | ""
  date_range?: DateRange
  order_by?: "publish_at" | "created_at" | "ends_at" | "progress"
  order_dir?: "asc" | "desc"
  page?: number
  per_page?: number
}

export type Paged<T> = { data: T[]; meta: { page: number; per_page: number; total: number; last_page: number } }

export function isFundraising(c: CampaignPublic): c is FundraisingCampaign { return c.kind === "fundraising" }
export function isPetition(c: CampaignPublic): c is PetitionCampaign { return c.kind === "petition" }
export function isVolunteer(c: CampaignPublic): c is VolunteerCampaign { return c.kind === "volunteer" }
export function isAwareness(c: CampaignPublic): c is AwarenessCampaign { return c.kind === "awareness" }

export type DonatePayload = { campaignId: number; amount: number; currency?: string | null; name?: string | null; email?: string | null }
export type SignPayload = { campaignId: number; name: string; email: string }
export type VolunteerPayload = { campaignId: number; name: string; email: string; role?: string | null }
export type BoostPayload = { campaignId: number; channel?: "copy" | "share" }
