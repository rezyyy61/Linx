export type MediaKind = 'image' | 'video' | 'audio' | 'document'

export const MEDIA_LIMITS = {
  total: 10,
  image: 10,
  video: 1,
  audio: 1,
  document: 3,
}

export type Counts = { total: number; image: number; video: number; audio: number; document: number }

export function emptyCounts(): Counts {
  return { total: 0, image: 0, video: 0, audio: 0, document: 0 }
}

export function kindFromMime(m?: string): MediaKind | null {
  const m2 = String(m || '').toLowerCase()
  if (m2.startsWith('image/')) return 'image'
  if (m2.startsWith('video/')) return 'video'
  if (m2.startsWith('audio/')) return 'audio'
  if (m2) return 'document'
  return null
}

export function kindFromFile(f: File): MediaKind {
  const t = String(f.type || '').toLowerCase()
  if (t.startsWith('image/')) return 'image'
  if (t.startsWith('video/')) return 'video'
  if (t.startsWith('audio/')) return 'audio'
  return 'document'
}

export function remainingFrom(counts: Counts) {
  return {
    total: Math.max(0, MEDIA_LIMITS.total - counts.total),
    image: Math.max(0, MEDIA_LIMITS.image - counts.image),
    video: Math.max(0, MEDIA_LIMITS.video - counts.video),
    audio: Math.max(0, MEDIA_LIMITS.audio - counts.audio),
    document: Math.max(0, MEDIA_LIMITS.document - counts.document),
  }
}

export function filterAllowed(files: File[], counts: Counts) {
  const picked: File[] = []
  const rejected: File[] = []
  const c = { ...counts }
  for (const f of files) {
    const k = kindFromFile(f)
    const lim = MEDIA_LIMITS[k]
    if (c.total >= MEDIA_LIMITS.total) { rejected.push(f); continue }
    if ((c as any)[k] >= lim) { rejected.push(f); continue }
    picked.push(f)
    c.total++
    ;(c as any)[k]++
  }
  return { picked, rejected, nextCounts: c, nextRemaining: remainingFrom(c) }
}
