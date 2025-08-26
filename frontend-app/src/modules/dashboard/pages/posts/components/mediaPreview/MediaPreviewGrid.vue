<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import MediaAudio from '@/modules/dashboard/pages/posts/components/mediaPreview/MediaAudio.vue'
import MediaDocument from '@/modules/dashboard/pages/posts/components/mediaPreview/MediaDocument.vue'
import MediaImage from '@/modules/dashboard/pages/posts/components/mediaPreview/MediaImage.vue'
import PhotoSwipeLightbox from 'photoswipe/lightbox'
import 'photoswipe/style.css'
import { useFileCaches, useVideoPoster } from '@/modules/dashboard/pages/posts/pages/useUploadHelpers'
import MediaVideoFrame from "@/modules/dashboard/pages/posts/components/mediaPreview/MediaVideoFrame.vue";

type ExistingItem = { id:number; url?:string; mime_type?:string; width?:number; height?:number; title?:string }
const props = withDefaults(defineProps<{
  files?: File[]
  existing?: ExistingItem[]
  maxVisible?: number
  removable?: boolean
}>(), { files: () => [], existing: () => [], maxVisible: 4, removable: true })

const emit = defineEmits<{ (e:'remove:file', index:number):void; (e:'remove:existing', index:number):void }>()

const { fileUrl, posterOf, posterCache } = useFileCaches()
const { generateVideoPoster } = useVideoPoster(fileUrl, posterCache)

type Item = {
  kind: 'image'|'video'|'audio'|'document'
  origin: 'new'|'old'
  i: number
  src: string
  mime?: string
  w?: number
  h?: number
  title?: string
  file?: File
}
const normalize = computed<Item[]>(() => {
  const a = (props.files||[]).map((f, i) => {
    const t = f.type || ''
    const k: Item['kind'] =
      t.startsWith('video/') ? 'video' : t.startsWith('image/') ? 'image' : t.startsWith('audio/') ? 'audio' : 'document'
    return { kind: k, origin: 'new' as const, i, src: fileUrl(f), mime: t, file: f }
  })
  const b = (props.existing||[]).map((m, i) => {
    const t = m.mime_type || ''
    const k: Item['kind'] =
      t.startsWith('video/') ? 'video' : t.startsWith('image/') ? 'image' : t.startsWith('audio/') ? 'audio' : 'document'
    return { kind: k, origin: 'old' as const, i, src: m.url || '', mime: t, w: m.width, h: m.height, title: m.title }
  })
  return [...a, ...b].filter(x => !!x.src)
})

const total = computed(() => normalize.value.length)
const allImages = computed(() => total.value > 0 && normalize.value.every(x => x.kind === 'image'))
const useImageComponent = computed(() => allImages.value && !props.removable)

const expanded = ref(false)
const visible = computed(() => {
  if (expanded.value) return normalize.value
  if (total.value <= props.maxVisible) return normalize.value
  return normalize.value.slice(0, props.maxVisible)
})
const hiddenCount = computed(() => Math.max(0, total.value - props.maxVisible))

function tileClass(idx:number, len:number) {
  if (len === 1 && idx === 0) return 'col-span-2'
  if (len === 3 && idx === 2) return 'col-span-2'
  return 'col-span-1'
}
function onRemove(it: Item) {
  if (!props.removable) return
  if (it.origin === 'new') emit('remove:file', it.i)
  else emit('remove:existing', it.i)
}

let lightbox: any
const gid = `pswp-${Math.random().toString(36).slice(2)}`
onMounted(() => {
  lightbox = new PhotoSwipeLightbox({
    gallery: `#${gid}`,
    children: 'a[data-pswp-item]',
    pswpModule: () => import('photoswipe'),
  })
  lightbox.init()
})
onBeforeUnmount(() => { try { lightbox?.destroy() } catch { /* empty */ } lightbox = null })

watch(normalize, (list) => {
  for (const it of list) if (it.origin==='new' && it.kind==='video' && it.file) void generateVideoPoster(it.file)
}, { immediate: true })

const imageItems = computed(() =>
  normalize.value
    .filter(x => x.kind === 'image')
    .map(x => ({ src: x.src, w: x.w || 1600, h: x.h || 900, thumb: x.src, alt: x.title || '' }))
)
</script>

<template>
  <div
    v-if="total"
    class="space-y-2"
  >
    <MediaImage
      v-if="useImageComponent"
      :items="imageItems"
    />

    <template v-else>
      <div
        :id="gid"
        class="grid grid-cols-2 gap-3"
      >
        <div
          v-for="(it, idx) in visible"
          :key="`${it.origin}-${it.kind}-${it.i}-${idx}`"
          class="group relative overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800"
          :class="tileClass(idx, visible.length)"
        >
          <template v-if="it.kind === 'video'">
            <div class="w-full bg-black grid place-items-center p-0.5">
              <MediaVideoFrame
                :key="it.src"
                :src="it.src"
                :type="it.mime || 'video/mp4'"
                :poster="it.origin==='new' && it.file ? posterOf(it.file) : undefined"
                :max-height="560"
                :portrait-max-width="360"
                :landscape-max-width="960"
                fit="contain"
              />
            </div>
          </template>

          <div
            v-else
            class="aspect-video w-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center"
          >
            <template v-if="it.kind === 'image'">
              <a
                data-pswp-item
                :href="it.src"
                :data-pswp-width="it.w || 1600"
                :data-pswp-height="it.h || 900"
                :data-cropped="true"
                class="block w-full h-full"
                target="_blank"
                rel="noreferrer"
              >
                <img
                  :src="it.src"
                  :alt="it.title||''"
                  class="w-full h-full object-cover"
                >
              </a>
            </template>

            <template v-else-if="it.kind === 'audio'">
              <MediaAudio :src="it.src" />
            </template>

            <template v-else>
              <MediaDocument
                :src="it.src"
                :mime="it.mime"
              />
            </template>
          </div>

          <button
            v-if="removable"
            type="button"
            class="absolute top-2 right-2 inline-flex items-center justify-center rounded-full bg-white/90 dark:bg-gray-900/80 p-1 shadow hover:scale-105"
            aria-label="Remove"
            @click="onRemove(it)"
          >
            <span
              class="iconify w-4 h-4 text-gray-700 dark:text-gray-200"
              data-icon="mdi:close"
              aria-hidden="true"
            />
          </button>

          <button
            v-if="!expanded && idx===3 && hiddenCount>0"
            type="button"
            class="absolute inset-0 grid place-items-center bg-black/40 text-white text-sm"
            @click="expanded = true"
          >
            +{{ hiddenCount }} more
          </button>
        </div>
      </div>

      <div
        v-if="expanded && hiddenCount>0"
        class="flex justify-end"
      >
        <button
          type="button"
          class="text-xs px-2 py-1 rounded-md bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600"
          @click="expanded = false"
        >
          Collapse
        </button>
      </div>
    </template>
  </div>
</template>
