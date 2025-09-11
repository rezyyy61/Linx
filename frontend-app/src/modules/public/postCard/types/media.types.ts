export type MediaType = 'image' | 'video' | 'gallery' | 'link' | 'document' | 'audio'

export interface MediaBase {
  id: string
  type: MediaType
}

export interface ImageMedia extends MediaBase {
  type: 'image'
  url: string
  alt?: string
  width?: number
  height?: number
  aspectRatio?: string
}

export interface VideoMedia extends MediaBase {
  type: 'video'
  url: string
  poster?: string
}

export interface GalleryItem {
  id: string
  url: string
  alt?: string
  width?: number
  height?: number
  aspectRatio?: string
}

export interface GalleryMedia extends MediaBase {
  type: 'gallery'
  items: Array<GalleryItem>
}

export interface LinkMedia extends MediaBase {
  type: 'link'
  url: string
  title?: string
  description?: string
  image?: string
  domain?: string
}

export interface DocumentMedia extends MediaBase {
  type: 'document'
  url: string
  filename: string
  mime?: string
  sizeBytes?: number
  thumbnailUrl?: string
}

export interface AudioMedia extends MediaBase {
  type: 'audio'
  url: string
  title?: string
  mime?: string
  durationSec?: number
  coverUrl?: string
}

export type AnyMedia =
  | ImageMedia
  | VideoMedia
  | GalleryMedia
  | LinkMedia
  | DocumentMedia
  | AudioMedia
