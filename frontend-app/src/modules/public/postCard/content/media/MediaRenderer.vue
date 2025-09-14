<template>
  <div
    v-if="blocks.length"
    class="space-y-2"
  >
    <template
      v-for="b in blocks"
      :key="b.id"
    >
      <MixedGrid
        v-if="b.kind === 'grid'"
        :items="b.items"
        :gid="gid"
      />
      <MediaDocument
        v-else-if="b.kind==='doc'"
        :media="b.media"
      />
      <MediaAudio
        v-else-if="b.kind==='aud'"
        :media="b.media"
      />
      <MediaLinkPreview
        v-else-if="b.kind==='link'"
        :media="b.media"
      />
      <MediaImage
        v-else-if="b.kind==='img'"
        :media="b.media"
        :gid="gid"
        :index-in-group="0"
      />
      <MediaVideo
        v-else-if="b.kind==='vid'"
        :media="b.media"
        :gid="gid"
        :index-in-group="0"
      />
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { AnyMedia, ImageMedia, VideoMedia } from '@/modules/public/postCard/types/media.types'
import MixedGrid from './mixed/MixedGrid.vue'
import MediaImage from './image/MediaImage.vue'
import MediaVideo from './video/MediaVideo.vue'
import MediaLinkPreview from './link/MediaLinkPreview.vue'
import MediaDocument from '@/modules/public/postCard/content/document/MediaDocument.vue'
import MediaAudio from '@/modules/public/postCard/content/audio/MediaAudio.vue'

const props = defineProps<{ items: AnyMedia[] | null | undefined; gid?: string }>()
const gid = computed(() => props.gid || 'default')

type MixedItem = { id: string; type: 'image'|'video'; url: string; poster?: string; alt?: string; aspectRatio?: string; width?: number; height?: number }

function toMixedItem(m: AnyMedia): MixedItem | null {
  if (m.type === 'image') {
    const im = m as ImageMedia
    return { id: im.id, type: 'image', url: im.url, alt: im.alt, aspectRatio: im.aspectRatio, width: im.width, height: im.height }
  }
  if (m.type === 'video') {
    const vm = m as VideoMedia
    return { id: vm.id, type: 'video', url: vm.url, poster: vm.poster, aspectRatio: (vm as any).aspectRatio }
  }
  return null
}

const blocks = computed(() => {
  const arr = Array.isArray(props.items) ? props.items : []
  const out: any[] = []

  let buf: MixedItem[] = []
  function flush() {
    if (buf.length === 1) {
      const it = buf[0]
      if (it.type === 'image') {
        out.push({ kind: 'img', id: `img_${it.id}`, media: { id: it.id, url: it.url, alt: it.alt, aspectRatio: it.aspectRatio, width: it.width, height: it.height } })
      } else {
        out.push({ kind: 'vid', id: `vid_${it.id}`, media: { id: it.id, url: it.url, poster: it.poster, aspectRatio: it.aspectRatio } })
      }
    } else if (buf.length > 1) {
      out.push({ kind: 'grid', id: `grid_${buf.map(x=>x.id).join('_')}`, items: buf.slice() })
    }
    buf = []
  }

  for (const m of arr) {
    if (m.type === 'image' || m.type === 'video') {
      const mi = toMixedItem(m)
      if (mi) buf.push(mi)
      continue
    }
    flush()
    if (m.type === 'document') out.push({ kind: 'doc', id: `doc_${m.id}`, media: m })
    else if (m.type === 'audio') out.push({ kind: 'aud', id: `aud_${m.id}`, media: m })
    else if (m.type === 'link') out.push({ kind: 'link', id: `lnk_${m.id}`, media: m })
    else if (m.type === 'gallery') {
      const items = (m as any).items || []
      const mixed: MixedItem[] = items.map((it: any) => ({ id: String(it.id), type: 'image', url: it.url, alt: it.alt, aspectRatio: it.aspectRatio, width: it.width, height: it.height }))
      out.push({ kind: 'grid', id: `grid_${mixed.map(x=>x.id).join('_')}`, items: mixed })
    } else {
      if ((m as any).type === 'image') out.push({ kind: 'img', id: `img_${(m as any).id}`, media: m })
      else if ((m as any).type === 'video') out.push({ kind: 'vid', id: `vid_${(m as any).id}`, media: m })
    }
  }
  flush()
  return out
})
</script>
