<template>
  <div
    ref="root"
    class="overflow-hidden rounded-xl border border-zinc-200 bg-black dark:border-zinc-800"
  >
    <div class="frame">
      <button
        v-if="isInView"
        class="relative flex items-center justify-center w-full h-full focus:outline-none"
        aria-label="Open video"
        @click="openLightbox"
      >
        <img
          v-if="posterSrc"
          :src="posterSrc"
          alt=""
          class="media"
          loading="lazy"
          draggable="false"
        >
        <video
          v-else
          ref="vid"
          :src="media.url"
          class="media"
          preload="metadata"
          muted
          playsinline
          disablepictureinpicture
          controlslist="nodownload noplaybackrate"
        />
        <div class="play">
          <span class="play-btn">
            <svg
              viewBox="0 0 24 24"
              class="play-ic"
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
  </div>
</template>

<script setup lang="ts">
import type { VideoMedia } from '@/modules/public/postCard/types/media.types'
import { computed } from 'vue'
import { useLightbox } from '@/modules/public/postCard/composables/useLightbox'
import { useInView } from '@/modules/public/postCard/composables/useInView'
import MediaSkeleton from '@/modules/public/postCard/placeholders/MediaSkeleton.vue'

const props = defineProps<{ media: VideoMedia; gid?: string; indexInGroup?: number }>()
const posterSrc = computed(() => props.media.poster || '')

const { open } = useLightbox()
const { el: root, isInView } = useInView()

function openLightbox() {
  const gid = props.gid || 'default'
  open(gid, [{ id: props.media.id, type: 'video', url: props.media.url, poster: props.media.poster }], props.indexInGroup || 0)
}
</script>

<style scoped>
.frame {
  aspect-ratio: 16 / 9; /* همیشه ثابت */
  width: 100%;
  position: relative;
  background: black;
  display: flex;
  align-items: center;
  justify-content: center;
}

.media {
  max-width: 100%;
  max-height: 100%;
  width: auto;
  height: auto;
  object-fit: contain;
  display: block;
}

.play {
  position: absolute;
  inset: 0;
  display: grid;
  place-items: center;
  pointer-events: none;
}

.play-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 56px;
  height: 56px;
  border-radius: 9999px;
  background: rgba(0, 0, 0, 0.55);
  color: #fff;
}

.play-ic {
  width: 28px;
  height: 28px;
}

@media (min-width: 768px) {
  .play-btn { width: 64px; height: 64px; }
  .play-ic { width: 32px; height: 32px; }
}
</style>
