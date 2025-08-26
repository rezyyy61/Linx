<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import videojs from 'video.js'
import type Player from 'video.js/dist/types/player'
import 'video.js/dist/video-js.css'

const props = withDefaults(defineProps<{
  src: string
  type?: string
  // eslint-disable-next-line vue/require-default-prop
  poster?: string
  autoplay?: boolean
  muted?: boolean
  loop?: boolean
  fluid?: boolean
  fit?: 'contain' | 'cover'
}>(), { type: 'video/mp4', autoplay: false, muted: false, loop: false, fluid: true, fit: 'contain' })

const emit = defineEmits<{ (e:'meta', v:{ w:number; h:number }): void }>()

const el = ref<HTMLVideoElement|null>(null)
let player: Player | null = null
const fallback = ref(false)

function setSource() {
  if (!player || !props.src) return
  try { player.src({ src: props.src, type: props.type || 'video/mp4' }); player.load() } catch { fallback.value = true }
}
function setPoster() { if (player) player.poster(props.poster || '') }

onMounted(() => {
  if (!el.value) { fallback.value = true; return }
  try {
    player = videojs(el.value, {
      controls: true,
      autoplay: props.autoplay,
      muted: props.muted,
      loop: props.loop,
      preload: 'metadata',
      fluid: props.fluid,
      fill: !props.fluid,
      responsive: true,
      techOrder: ['html5'],
      sources: props.src ? [{ src: props.src, type: props.type || 'video/mp4' }] : [],
      html5: { vhs: { withCredentials: false }, nativeAudioTracks: false, nativeVideoTracks: false },
    })
    player.ready(() => { setPoster(); setSource() })
    player.on('loadedmetadata', () => { try { emit('meta', { w: player!.videoWidth(), h: player!.videoHeight() }) } catch { /* empty */ } })
    player.on('error', () => { fallback.value = true })
  } catch { fallback.value = true }
})
onBeforeUnmount(() => { try { player?.dispose() } catch { /* empty */ } player = null })

watch(() => props.src, setSource)
watch(() => props.type, setSource)
watch(() => props.poster, setPoster)
watch(() => props.autoplay, v => { if (player) player.autoplay(!!v) })
watch(() => props.muted, v => { if (player) player.muted(!!v) })
watch(() => props.loop, v => { if (player) player.loop(!!v) })
</script>

<template>
  <div :class="['w-full h-full', { 'fixed-frame': !fluid, 'fit-cover': fit==='cover', 'fit-contain': fit!=='cover' }]">
    <video
      v-if="!fallback"
      ref="el"
      :class="['video-js vjs-default-skin vjs-big-play-centered', fluid ? 'vjs-fluid' : 'w-full h-full']"
      playsinline
    />
    <video
      v-else
      class="w-full h-full bg-black"
      controls
      playsinline
      preload="metadata"
      :poster="poster"
      :autoplay="autoplay"
      :muted="muted"
      :loop="loop"
    >
      <source
        :src="src"
        :type="type"
      >
    </video>
  </div>
</template>

<style scoped>
.fixed-frame :deep(.vjs-tech){ width:100%; height:100%; }
.fit-contain :deep(.vjs-tech){ object-fit:contain; background:#000; }
.fit-cover   :deep(.vjs-tech){ object-fit:cover;   background:#000; }
</style>
