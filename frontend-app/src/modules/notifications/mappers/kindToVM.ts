import type { NotificationRecord, NotificationVM, NotificationKind, ActorLite } from "../types"

function actorOf(rec: NotificationRecord): ActorLite | null {
  const a = rec.data?.actor || null
  if (!a) return null
  return {
    id: Number(a.id),
    slug: a.slug ?? (a.username ?? null),
    username: a.username ?? null,
    avatar: a.avatar ?? null
  }
}

export function mapRecordToVM(rec: NotificationRecord): NotificationVM & { actor: ActorLite | null } {
  const kind: NotificationKind = rec.data?.kind || ""
  const a = actorOf(rec)
  const handle = a?.slug || a?.username || null
  const href = handle ? `/u/${handle}` : null
  return {
    id: rec.id,
    kind,
    title: "",
    body: "",
    avatar: a?.avatar || null,
    href,
    handle,
    createdAt: rec.created_at,
    read: !!rec.read_at,
    actor: a
  }
}
