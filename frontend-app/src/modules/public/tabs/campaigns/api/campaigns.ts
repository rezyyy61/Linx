import { api } from "@/lib/http"
import type { CampaignPublic, CampaignQuery, Paged } from "./types"

function cleanParams<T extends Record<string, any>>(obj?: T): Record<string, any> {
  const out: Record<string, any> = {}
  if (!obj) return out
  for (const [k, v] of Object.entries(obj)) {
    if (v === undefined || v === null || v === "") continue
    out[k] = v
  }
  return out
}

export async function fetchCampaigns(query: CampaignQuery = {}): Promise<Paged<CampaignPublic>> {
  const params = cleanParams(query)
  const res = await api.get("/public/v1/campaigns", { params })
  const payload = res.data as {
    data: CampaignPublic[];
    meta?: { current_page: number; per_page: number; total: number; last_page: number };
  }


  const meta = payload.meta
    ? {
      page: payload.meta.current_page,
      per_page: payload.meta.per_page,
      total: payload.meta.total,
      last_page: payload.meta.last_page,
    }
    : { page: 1, per_page: payload.data?.length ?? 0, total: payload.data?.length ?? 0, last_page: 1 }

  return { data: payload.data || [], meta }
}

export async function fetchCampaign(slug: string): Promise<CampaignPublic | null> {
  const res = await api.get(`/public/v1/campaigns/${encodeURIComponent(slug)}`)
  const payload = res.data as any
  return (payload?.data ?? payload) as CampaignPublic
}
