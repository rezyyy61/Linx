import { api } from "@/lib/http"

export type Visibility = "public" | "members" | "supporters" | "private"

export type AnnouncementPublic = {
  id: number
  slug: string
  title: string
  excerpt: string | null
  body?: string | null
  is_pinned: boolean
  visibility: Visibility
  publish_at: string | null
  cover_url: string | null
  read_time?: number | null
  views?: number
  owner?: { name?: string | null; slug?: string | null; avatar?: string | null } | null
}

export async function fetchAnnouncements(params?: Record<string, any>) {
  const res = await api.get<{ data: AnnouncementPublic[] }>(
    "/public/v1/announcements",
    { params }
  )
  return res.data
}

export async function fetchAnnouncement(slug: string): Promise<AnnouncementPublic> {
  const res = await api.get(`/public/v1/announcements/${slug}`)
  const payload = res.data as any
  return (payload?.data ?? payload) as AnnouncementPublic
}
