<template>
  <div
    ref="root"
    class="grid gap-2 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800"
    :class="gridClass"
  >
    <template
      v-for="(it, idx) in limited"
      :key="it.id"
    >
      <button
        class="relative block w-full focus:outline-none"
        :class="cellClass(idx)"
        aria-label="Open media"
        @click="openAt(idx)"
      >
        <div
          class="tile frame bg-zinc-100 dark:bg-zinc-900"
          :style="varsFor(it)"
        >
          <img
            v-if="it.type==='image' && isInView"
            :src="it.url"
            :alt="it.alt || 'image'"
            class="max-w-full max-h-full object-contain"
            loading="lazy"
            draggable="false"
            @load="onImgLoad(it, $event)"
          >
          <template v-else-if="it.type==='video' && isInView">
            <img
              v-if="it.poster"
              :src="it.poster"
              alt=""
              class="max-w-full max-h-full object-contain"
              loading="lazy"
              draggable="false"
            >
            <video
              v-else
              :src="it.url"
              class="max-w-full max-h-full object-contain"
              preload="metadata"
              muted
              playsinline
              disablepictureinpicture
              controlslist="nodownload noplaybackrate"
            />
          </template>
          <div
            v-else
            class="h-full w-full animate-pulse"
          />
        </div>

        <div
          v-if="idx === limited.length - 1 && remaining > 0"
          class="pointer-events-none absolute right-2 top-2 z-20 rounded-full bg-black/70 px-3 py-2 text-s font-semibold text-white"
        >
          +{{ remaining }}
        </div>
      </button>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, reactive } from 'vue'
import { useLightbox } from '@/modules/public/postCard/composables/useLightbox'
import { useInView } from '@/modules/public/postCard/composables/useInView'

type MixedItem = {
  id: string
  type: 'image' | 'video'
  url: string
  poster?: string
  alt?: string
  aspectRatio?: string
  width?: number
  height?: number
}

const props = defineProps<{ items: MixedItem[]; gid?: string }>()
const { open } = useLightbox()
const { el: root, isInView } = useInView()

const maxTiles = 4
const limited = computed(() => props.items.slice(0, maxTiles))
const remaining = computed(() => Math.max(0, props.items.length - maxTiles))
const count = computed(() => Math.min(limited.value.length, maxTiles))

const gridClass = computed(() =>
  count.value === 1 ? 'grid-cols-1'
    : count.value === 2 ? 'grid-cols-2'
      : 'grid-cols-2 grid-rows-2'
)
function cellClass(i: number) { if (count.value === 3 && i === 0) return 'row-span-2'; return '' }

const nat = reactive<Record<string, { w: number; h: number }>>({})
function parseAR(it: MixedItem) {
  if (it.aspectRatio) {
    const [w, h] = it.aspectRatio.split('/').map(s => Number(s.trim()))
    if (w > 0 && h > 0) return { w, h }
  }
  if (it.width && it.height) return { w: it.width, h: it.height }
  if (nat[it.id]) return nat[it.id]
  return it.type === 'video' ? { w: 16, h: 9 } : { w: 4, h: 3 }
}
function varsFor(it: MixedItem) {
  const { w, h } = parseAR(it)
  return { '--ar-w': String(w), '--ar-h': String(h) }
}
function onImgLoad(it: MixedItem, e: Event) {
  const t = e.target as HTMLImageElement
  nat[it.id] = { w: t.naturalWidth, h: t.naturalHeight }
}
function openAt(idx: number) {
  const gid = props.gid || 'default'
  const lbItems = props.items.map(i =>
    i.type === 'image'
      ? { id: i.id, type: 'image' as const, url: i.url, alt: i.alt }
      : { id: i.id, type: 'video' as const, url: i.url, poster: i.poster }
  )
  const clickedId = limited.value[idx]?.id
  const realIndex = clickedId ? lbItems.findIndex(x => x.id === clickedId) : idx
  open(gid, lbItems, realIndex >= 0 ? realIndex : 0)
}
</script>

<style scoped>
.tile { aspect-ratio: var(--ar-w) / var(--ar-h); }
.frame { display: grid; place-items: center; padding: 0.5rem; width: 100%; height: 100%; }
@supports not (aspect-ratio: 1 / 1) {
  .tile { position: relative; }
  .tile::before { content: ""; display: block; padding-top: calc(var(--ar-h) / var(--ar-w) * 100%); }
  .frame { position: absolute; inset: 0; }
}
</style>
