<template>
  <div
    ref="root"
    class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800 bg-black"
  >
    <button
      v-if="isInView"
      class="relative block w-full focus:outline-none"
      :style="ratioStyle"
      aria-label="Open video"
      @click="openLightbox"
    >
      <img
        v-if="posterSrc"
        :src="posterSrc"
        alt=""
        class="absolute inset-0 h-full w-full object-contain"
        loading="lazy"
        draggable="false"
        @load="onPosterLoad"
      >


      <video
        v-else
        ref="vid"
        :src="media.url"
        class="absolute inset-0 h-full w-full object-contain"
        preload="metadata"
        muted
        playsinline
        disablepictureinpicture
        controlslist="nodownload noplaybackrate"
        @loadedmetadata="onVideoMeta"
      />

      <!-- آیکون پلی -->
      <div class="absolute inset-0 flex items-center justify-center">
        <span class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-black/60 text-white">
          <svg
            viewBox="0 0 24 24"
            class="h-7 w-7"
            fill="currentColor"
          >
            <path d="M8 5v14l11-7z" />
          </svg>
        </span>
      </div>
    </button>

    <MediaSkeleton
      v-else
      ratio="16/9"
    />
  </div>
</template>

<script setup lang="ts">
import type { VideoMedia } from '@/modules/public/postCard/types/media.types'
import { computed, ref } from 'vue'
import { useLightbox } from '@/modules/public/postCard/composables/useLightbox'
import { useInView } from '@/modules/public/postCard/composables/useInView'
import MediaSkeleton from '@/modules/public/postCard/placeholders/MediaSkeleton.vue'

const props = defineProps<{ media: VideoMedia; gid?: string; indexInGroup?: number }>()

const initialRatio = (props.media as any).aspectRatio || '16/9'
const ratioW = ref<number>(Number(String(initialRatio).split('/')[0]) || 16)
const ratioH = ref<number>(Number(String(initialRatio).split('/')[1]) || 9)
const ratioStyle = computed(() => ({ paddingTop: `${(ratioH.value / ratioW.value) * 100}%` }))
const posterSrc = computed(() => props.media.poster || '')

function onPosterLoad(e: Event) {
  const img = e.target as HTMLImageElement
  if (img?.naturalWidth && img?.naturalHeight) {
    ratioW.value = img.naturalWidth
    ratioH.value = img.naturalHeight
  }
}

const vid = ref<HTMLVideoElement | null>(null)
function onVideoMeta() {
  const v = vid.value
  if (v && v.videoWidth && v.videoHeight) {
    ratioW.value = v.videoWidth
    ratioH.value = v.videoHeight
  }
}

const { open } = useLightbox()
const { el: root, isInView } = useInView()

function openLightbox() {
  const gid = props.gid || 'default'
  open(
    gid,
    [{ id: props.media.id, type: 'video', url: props.media.url, poster: props.media.poster }],
    props.indexInGroup || 0
  )
}
</script>
