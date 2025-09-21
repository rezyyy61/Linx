import { api } from "@/lib/http"
import type { EventPublic } from "../types"

export async function fetchEvents(params?: Record<string, any>) {
  const res = await api.get<{ data: EventPublic[] }>("/public/v1/events", { params })
  return res.data
}

export async function fetchEvent(slug: string): Promise<EventPublic> {
  const res = await api.get(`/public/v1/events/${slug}`)
  const payload = res.data as any
  return (payload?.data ?? payload) as EventPublic
}

export async function joinEvent(eventId: number) {
  await api.post(`/events/${eventId}/join`)
  return { joined: true }
}

export async function unjoinEvent(eventId: number) {
  await api.delete(`/events/${eventId}/join`)
  return { joined: false }
}

export async function getJoinStatus(eventId: number) {
  const res = await api.get<{ joined: boolean; count: number }>(`/events/${eventId}/join`)
  return res.data
}
