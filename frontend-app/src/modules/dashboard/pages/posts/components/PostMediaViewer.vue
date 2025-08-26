<script setup lang="ts">
import { computed, onMounted, onBeforeUnmount, watch } from 'vue'
import PhotoSwipeLightbox from 'photoswipe/lightbox'
import 'photoswipe/style.css'
import MediaVideoFrame from '@/modules/dashboard/pages/posts/components/mediaPreview/MediaVideoFrame.vue'
import MediaAudio from '@/modules/dashboard/pages/posts/components/mediaPreview/MediaAudio.vue'
import MediaDocument from '@/modules/dashboard/pages/posts/components/mediaPreview/MediaDocument.vue'

type Item = {
  id:number
  url?:string
  mime_type?:string
  width?:number
  height?:number
  title?:string
  poster?:string
  poster_url?:string
  processed?:any
  meta?:any
}

const props = withDefaults(defineProps<{
  items: Item[]
  height?: number
  fit?: 'contain'|'cover'
}>(), { height: 560, fit: 'contain' })

const list = computed(() =>
  (props.items||[])
    .filter(m => !!m?.url)
    .map((m,i) => {
      const mime = m.mime_type || ''
      const kind = mime.startsWith('video/') ? 'video'
        : mime.startsWith('image/') ? 'image'
          : mime.startsWith('audio/') ? 'audio' : 'document'
      const poster = m.poster || m.poster_url || m?.processed?.poster_url || m?.meta?.poster_url || null
      return {
        key: `${m.id ?? i}-${m.url}`,
        kind,
        url: m.url as string,
        mime,
        w: m.width || 0,
        h: m.height || 0,
        title: m.title || '',
        poster,
      }
    })
)

const gid = `pswp-${Math.random().toString(36).slice(2)}`
let lightbox:any=null
onMounted(() => {
  lightbox = new PhotoSwipeLightbox({ gallery:`#${gid}`, children:'a[data-pswp-item]', pswpModule:()=>import('photoswipe') })
  lightbox.init()
})
onBeforeUnmount(() => { try { lightbox?.destroy() } catch{ /* empty */ } lightbox=null })
watch(() => props.items, () => { try { lightbox?.destroy() } catch{ /* empty */ } lightbox=null; lightbox = new PhotoSwipeLightbox({ gallery:`#${gid}`, children:'a[data-pswp-item]', pswpModule:()=>import('photoswipe') }); lightbox.init() })
</script>

<template>
  <div
    :id="gid"
    class="space-y-4 p-4"
  >
    <div
      v-for="m in list"
      :key="m.key"
      class="rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-black"
      :style="{ height: (height) + 'px' }"
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
          >
        </a>
      </template>

      <template v-else-if="m.kind==='audio'">
        <div class="w-full h-full grid place-items-center p-4 bg-gray-950">
          <MediaAudio :src="m.url" />
        </div>
      </template>

      <template v-else>
        <div class="w-full h-full p-4 bg-gray-950">
          <MediaDocument
            :src="m.url"
            :mime="m.mime"
          />
        </div>
      </template>
    </div>
  </div>
</template>
