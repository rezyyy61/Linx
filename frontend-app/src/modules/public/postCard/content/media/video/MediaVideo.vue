<template>
  <div
    ref="root"
    class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800"
  >
    <button
      v-if="isInView"
      class="relative block w-full focus:outline-none"
      :style="ratioStyle"
      aria-label="Open video"
      @click="openLightbox"
    >
      <img
        v-if="media.poster"
        :src="media.poster"
        alt=""
        class="absolute inset-0 h-full w-full object-cover opacity-90"
        loading="lazy"
        draggable="false"
      >
      <div class="absolute inset-0 flex items-center justify-center">
        <span class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-black/60 text-white">
          <svg
            viewBox="0 0 24 24"
            class="h-7 w-7"
            fill="currentColor"
          ><path d="M8 5v14l11-7z" /></svg>
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
import { useLightbox } from '@/modules/public/postCard/composables/useLightbox'
import { useInView } from '@/modules/public/postCard/composables/useInView'
import MediaSkeleton from '@/modules/public/postCard/placeholders/MediaSkeleton.vue'

const props = defineProps<{ media: VideoMedia; gid?: string; indexInGroup?: number }>()
const ratioStyle = { paddingTop: '56.25%' }
const { open } = useLightbox()
const { el: root, isInView } = useInView()

function openLightbox() {
  const gid = props.gid || 'default'
  open(gid, [{ id: props.media.id, type: 'video', url: props.media.url, poster: props.media.poster }], props.indexInGroup || 0)
}
</script>
