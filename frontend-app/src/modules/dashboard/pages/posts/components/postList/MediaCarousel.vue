<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { Icon } from '@iconify/vue'

type Item = {
  id?: number
  url: string
  mime_type?: string
  width?: number
  height?: number
  title?: string
  poster?: string
  poster_url?: string
  thumb?: string
  thumbnail_url?: string
  preview_url?: string
  processed?: Record<string, any>
  meta?: Record<string, any>
}

const props = withDefaults(defineProps<{ items: Item[]; height?: number }>(), { height: 420 })

const box = ref<HTMLElement|null>(null)
const idx = ref(0)
const posterMap = ref<Record<string,string>>({})
const busy = new Set<string>()

const kindOf = (m: Item) =>
  m.mime_type?.startsWith('video/') ? 'video' :
    m.mime_type?.startsWith('image/') ? 'image' :
      m.mime_type?.startsWith('audio/') ? 'audio' : 'document'

const fromFields = (m: Item): string | null => {
  const cands = [
    m.poster, m.poster_url, m.thumb, m.thumbnail_url, m.preview_url,
    (m.processed as any)?.poster_url,
    (m.processed as any)?.thumb_url,
    (m.meta as any)?.poster_url,
  ].filter(Boolean) as string[]
  if (cands.length) return cands[0]
  if (kindOf(m) === 'image') return m.url
  return null
}

const slides = computed(() => props.items.map((m, i) => {
  const key = `${m.id ?? i}-${m.url}`
  return {
    key,
    kind: kindOf(m),
    src: m.url,
    mime: m.mime_type || '',
    title: m.title || '',
    poster: fromFields(m) || posterMap.value[m.url] || null,
  }
}))

async function makePoster(url: string): Promise<string | null> {
  return new Promise((resolve) => {
    const v = document.createElement('video')
    v.crossOrigin = 'anonymous'
    v.muted = true
    v.preload = 'metadata'
    v.playsInline = true as any
    v.src = url
    let to: any = null
    const done = (val: string | null) => { try { v.src = '' } catch { /* empty */ } if (to) clearTimeout(to); resolve(val) }
    const draw = () => {
      const w = v.videoWidth || 0, h = v.videoHeight || 0
      if (!w || !h) { done(null); return }
      const targetW = Math.min(720, w)
      const ratio = targetW / w
      const canvas = document.createElement('canvas')
      canvas.width = targetW
      canvas.height = Math.round(h * ratio)
      const ctx = canvas.getContext('2d')
      if (!ctx) { done(null); return }
      ctx.drawImage(v, 0, 0, canvas.width, canvas.height)
      try { done(canvas.toDataURL('image/jpeg', 0.8)) } catch { done(null) }
    }
    const onMeta = () => { try { v.currentTime = Math.min(1, (isFinite(v.duration) ? v.duration : 0) / 3) } catch { to = setTimeout(draw, 300) } }
    const onSeeked = () => draw()
    const onError = () => done(null)
    v.addEventListener('loadedmetadata', onMeta)
    v.addEventListener('seeked', onSeeked)
    v.addEventListener('error', onError)
  })
}

async function ensurePosters() {
  for (const s of slides.value) {
    if (s.kind !== 'video') continue
    if (s.poster) continue
    if (posterMap.value[s.src]) continue
    if (busy.has(s.src)) continue
    busy.add(s.src)
    const img = await makePoster(s.src)
    if (img) posterMap.value = { ...posterMap.value, [s.src]: img }
    busy.delete(s.src)
  }
}

function go(i:number) {
  if (!box.value) return
  const clamped = Math.max(0, Math.min(slides.value.length - 1, i))
  const w = box.value.clientWidth
  box.value.scrollTo({ left: clamped * w, behavior: 'smooth' })
}
function onScroll() {
  if (!box.value) return
  const w = box.value.clientWidth || 1
  idx.value = Math.round(box.value.scrollLeft / w)
}

let ro: ResizeObserver | null = null
onMounted(() => {
  if (box.value) box.value.addEventListener('scroll', onScroll, { passive: true })
  ro = new ResizeObserver(() => { if (box.value) go(idx.value) })
  if (box.value) ro.observe(box.value)
  ensurePosters()
})
onBeforeUnmount(() => {
  if (box.value) box.value.removeEventListener('scroll', onScroll)
  try { ro?.disconnect() } catch { /* empty */ }
  ro = null
})
watch(slides, () => ensurePosters(), { immediate: false })
</script>

<template>
  <div class="relative">
    <div
      ref="box"
      class="overflow-x-auto snap-x snap-mandatory no-scrollbar"
      :style="{ height: height + 'px' }"
    >
      <div class="flex w-full h-full">
        <div
          v-for="s in slides"
          :key="s.key"
          class="snap-start shrink-0 w-full h-full relative rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-black grid place-items-center"
        >
          <img
            v-if="s.poster"
            :src="s.poster"
            :alt="s.title"
            class="w-full h-full object-contain"
            loading="lazy"
            referrerpolicy="no-referrer"
          >
          <div
            v-else
            class="w-full h-full grid place-items-center text-gray-300"
          >
            <Icon
              :icon="s.kind==='video' ? 'mdi:filmstrip' : s.kind==='audio' ? 'mdi:music' : 'mdi:file-outline'"
              class="w-14 h-14"
            />
          </div>

          <div class="absolute top-2 left-2 inline-flex items-center gap-1 rounded-full bg-black/70 text-white px-2 py-0.5 text-[11px]">
            <Icon
              :icon="s.kind==='video' ? 'mdi:filmstrip' : s.kind==='image' ? 'mdi:image' : s.kind==='audio' ? 'mdi:music' : 'mdi:file-outline'"
              class="w-3.5 h-3.5"
            />
            <span class="capitalize">{{ s.kind }}</span>
          </div>
        </div>
      </div>
    </div>

    <button
      v-if="idx>0"
      type="button"
      class="absolute left-2 top-1/2 -translate-y-1/2 z-10 rounded-full bg-white/80 dark:bg-gray-900/80 p-1 border border-gray-200 dark:border-gray-700 hover:scale-105"
      @click="go(idx-1)"
    >
      <span
        class="iconify w-5 h-5 text-gray-800 dark:text-gray-100"
        data-icon="mdi:chevron-left"
      />
    </button>
    <button
      v-if="idx < slides.length-1"
      type="button"
      class="absolute right-2 top-1/2 -translate-y-1/2 z-10 rounded-full bg-white/80 dark:bg-gray-900/80 p-1 border border-gray-200 dark:border-gray-700 hover:scale-105"
      @click="go(idx+1)"
    >
      <span
        class="iconify w-5 h-5 text-gray-800 dark:text-gray-100"
        data-icon="mdi:chevron-right"
      />
    </button>

    <div
      v-if="slides.length>1"
      class="absolute bottom-2 left-1/2 -translate-x-1/2 flex items-center gap-1.5"
    >
      <span
        v-for="(_, i) in slides.length"
        :key="i"
        class="h-1.5 rounded-full transition-all"
        :class="i===idx ? 'w-6 bg-white' : 'w-2 bg-white/50'"
      />
    </div>
  </div>
</template>

<style scoped>
.no-scrollbar{scrollbar-width:none}
.no-scrollbar::-webkit-scrollbar{display:none}
</style>
