import type { CampaignRow, CampaignQuery, CreateCampaignPayload, UpdateCampaignPayload, Paged } from "./types"
import { api } from "@/lib/http"

function qs(obj: Record<string, any>) {
  const entries = Object.entries(obj)
    .filter(([, v]) => v !== undefined && v !== null && v !== "")
    .flatMap(([k, v]) => Array.isArray(v) ? v.map(x => [k, String(x)]) : [[k, String(v)]])
  return entries.length ? `?${entries.map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`).join("&")}` : ""
}

export async function listCampaigns(query: CampaignQuery = {}): Promise<Paged<CampaignRow>> {
  const res = await api.get(`/campaigns${qs(query)}`)
  return res.data as Paged<CampaignRow>
}

export async function getCampaign(id: number): Promise<CampaignRow> {
  const res = await api.get(`/campaigns/${id}`)
  return res.data.data as CampaignRow
}

export async function createCampaign(payload: CreateCampaignPayload): Promise<CampaignRow> {
  const res = await api.post(`/campaigns`, payload)
  return res.data.data as CampaignRow
}

export async function updateCampaign(id: number, payload: UpdateCampaignPayload): Promise<CampaignRow> {
  const res = await api.put(`/campaigns/${id}`, payload)
  return res.data.data as CampaignRow
}

export async function deleteCampaign(id: number): Promise<void> {
  await api.delete(`/campaigns/${id}`)
}
