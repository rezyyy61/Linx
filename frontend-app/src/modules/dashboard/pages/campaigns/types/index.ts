export type CampaignStatus = "draft" | "running" | "paused" | "ended";

export type MediaRef = {
  id: number;
  url: string | null;
  order: number;
};

export interface Campaign {
  id: number;
  owner_id: number;
  title: string;
  goal: number | null;
  description: string | null;
  starts_at: string | null;
  ends_at: string | null;
  status: CampaignStatus;
  donation_enabled: boolean;
  slug: string | null;
  cover_url?: string | null;
  covers?: MediaRef[];
  documents?: MediaRef[];
  created_at?: string;
  updated_at?: string;
}

export interface Paginated<T> {
  data: T[];
  meta: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
}

export const QK_CAMPAIGNS = "campaigns";
export const QK_CAMPAIGN = (id: number) => ["campaign", id] as const;
export const QK_CAMPAIGN_STATS = (id: number) => ["campaign-stats", id] as const;
export const QK_CONTENT = (campaignId: number, contentId: number) =>
  ["campaign-content", campaignId, contentId] as const;
