<!-- /home/rezyyy/PhpstormProjects/Linx/frontend-app/src/modules/public/postCard/content/media/mixed/MixedGrid.vue -->
<template>
  <div
    ref="root"
    class="grid grid-cols-2 gap-2 rounded-xl"
  >
    <template
      v-for="(it, idx) in limited"
      :key="it.id"
    >
      <button
        class="group relative block w-full overflow-hidden rounded-xl focus:outline-none"
        aria-label="Open media"
        @click="openAt(idx)"
      >
        <div class="tile">
          <div class="frame">
            <img
              v-if="it.type==='image' && isInView"
              :src="bestUrl(it)"
              :alt="it.alt || 'image'"
              class="media"
              loading="lazy"
              draggable="false"
            >
            <template v-else-if="it.type==='video' && isInView">
              <img
                v-if="it.poster"
                :src="it.poster"
                alt=""
                class="media"
                loading="lazy"
                draggable="false"
              >
              <video
                v-else
                :src="it.url"
                class="media"
                preload="metadata"
                muted
                playsinline
                disablepictureinpicture
                controlslist="nodownload noplaybackrate"
              />
              <span class="play-badge">
                <svg
                  viewBox="0 0 24 24"
                  class="play-icon"
                ><path d="M8 5v14l11-7z" /></svg>
              </span>
            </template>
            <div
              v-else
              class="skeleton"
            />
          </div>
        </div>

        <div
          v-if="idx === limited.length - 1 && remaining > 0"
          class="pointer-events-none absolute inset-0 z-20 flex items-center justify-center"
        >
          <span class="badge">+{{ remaining }}</span>
        </div>
      </button>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useLightbox } from '@/modules/public/postCard/composables/useLightbox'
import { useInView } from '@/modules/public/postCard/composables/useInView'

type MixedItem = {
  id: string
  type: 'image' | 'video'
  url: string
  poster?: string
  alt?: string
  original_url?: string
  preview_url?: string
}

const props = defineProps<{ items: MixedItem[]; gid?: string }>()
const { open } = useLightbox()
const { el: root, isInView } = useInView()

const maxTiles = 4
const limited = computed(() => props.items.slice(0, maxTiles))
const remaining = computed(() => Math.max(0, props.items.length - maxTiles))

function bestUrl(it: MixedItem): string {
  return it.url || it.original_url || it.preview_url || it.url
}

function openAt(idx: number) {
  const gid = props.gid || 'default'
  const lbItems = props.items.map(i =>
    i.type === 'image'
      ? { id: i.id, type: 'image' as const, url: bestUrl(i), alt: i.alt }
      : { id: i.id, type: 'video' as const, url: i.url, poster: i.poster }
  )
  open(gid, lbItems, idx)
}
</script>

<style scoped>
.tile{aspect-ratio:1/1;width:100%;border-radius:0.75rem}
.frame{position:relative;display:flex;align-items:center;justify-content:center;width:100%;height:100%;background-color:rgb(244 244 245);border-radius:0.75rem}
:global(.dark) .frame{background-color:rgb(24 24 27)}
.media{max-width:100%;max-height:100%;width:auto;height:auto;display:block}
.skeleton{width:100%;height:100%;background:linear-gradient(90deg,rgba(0,0,0,0.06),rgba(0,0,0,0.12),rgba(0,0,0,0.06));animation:pulse 1.2s infinite linear}
@keyframes pulse{0%{transform:translateX(-20%)}100%{transform:translateX(20%)}}
:global(.dark) .skeleton{background:linear-gradient(90deg,rgba(255,255,255,0.06),rgba(255,255,255,0.12),rgba(255,255,255,0.06))}
.play-badge{position:absolute;inset:0;display:grid;place-items:center;pointer-events:none}
.play-icon{width:46px;height:46px;fill:white;filter:drop-shadow(0 2px 8px rgba(0,0,0,.4));opacity:.95}
@media (min-width:768px){.play-icon{width:54px;height:54px}}
.badge{border-radius:1rem;background-color:rgba(0,0,0,.55);padding:.375rem .75rem;font-size:.875rem;font-weight:600;color:#fff;backdrop-filter:saturate(180%) blur(6px)}
button .frame{transition:transform .15s ease}
button:hover .frame{transform:scale(1.01)}
</style>
