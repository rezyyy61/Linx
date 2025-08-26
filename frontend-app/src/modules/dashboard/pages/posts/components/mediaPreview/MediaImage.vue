<script setup lang="ts">
import { computed, onMounted, onBeforeUnmount } from 'vue'
import PhotoSwipeLightbox from 'photoswipe/lightbox'
import 'photoswipe/style.css'

type Item = { src: string; w?: number; h?: number; thumb?: string; alt?: string }
const props = defineProps<{ items: Item[] }>()
let lightbox: any
const gid = `pswp-${Math.random().toString(36).slice(2)}`

const list = computed(() => props.items || [])
const grid = computed(() => list.value.slice(0, 4))
const more = computed(() => Math.max(0, list.value.length - 4))

onMounted(() => {
  lightbox = new PhotoSwipeLightbox({
    gallery: `#${gid}`,
    children: 'a',
    pswpModule: () => import('photoswipe'),
  })
  lightbox.init()
})
onBeforeUnmount(() => { try { lightbox?.destroy() } catch { /* empty */ } lightbox = null })
</script>

<template>
  <div
    v-if="list.length"
    class="rounded-xl overflow-hidden"
  >
    <div
      :id="gid"
      class="grid grid-cols-2 gap-3"
    >
      <a
        v-for="(it, idx) in grid"
        :key="idx"
        :href="it.src"
        :data-pswp-width="it.w || 1600"
        :data-pswp-height="it.h || 900"
        target="_blank"
        rel="noreferrer"
        class="group relative block rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700"
      >
        <img
          :src="it.thumb || it.src"
          :alt="it.alt || ''"
          class="w-full h-full object-cover aspect-video"
        >
        <div
          v-if="list.length===1 && idx===0"
          class="absolute inset-0"
        />
        <div
          v-if="list.length===3 && idx===2"
          class="absolute inset-0"
        />
        <div
          v-if="more && idx===3"
          class="absolute inset-0 bg-black/40 grid place-items-center text-white text-sm"
        >+{{ more }} more</div>
      </a>
    </div>
  </div>
</template>
