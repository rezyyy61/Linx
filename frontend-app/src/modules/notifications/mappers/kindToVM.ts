// src/modules/notifications/mappers/kindToVM.ts
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

  const membershipId = pickNum(rec.data, ["membershipId", "membership_id", "entityId", "entity_id", "id"])
  const ownerId = pickNum(rec.data, ["ownerId", "owner_id"])

  const base: NotificationVM & { actor: ActorLite | null } = {
    id: rec.id,
    kind,
    title: a?.username || a?.slug || "",
    body: "",
    avatar: a?.avatar || null,
    href: null,
    handle,
    createdAt: rec.created_at,
    read: !!rec.read_at,
    actor: a
  }

// src/modules/notifications/mappers/kindToVM.ts
  if (kind === "comment.on_post" || kind === "comment.mentioned") {
    const postId =
      pickNum(rec.data, ["post_id"]) ??
      pickNum(rec.data?.post, ["id"]) ?? null
    const commentId =
      pickNum(rec.data, ["comment_id"]) ??
      pickNum(rec.data?.comment, ["id"]) ?? null

    const snippet = String(
      rec.data?.snippet ?? rec.data?.comment?.excerpt ?? ""
    ).trim()

    const title = base.title
    const body =
      kind === "comment.on_post"
        ? (snippet ? `commented: ${snippet}` : "commented on your post")
        : (snippet ? `mentioned you: ${snippet}` : "mentioned you in a comment")

    // هم query و هم hash
    const href = postId
      ? `/p/${postId}${commentId ? `?comment=${commentId}#c-${commentId}` : ""}`
      : null

    return { ...base, title, body, href }
  }


  if (kind === "comment.mentioned") {
    const postId =
      pickNum(rec.data, ["post_id"]) ??
      pickNum(rec.data?.post, ["id"]) ??
      null
    const commentId =
      pickNum(rec.data, ["comment_id"]) ??
      pickNum(rec.data?.comment, ["id"]) ??
      null
    const snippet = String(rec.data?.snippet ?? rec.data?.comment?.excerpt ?? "").trim()
    const title = base.title
    const body = snippet ? `mentioned you: ${snippet}` : "mentioned you in a comment"
    const href = postId ? `/p/${postId}${commentId ? `#c-${commentId}` : ""}` : null
    return { ...base, title, body, href }
  }

  if (kind.startsWith("member.")) {
    return { ...base, membershipId, ownerId }
  }

  return base
}
