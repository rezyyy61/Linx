import { api } from "@/lib/http"
import type { NotificationRecord, PaginatedResponse } from "../types"

export async function fetchNotifications(page = 1, perPage = 15): Promise<PaginatedResponse<NotificationRecord>> {
  const { data } = await api.get("/notifications", { params: { page, per_page: perPage } })
  return data
}

export async function markAsRead(id: number): Promise<void> {
  await api.post(`/notifications/${id}/read`)
}

export async function markAllAsRead(): Promise<number> {
  const { data } = await api.post("/notifications/read-all")
  return Number(data?.updated ?? 0)
}

export async function removeNotification(id: number): Promise<void> {
  await api.delete(`/notifications/${id}`)
}
