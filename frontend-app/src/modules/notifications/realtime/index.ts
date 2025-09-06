import type { NotificationRecord } from "../types"
import { subscribePrivate, unsubscribe } from "@/lib/echo"

export type UnsubscribeFn = () => void

export async function subscribeUserNotifications(userId: number, onCreated: (rec: NotificationRecord) => void): Promise<UnsubscribeFn> {
  const channel = await subscribePrivate(`user.${userId}`)
  const handler = (payload: any) => {
    const rec: NotificationRecord = {
      id: Number(payload?.id),
      type: String(payload?.type || ""),
      data: payload?.data ?? {},
      read_at: payload?.read_at ?? null,
      created_at: String(payload?.created_at || new Date().toISOString()),
    }
    onCreated(rec)
  }
  channel.bind("notification.created", handler)
  return () => {
    channel.unbind("notification.created", handler)
    unsubscribe(`user.${userId}`)
  }
}
