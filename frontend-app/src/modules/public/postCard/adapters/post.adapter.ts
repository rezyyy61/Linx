import type { Post } from '../types/post.types'
import { mapServerMedia } from './media.adapter'
import type { AnyMedia, GalleryMedia, GalleryItem, ImageMedia } from '../types/media.types'

type ServerAuthor = {
  id: number | string
  name?: string
  slug?: string
  avatar?: string | null
  avatarColor?: string | null
}

type ServerCounts = {
  likes?: number
  comments?: number
  shares?: number
  saves?: number
  views?: number
}

type ServerPost = {
  id: number | string
  content?: string | null
  visibility: 'public' | 'private' | 'friends' | 'unlisted'
  status?: string
  published_at?: string | null
  created_at?: string | null
  updated_at?: string | null
  author?: ServerAuthor
  media?: any[] | null
  counts?: ServerCounts | null
  liked?: boolean
}

function packImagesAsGallery(list: AnyMedia[] | null | undefined): AnyMedia[] {
  if (!list || !list.length) return [];
  if (list.some(m => m.type === 'gallery')) return list;
  const images: ImageMedia[] = list.filter(m => m.type === 'image') as ImageMedia[];
  const others: AnyMedia[] = list.filter(m => m.type !== 'image');
  if (images.length <= 1 || others.length > 0) return list;

  const items: GalleryItem[] = images.map(img => ({
    id: img.id,
    url: img.url,
    alt: img.alt,
    width: img.width,
    height: img.height,
    aspectRatio: img.aspectRatio || '1/1',
  }));
  const gallery: GalleryMedia = { id: `gallery_${items.map(i => i.id).join('_')}`, type: 'gallery', items };
  return [gallery];
}


export function mapServerPost(p: ServerPost): Post {
  const raw = Array.isArray(p.media) ? p.media.map(mapServerMedia).filter(Boolean) as AnyMedia[] : [];
  const media = packImagesAsGallery(raw);
  const a: ServerAuthor = p.author ?? { id: '', name: 'Unknown', slug: '', avatar: null }
  const visibility: Post['visibility'] =
    p.visibility === 'friends' ? 'followers'
      : (['public','private','unlisted'].includes(p.visibility) ? p.visibility as Post['visibility'] : 'public')
  const c = p.counts || {}
  return {
    id: String(p.id),
    author: {
      id: String(a.id ?? ''),
      name: a.name ?? 'Unknown',
      username: a.slug ?? '',
      avatarUrl: a.avatar ?? null,
      avatarColor: a.avatarColor ?? null,
      verified: false,
    },
    createdAt: p.published_at ?? p.created_at ?? new Date().toISOString(),
    editedAt: p.updated_at && p.updated_at !== p.created_at ? p.updated_at : null,
    visibility,
    text: (p.content ?? '').toString(),
    media,
    counts: {
      likes: Number(c.likes ?? 0),
      comments: Number(c.comments ?? 0),
      shares: Number(c.shares ?? 0),
      saves: Number(c.saves ?? 0),
      views: Number(c.views ?? 0),
    },
    isPinned: false,
    isRepost: false,
    originalPostId: null,
    liked: !!p.liked,
  }
}
