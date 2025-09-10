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

function pickNum(obj: any, keys: string[]): number | null {
  for (const k of keys) {
    const v = obj?.[k]
    const n = Number(v)
    if (Number.isFinite(n)) return n
  }
  return null
}

export function mapRecordToVM(
  rec: NotificationRecord
): NotificationVM & {
  actor: ActorLite | null
  membershipId?: number | null
  ownerId?: number | null
} {
  const kind: NotificationKind = rec.data?.kind || ""
  const a = actorOf(rec)
  const handle = a?.slug || a?.username || null
  const href = handle ? `/u/${handle}` : null

  const membershipId = pickNum(rec.data, ["membershipId", "membership_id", "entityId", "entity_id", "id"])
  const ownerId = pickNum(rec.data, ["ownerId", "owner_id"])

  const base: NotificationVM & { actor: ActorLite | null } = {
    id: rec.id,
    kind,
    title: a?.username || a?.slug || "",
    body: "",
    avatar: a?.avatar || null,
    href,
    handle,
    createdAt: rec.created_at,
    read: !!rec.read_at,
    actor: a
  }

  if (kind.startsWith("member.")) {
    return { ...base, membershipId, ownerId }
  }
  return base
}
