<template>
  <figure
    ref="root"
    class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800"
    :aria-label="media.alt || 'image'"
  >
    <button
      v-if="isInView"
      class="relative block w-full focus:outline-none"
      :style="ratioStyle"
      aria-label="Open image"
      @click="openLightbox"
    >
      <img
        v-show="!loaded"
        :src="previewSrc"
        :alt="media.alt || 'image preview'"
        class="absolute inset-0 h-full w-full object-cover blur-sm scale-[1.02]"
        loading="lazy"
        draggable="false"
      >
      <img
        :src="media.url"
        :alt="media.alt || 'image'"
        class="absolute inset-0 h-full w-full object-cover transition-[filter,transform,opacity] duration-500"
        :class="loaded ? 'opacity-100' : 'opacity-0 blur-sm scale-[1.02]'"
        loading="lazy"
        draggable="false"
        @load="onLoad"
      >
    </button>
    <MediaSkeleton
      v-else
      :ratio="media.aspectRatio || '16/9'"
    />
  </figure>
</template>

<script setup lang="ts">
import type { ImageMedia } from '@/modules/public/postCard/types/media.types'
import { ref, computed } from 'vue'
import { useInView } from '@/modules/public/postCard/composables/useInView'
import { useLightbox } from '@/modules/public/postCard/composables/useLightbox'
import MediaSkeleton from '@/modules/public/postCard/placeholders/MediaSkeleton.vue'

const props = defineProps<{ media: ImageMedia; gid?: string; indexInGroup?: number }>()
const ratio = props.media.aspectRatio || '16/9'
const [w, h] = ratio.split('/').map(Number)
const ratioStyle = { paddingTop: `${(h / w) * 100}%` }

const { el: root, isInView } = useInView()
const loaded = ref(false)
const previewSrc = computed(() => props.media.url)

function onLoad() { loaded.value = true }

const { open } = useLightbox()
function openLightbox() {
  const gid = props.gid || 'default'
  open(gid, [{ id: props.media.id, type: 'image', url: props.media.url, alt: props.media.alt }], props.indexInGroup || 0)
}
</script>
