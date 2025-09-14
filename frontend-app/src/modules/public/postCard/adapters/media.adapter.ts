// src/modules/public/postCard/adapters/media.adapter.ts
import type {
  AnyMedia,
  ImageMedia,
  VideoMedia,
  AudioMedia,
  DocumentMedia,
  LinkMedia,
  GalleryMedia,
  GalleryItem,
} from '../types/media.types'

export type ServerMedia = {
  id: number | string
  type?: string
  url?: string
  public_url?: string

  width?: number
  height?: number
  aspect_ratio?: string

  mime?: string
  ext?: string
  size?: number
  key?: string
  filename?: string
  status?: string

  poster_url?: string
  poster_public_url?: string
  cover_url?: string
  cover_public_url?: string
  thumb_url?: string
  thumb_public_url?: string
  thumbnail_url?: string
  thumbnail_public_url?: string

  title?: string
  description?: string
  image?: string
  domain?: string
  meta?: {
    title?: string
    description?: string
    image?: string
    url?: string
    domain?: string
  }

  items?: Array<ServerMedia>
}

const isNonEmpty = (v: any) => v !== undefined && v !== null && v !== ''

function pickUrl(m: ServerMedia): string | undefined {
  return (m.public_url || m.url) as string | undefined
}
function pickPoster(m: ServerMedia): string | undefined {
  return (
    m.poster_public_url ||
    m.poster_url ||
    m.thumb_public_url ||
    m.thumbnail_public_url ||
    m.thumbnail_url ||
    m.thumb_url ||
    m.image ||
    m.meta?.image ||
    undefined
  )
}
function pickCover(m: ServerMedia): string | undefined {
  return (m.cover_public_url || m.cover_url || pickPoster(m)) as string | undefined
}
function pickThumb(m: ServerMedia): string | undefined {
  return (m.thumbnail_public_url || m.thumbnail_url || m.thumb_public_url || m.thumb_url) as string | undefined
}
function filenameFrom(m: ServerMedia): string | undefined {
  if (m.filename) return m.filename
  if (m.key) {
    const parts = String(m.key).split('/')
    return parts[parts.length - 1]
  }
  if (m.url || m.public_url) {
    try {
      const u = new URL((m.public_url || m.url)!)
      const path = u.pathname.split('/')
      return path[path.length - 1]
    } catch { /* empty */ }
  }
  return undefined
}
function aspectRatioFrom(m: ServerMedia): string | undefined {
  if (m.aspect_ratio) return m.aspect_ratio
  if (m.width && m.height && m.width > 0 && m.height > 0) {
    return `${m.width}:${m.height}`
  }
  return undefined
}
function domainFrom(url?: string): string | undefined {
  if (!url) return undefined
  try {
    const u = new URL(url)
    return u.hostname.replace(/^www\./, '')
  } catch {
    return undefined
  }
}
function guessType(m: ServerMedia): AnyMedia['type'] {
  const t = (m.type || '').toLowerCase()
  if (t === 'image' || t === 'video' || t === 'audio' || t === 'document' || t === 'link' || t === 'gallery') {
    return t as AnyMedia['type']
  }

  const mime = (m.mime || '').toLowerCase()
  const ext = (m.ext || '').toLowerCase()
  if (mime.startsWith('image/') || ['png','jpg','jpeg','webp','gif','avif','svg'].includes(ext)) return 'image'
  if (mime.startsWith('video/') || ['mp4','webm','mov','mkv'].includes(ext)) return 'video'
  if (mime.startsWith('audio/') || ['mp3','wav','ogg','m4a','flac'].includes(ext)) return 'audio'
  if (['pdf','doc','docx','xls','xlsx','ppt','pptx','txt'].includes(ext)) return 'document'
  return 'image'
}

export function mapServerMedia(m: ServerMedia): AnyMedia | null {
  const id = String(m.id ?? '')
  if (!id) return null

  const t = guessType(m)
  const url = pickUrl(m)
  if (!url && t !== 'gallery' && t !== 'link' && t !== 'document') return null

  if (t === 'image') {
    const out: ImageMedia = {
      id,
      type: 'image',
      url: url!,
      alt: undefined,
      width: m.width,
      height: m.height,
      aspectRatio: aspectRatioFrom(m),
    }
    return out
  }

  if (t === 'video') {
    const out: VideoMedia = {
      id,
      type: 'video',
      url: url!,
      poster: pickPoster(m),
    }
    return out
  }

  if (t === 'audio') {
    const out: AudioMedia = {
      id,
      type: 'audio',
      url: url!,
      title: m.title || m.meta?.title,
      mime: m.mime,
      durationSec: undefined,
      coverUrl: pickCover(m),
    }
    return out
  }

  if (t === 'document') {
    if (!url) return null
    const out: DocumentMedia = {
      id,
      type: 'document',
      url,
      filename: filenameFrom(m) || 'document',
      mime: m.mime,
      sizeBytes: isNonEmpty(m.size) ? Number(m.size) : undefined,
      thumbnailUrl: pickThumb(m),
    }
    return out
  }

  if (t === 'link') {
    const linkUrl = url || m.meta?.url
    if (!linkUrl) return null
    const out: LinkMedia = {
      id,
      type: 'link',
      url: linkUrl,
      title: m.title || m.meta?.title,
      description: m.description || m.meta?.description,
      image: m.image || m.meta?.image || pickThumb(m) || pickPoster(m),
      domain: m.domain || domainFrom(linkUrl),
    }
    return out
  }

  if (t === 'gallery') {
    const itemsSrc = Array.isArray(m.items) ? m.items : []
    const items: GalleryItem[] = itemsSrc
      .map((it) => {
        const u = pickUrl(it)
        if (!u) return null
        return {
          id: String(it.id ?? u),
          url: u,
          alt: undefined,
          width: it.width,
          height: it.height,
          aspectRatio: aspectRatioFrom(it),
        } as GalleryItem
      })
      .filter(Boolean) as GalleryItem[]

    if (!items.length && url) {
      items.push({
        id,
        url,
        alt: undefined,
        width: m.width,
        height: m.height,
        aspectRatio: aspectRatioFrom(m),
      })
    }

    const out: GalleryMedia = { id, type: 'gallery', items }
    return out
  }

  return null
}

export function mapServerMediaList(list: any[] | null | undefined): AnyMedia[] {
  if (!Array.isArray(list) || !list.length) return []
  return list.map(mapServerMedia).filter(Boolean) as AnyMedia[]
}
