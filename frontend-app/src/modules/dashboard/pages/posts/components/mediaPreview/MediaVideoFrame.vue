<script setup lang="ts">
import { ref, computed } from 'vue'
import MediaVideo from './MediaVideo.vue'

const props = withDefaults(defineProps<{
  src: string
  type?: string
  // eslint-disable-next-line vue/require-default-prop
  poster?: string
  autoplay?: boolean
  muted?: boolean
  loop?: boolean
  maxWidth?: number
  maxHeight?: number
  portraitMaxWidth?: number
  landscapeMaxWidth?: number
  fit?: 'contain' | 'cover'
}>(), {
  type: 'video/mp4',
  autoplay: false,
  muted: false,
  loop: false,
  maxWidth: 960,
  maxHeight: 720,
  portraitMaxWidth: 360,
  landscapeMaxWidth: 960,
  fit: 'contain'
})

const natW = ref(9)
const natH = ref(16)
function onMeta(v: { w:number; h:number }) { if (v.w>0 && v.h>0) { natW.value=v.w; natH.value=v.h } }

const isPortrait = computed(() => natH.value >= natW.value)
const limitW = computed(() => isPortrait.value ? (props.portraitMaxWidth ?? props.maxWidth) : (props.landscapeMaxWidth ?? props.maxWidth))
const box = computed(() => {
  const w = natW.value, h = natH.value
  const mw = Math.max(1, limitW.value)
  const mh = Math.max(1, props.maxHeight)
  const s = Math.min(mw / w, mh / h, 1)
  return { w: Math.round(w * s), h: Math.round(h * s) }
})
</script>

<template>
  <div
    class="grid place-items-center bg-black rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700"
    :style="{ width: box.w + 'px', height: box.h + 'px' }"
  >
    <MediaVideo
      class="w-full h-full"
      :src="src"
      :type="type"
      :poster="poster"
      :autoplay="autoplay"
      :muted="muted"
      :loop="loop"
      :fluid="false"
      :fit="fit"
      @meta="onMeta"
    />
  </div>
</template>
