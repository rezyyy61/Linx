export type MediaKind = 'image'|'video'|'audio'|'document'

export const maxBytes: Record<MediaKind, number> = {
  image: 10 * 1024 * 1024,
  video: 200 * 1024 * 1024,
  audio: 20 * 1024 * 1024,
  document: 20 * 1024 * 1024,
}

const imageExts = ['jpg','jpeg','png','webp','gif','bmp','tiff','tif']
const videoExts = ['mp4','mov','mkv','webm','avi']
const audioExts = ['mp3','aac','m4a','wav','flac','ogg','oga']
const docExts   = ['pdf','doc','docx','xls','xlsx','ppt','pptx','txt']

const docMimes = [
  'application/pdf',
  'application/msword',
  'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
  'application/vnd.ms-excel',
  'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
  'application/vnd.ms-powerpoint',
  'application/vnd.openxmlformats-officedocument.presentationml.presentation',
  'text/plain',
  'application/vnd.ms-excel.sheet.macroenabled.12',
  'application/vnd.ms-powerpoint.presentation.macroenabled.12',
]

export function humanBytes(n: number) {
  if (!Number.isFinite(n)) return ''
  const u = ['B','KB','MB','GB','TB']
  let i = 0, v = n
  while (v >= 1024 && i < u.length - 1) { v /= 1024; i++ }
  return `${v.toFixed(v < 10 && i > 0 ? 1 : 0)} ${u[i]}`
}

const extOf = (name: string) => {
  const i = name.lastIndexOf('.')
  return i >= 0 ? name.slice(i + 1).toLowerCase() : ''
}

function kindByMime(mime: string): MediaKind | null {
  if (!mime) return null
  if (mime.startsWith('video/')) return 'video'
  if (mime.startsWith('image/')) return 'image'
  if (mime.startsWith('audio/')) return 'audio'
  if (docMimes.includes(mime)) return 'document'
  return null
}

function kindByExt(ext: string): MediaKind | null {
  if (!ext) return null
  if (imageExts.includes(ext)) return 'image'
  if (videoExts.includes(ext)) return 'video'
  if (audioExts.includes(ext)) return 'audio'
  if (docExts.includes(ext)) return 'document'
  return null
}

export function kindOfFile(f: File): MediaKind {
  const byMime = kindByMime(f.type)
  if (byMime) return byMime
  const byExt = kindByExt(extOf(f.name))
  if (byExt) return byExt
  return 'document'
}

export function isAllowed(kind: MediaKind, mime: string, ext: string) {
  if (kind === 'image') return /^image\//.test(mime) || imageExts.includes(ext)
  if (kind === 'video') return /^video\//.test(mime) || videoExts.includes(ext)
  if (kind === 'audio') return /^audio\//.test(mime) || audioExts.includes(ext)
  return docMimes.includes(mime) || docExts.includes(ext)
}

export function validateFile(file: File, forcedKind?: MediaKind) {
  const ext = extOf(file.name)
  const detected = kindOfFile(file)
  const kind = forcedKind ?? detected
  if (!isAllowed(kind, file.type || '', ext)) return { ok: false as const, reason: 'mime' as const, kind }
  const lim = maxBytes[kind]
  if (file.size > lim) return { ok: false as const, reason: 'size' as const, kind, limit: lim }
  return { ok: true as const, reason: null, kind }
}
