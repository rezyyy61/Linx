export type PostFields = {
  content?: string | null
  visibility?: 'public' | 'private' | 'friends'
  status?: 'draft' | 'published'
}
export type MediaOrder = { id: number; order?: number }

export function buildPostPayload(fields: PostFields = {}, media?: MediaOrder[] | null) {
  const out: any = {}
  if ('content' in fields) out.content = fields.content ?? null
  if ('visibility' in fields) out.visibility = fields.visibility
  if ('status' in fields) out.status = fields.status
  if (Array.isArray(media)) out.media = media
  else if (media === null) out.media = null
  return out
}
