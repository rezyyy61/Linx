export type MediaRef = { id: number }
export type PostPayload = { content?: string|null; visibility?: 'public'|'private'|'friends'; status?: 'draft'|'published'|'archived'; published_at?: string|null; media?: { id:number; order:number }[]|null }

export function buildPostPayload(base: Partial<PostPayload>, media: MediaRef[]) {
  const m = media.map((x, i) => ({ id: Number(x.id), order: i }))
  return { ...base, media: m }
}
