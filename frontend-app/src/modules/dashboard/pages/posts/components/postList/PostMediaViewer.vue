<script setup lang="ts">
import { computed, onMounted, onBeforeUnmount, watch } from 'vue'
import PhotoSwipeLightbox from 'photoswipe/lightbox'
import 'photoswipe/style.css'
import MediaVideoFrame from '@/modules/dashboard/pages/posts/components/mediaPreview/MediaVideoFrame.vue'
import MediaAudio from '@/modules/dashboard/pages/posts/components/mediaPreview/MediaAudio.vue'
import MediaDocument from '@/modules/dashboard/pages/posts/components/mediaPreview/MediaDocument.vue'

type Item = {
  id: number
  url?: string
  mime_type?: string
  mime?: string
  width?: number
  height?: number
  w?: number
  h?: number
  title?: string
  poster?: string
  poster_url?: string
  processed?: any
  meta?: any
}

const props = withDefaults(defineProps<{
  items: Item[]
  height?: number
  fit?: 'contain'|'cover'
}>(), { height: 560, fit: 'contain' })

function inferExt(u: string): string {
  try {
    const p = new URL(u, typeof window !== 'undefined' ? window.location.href : 'http://x').pathname
    const i = p.lastIndexOf('.')
    return i >= 0 ? p.slice(i + 1).toLowerCase() : ''
  } catch {
    const s = (u || '').split('?')[0]
    const i = s.lastIndexOf('.')
    return i >= 0 ? s.slice(i + 1).toLowerCase() : ''
  }
}
function inferKind(mime?: string, url?: string): 'image'|'video'|'audio'|'document' {
  const mm = (mime || '').toLowerCase()
  if (mm.startsWith('image/')) return 'image'
  if (mm.startsWith('video/')) return 'video'
  if (mm.startsWith('audio/')) return 'audio'
  const ext = inferExt(url || '')
  if (['jpg','jpeg','png','webp','gif','bmp','tif','tiff'].includes(ext)) return 'image'
  if (['mp4','mov','mkv','webm','avi'].includes(ext)) return 'video'
  if (['mp3','aac','m4a','wav','flac','ogg','oga'].includes(ext)) return 'audio'
  return 'document'
}

const list = computed(() =>
  (props.items || [])
    .filter(m => !!m?.url)
    .map((m, i) => {
      const mime = m.mime || m.mime_type || ''
      const kind = inferKind(mime, m.url)
      const poster =
        m.poster ||
        m.poster_url ||
        m?.processed?.poster_url ||
        m?.meta?.poster_url ||
        null
      const w = (m.width ?? m.w ?? 0) || 0
      const h = (m.height ?? m.h ?? 0) || 0
      return {
        key: `${m.id ?? i}-${m.url}`,
        kind,
        url: m.url as string,
        mime,
        w,
        h,
        title: m.title || '',
        poster,
      }
    })
)

const gid = `pswp-${Math.random().toString(36).slice(2)}`
let lightbox: any = null

function initLightbox() {
  lightbox = new PhotoSwipeLightbox({
    gallery: `#${gid}`,
    children: 'a[data-pswp-item]',
    pswpModule: () => import('photoswipe'),
  })
  lightbox.init()
}

onMounted(() => {
  if ((list.value || []).some(x => x.kind === 'image')) initLightbox()
})
onBeforeUnmount(() => {
  try { lightbox?.destroy() } catch { /* empty */ }
  lightbox = null
})
watch(() => list.value.map(x => x.url).join('|'), () => {
  try { lightbox?.destroy() } catch { /* empty */ }
  lightbox = null
  if ((list.value || []).some(x => x.kind === 'image')) initLightbox()
})
</script>

<template>
  <div
    :id="gid"
    class="space-y-4 p-4"
  >
    <div
      v-for="m in list"
      :key="m.key"
      class="rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900"
      :style="{ height: height + 'px' }"
    >
      <template v-if="m.kind==='video'">
        <MediaVideoFrame
          :src="m.url"
          :type="m.mime || 'video/mp4'"
          :poster="m.poster || undefined"
          :fixed-height="height"
          :fit="fit"
        />
      </template>

      <template v-else-if="m.kind==='image'">
        <a
          data-pswp-item
          :href="m.url"
          :data-pswp-width="m.w || 1600"
          :data-pswp-height="m.h || 900"
          :data-cropped="true"
          class="block w-full h-full"
          target="_blank"
          rel="noreferrer"
        >
          <img
            :src="m.url"
            :alt="m.title"
            class="w-full h-full"
            :class="fit==='cover' ? 'object-cover' : 'object-contain'"
            loading="lazy"
            decoding="async"
          >
        </a>
      </template>

      <template v-else-if="m.kind==='audio'">
        <div class="w-full h-full grid place-items-center p-4 bg-gray-50 dark:bg-gray-950">
          <MediaAudio :src="m.url" />
        </div>
      </template>

      <template v-else>
        <div class="w-full h-full p-4 bg-gray-50 dark:bg-gray-950">
          <MediaDocument
            :src="m.url"
            :mime="m.mime"
          />
        </div>
      </template>
    </div>
  </div>
</template>
