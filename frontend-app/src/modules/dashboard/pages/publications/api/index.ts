// /home/rezyyy/PhpstormProjects/Linx/frontend-app/src/modules/dashboard/pages/publications/api/index.ts
import { api, ensureCsrfCookie } from "@/lib/http";
import type { Publication, Paginated } from "../types";

export interface PublicationPayload {
  title?: string;
  issue?: string;
  description?: string | null;
  slug?: string | null;
  is_published?: boolean;
  publish_at?: string | null;
  language?: string | null;
  cover_id?: number | null;
  documents?: Array<{ id: number; order?: number }> | null;
}

function unwrap<T>(x: any): T {
  return (x && typeof x === "object" && "data" in x) ? (x.data as T) : (x as T);
}

export async function listPublications(params: {
  page?: number;
  per_page?: number;
  q?: string;
  published?: boolean;
  published_from?: string;
  published_to?: string;
  order_by?: "publish_at" | "created_at" | "updated_at";
  order_dir?: "asc" | "desc";
} = {}): Promise<Paginated<Publication>> {
  const { data } = await api.get("/publications", { params });
  return {
    data: unwrap<Publication[]>(data.data ?? data),
    meta: data.meta ?? { current_page: 1, per_page: params.per_page ?? 15, total: 0, last_page: 1 }
  };
}

export async function getPublication(id: number): Promise<Publication> {
  const { data } = await api.get(`/publications/${id}`);
  return unwrap<Publication>(data);
}

export async function createPublication(payload: Required<Pick<PublicationPayload, "title" | "issue">> & PublicationPayload): Promise<Publication> {
  await ensureCsrfCookie();
  const { data } = await api.post("/publications", payload);
  return unwrap<Publication>(data);
}

export async function updatePublication(id: number, payload: PublicationPayload): Promise<Publication> {
  await ensureCsrfCookie();
  const { data } = await api.put(`/publications/${id}`, payload);
  return unwrap<Publication>(data);
}

export async function deletePublication(id: number): Promise<void> {
  await ensureCsrfCookie();
  await api.delete(`/publications/${id}`);
}
