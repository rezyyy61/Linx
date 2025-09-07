import { api, ensureCsrfCookie } from "@/lib/http";
import type { Announcement, Paginated } from "../types";

export interface AnnouncementPayload {
  title?: string;
  body?: string | null;
  slug?: string | null;
  is_pinned?: boolean;
  visibility?: "public" | "members" | "supporters" | "private";
  publish_at?: string | null;
  cover_id?: number | null;
  documents?: Array<{ id: number; order?: number }> | null;
}

function unwrap<T>(x: any): T {
  return (x && typeof x === "object" && "data" in x) ? (x.data as T) : (x as T);
}

export async function listAnnouncements(params: {
  page?: number;
  per_page?: number;
  q?: string;
  visibility?: Announcement["visibility"];
  pinned?: boolean;
  order_by?: "publish_at" | "created_at" | "updated_at" | "is_pinned";
  order_dir?: "asc" | "desc";
} = {}): Promise<Paginated<Announcement>> {
  const { data } = await api.get("/announcements", { params });
  return {
    data: unwrap<Announcement[]>(data.data ?? data),
    meta: data.meta ?? { current_page: 1, per_page: params.per_page ?? 15, total: 0, last_page: 1 }
  };
}

export async function getAnnouncement(id: number): Promise<Announcement> {
  const { data } = await api.get(`/announcements/${id}`);
  return unwrap<Announcement>(data);
}

export async function createAnnouncement(payload: Required<Pick<AnnouncementPayload,"title">> & AnnouncementPayload): Promise<Announcement> {
  await ensureCsrfCookie();
  const { data } = await api.post("/announcements", payload);
  return unwrap<Announcement>(data);
}

export async function updateAnnouncement(id: number, payload: AnnouncementPayload): Promise<Announcement> {
  await ensureCsrfCookie();
  const { data } = await api.put(`/announcements/${id}`, payload);
  return unwrap<Announcement>(data);
}

export async function deleteAnnouncement(id: number): Promise<void> {
  await ensureCsrfCookie();
  await api.delete(`/announcements/${id}`);
}
