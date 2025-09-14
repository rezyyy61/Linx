<template>
  <figure
    ref="root"
    class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800"
    :aria-label="media.alt || 'image'"
  >
    <button
      v-if="isInView"
      class="block w-full focus:outline-none"
      aria-label="Open image"
      @click="openLightbox"
    >
      <div
        :class="['ar-box','bounded','grid','place-items-center','bg-zinc-100','dark:bg-zinc-800', isPortrait ? 'portrait px-3' : '']"
        :style="boxVars"
      >
        <img
          :src="media.url"
          :alt="media.alt || 'image'"
          class="w-auto h-auto max-w-full max-h-full object-contain transition-opacity duration-300"
          :class="loaded ? 'opacity-100' : 'opacity-0'"
          loading="lazy"
          draggable="false"
          @load="onLoad"
        >
      </div>
    </button>
    <MediaSkeleton
      v-else
      :ratio="skeletonRatio"
    />
  </figure>
</template>

<script setup lang="ts">
import type { ImageMedia } from '@/modules/public/postCard/types/media.types'
import { ref, computed } from 'vue'
import { useInView } from '@/modules/public/postCard/composables/useInView'
import { useLightbox } from '@/modules/public/postCard/composables/useLightbox'
import MediaSkeleton from '@/modules/public/postCard/placeholders/MediaSkeleton.vue'

type ImageMediaLike = ImageMedia & { width?: number; height?: number; aspectRatio?: string }
const props = defineProps<{ media: ImageMediaLike; gid?: string; indexInGroup?: number }>()

const natW = ref<number | null>(null)
const natH = ref<number | null>(null)

function parseAR(m: ImageMediaLike) {
  if (m.aspectRatio) {
    const [w, h] = m.aspectRatio.split('/').map(s => Number(s.trim()))
    if (w > 0 && h > 0) return { w, h }
  }
  if (m.width && m.height) return { w: m.width, h: m.height }
  if (natW.value && natH.value) return { w: natW.value, h: natH.value }
  return { w: 16, h: 9 }
}

const ar = computed(() => parseAR(props.media))
const isPortrait = computed(() => ar.value.w < ar.value.h)

const boxVars = computed(() => ({
  '--ar-w': String(ar.value.w),
  '--ar-h': String(ar.value.h)
}))
const skeletonRatio = computed(() => `${ar.value.w}/${ar.value.h}`)

const { el: root, isInView } = useInView()
const loaded = ref(false)
function onLoad(e: Event) {
  const t = e.target as HTMLImageElement
  natW.value = t.naturalWidth
  natH.value = t.naturalHeight
  loaded.value = true
}

const { open } = useLightbox()
function openLightbox() {
  const gid = props.gid || 'default'
  open(gid, [{ id: props.media.id, type: 'image', url: props.media.url, alt: props.media.alt }], props.indexInGroup || 0)
}
</script>

<style scoped>
.ar-box { aspect-ratio: var(--ar-w) / var(--ar-h); }
.bounded { --vh-limit: 70svh; max-height: var(--vh-limit); }
@supports not (height: 100svh) {
  .bounded { --vh-limit: 70vh; max-height: var(--vh-limit); }
}
.portrait {
  max-width: min(100%, calc(var(--vh-limit) * (var(--ar-w) / var(--ar-h))));
  margin-inline: auto;
  border-radius: 0.75rem;
}
@supports not (aspect-ratio: 1 / 1) {
  .ar-box { position: relative; }
  .ar-box::before { content: ""; display: block; padding-top: calc(var(--ar-h) / var(--ar-w) * 100%); }
}
</style>
