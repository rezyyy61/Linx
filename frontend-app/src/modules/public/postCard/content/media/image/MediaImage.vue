<!-- /src/modules/public/postCard/content/media/image/MediaImage.vue -->
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
        v-if="hasNat"
        class="auto-box grid place-items-center bg-zinc-100 dark:bg-zinc-800"
      >
        <img
          :src="media.url"
          :alt="media.alt || 'image'"
          class="media"
          :class="loaded ? 'opacity-100' : 'opacity-0'"
          loading="lazy"
          draggable="false"
          @load="onLoad"
        >
      </div>

      <div
        v-else
        class="preset-box grid place-items-center bg-zinc-100 dark:bg-zinc-800"
        :style="presetStyle"
      >
        <img
          :src="media.url"
          :alt="media.alt || 'image'"
          class="media"
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
const loaded = ref(false)

function parseARStr(s?: string) {
  if (!s) return null
  const [w, h] = s.split('/').map(Number)
  return w > 0 && h > 0 ? w / h : null
}
function naturalAR(m: ImageMediaLike) {
  const fromProp = parseARStr(m.aspectRatio)
  if (fromProp) return fromProp
  if (m.width && m.height) return m.width / m.height
  if (natW.value && natH.value) return (natW.value as number) / (natH.value as number)
  return 1
}
function choosePreset(r: number) {
  if (r < 0.95) return 4 / 5
  if (r > 1.45) return 16 / 9
  return 1
}

const chosenAR = computed(() => choosePreset(naturalAR(props.media)))
const skeletonRatio = computed(() => {
  const r = chosenAR.value
  const w = Math.round(r * 100)
  const h = 100
  return `${w}/${h}`
})
const presetStyle = computed(() => ({ aspectRatio: String(chosenAR.value) }))

const hasNat = computed(() => !!(natW.value && natH.value))

function onLoad(e: Event) {
  const t = e.target as HTMLImageElement
  natW.value = t.naturalWidth
  natH.value = t.naturalHeight
  loaded.value = true
}

const { el: root, isInView } = useInView()
const { open } = useLightbox()
function openLightbox() {
  open(props.gid || 'default', [{ id: props.media.id, type: 'image', url: props.media.url, alt: props.media.alt }], props.indexInGroup || 0)
}
</script>

<style scoped>
.auto-box{width:100%;max-height:70svh}
@supports not (height: 100svh){.auto-box{max-height:70vh}}
.preset-box{width:100%;max-height:70svh}
@supports not (height: 100svh){.preset-box{max-height:70vh}}
.media{width:auto;height:auto;max-width:100%;max-height:100%;object-fit:contain;transition:opacity .25s ease;display:block}
</style>
