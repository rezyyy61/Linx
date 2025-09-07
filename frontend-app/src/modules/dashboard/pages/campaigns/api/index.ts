import { api, ensureCsrfCookie } from "@/lib/http";
import type { Campaign, Paginated } from "../types";

export interface CampaignPayload {
  title?: string;
  goal?: number | null;
  description?: string | null;
  starts_at?: string | null;
  ends_at?: string | null;
  status?: "draft" | "running" | "paused" | "ended";
  donation_enabled?: boolean;
  slug?: string | null;
  cover_id?: number | null;
  documents?: Array<{ id: number; order?: number }> | null;
}

function unwrap<T>(x: any): T {
  return (x && typeof x === "object" && "data" in x) ? (x.data as T) : (x as T);
}

export async function listCampaigns(page = 1): Promise<Paginated<Campaign>> {
  const res = await api.get("/campaigns", { params: { page } });
  const data = res.data?.data ?? [];
  const meta = res.data?.meta ?? { current_page: 1, per_page: data.length, total: data.length, last_page: 1 };
  return { data: data as Campaign[], meta };
}

export async function getCampaign(id: number): Promise<Campaign> {
  const res = await api.get(`/campaigns/${id}`);
  return unwrap<Campaign>(res.data);
}

export async function createCampaign(
  payload: Required<Pick<CampaignPayload, "title" | "status">> & CampaignPayload
): Promise<Campaign> {
  await ensureCsrfCookie();
  const res = await api.post("/campaigns", payload);
  return unwrap<Campaign>(res.data);
}

export async function updateCampaign(id: number, payload: CampaignPayload): Promise<Campaign> {
  await ensureCsrfCookie();
  const res = await api.put(`/campaigns/${id}`, payload);
  return unwrap<Campaign>(res.data);
}

export async function deleteCampaign(id: number): Promise<void> {
  await ensureCsrfCookie();
  await api.delete(`/campaigns/${id}`);
}
