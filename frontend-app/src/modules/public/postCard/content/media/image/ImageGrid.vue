<template>
  <div
    ref="root"
    class="grid gap-2 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800"
    :class="gridClass"
  >
    <template
      v-for="(img, idx) in limited"
      :key="img.id"
    >
      <button
        class="relative block w-full focus:outline-none"
        :class="cellClass(idx)"
        :style="styleFor(img)"
        aria-label="Open image"
        @click="openAt(idx)"
      >
        <img
          v-if="isInView"
          :src="img.url"
          :alt="img.alt || 'image'"
          class="absolute inset-0 h-full w-full object-cover transition-opacity duration-300"
          loading="lazy"
          draggable="false"
        >
        <div
          v-else
          class="absolute inset-0 animate-pulse bg-zinc-100 dark:bg-zinc-800"
        />
        <div
          v-if="idx === limited.length - 1 && remaining > 0"
          class="absolute inset-0 flex items-center justify-center bg-black/50 text-white text-xl font-semibold"
        >
          +{{ remaining }}
        </div>
      </button>
    </template>
  </div>
</template>

<script setup lang="ts">
import { useLightbox } from '@/modules/public/postCard/composables/useLightbox'
import { useInView } from '@/modules/public/postCard/composables/useInView'

type GItem = { id: string; url: string; alt?: string; aspectRatio?: string; width?: number; height?: number }

const props = defineProps<{ items: GItem[]; gid?: string }>()
const { open } = useLightbox()
const { el: root, isInView } = useInView()

const count = Math.min(props.items.length, 4)
const limited = props.items.slice(0, 4)
const remaining = Math.max(0, props.items.length - 4)

const gridClass = count === 1 ? 'grid-cols-1' : count === 2 ? 'grid-cols-2' : count === 3 ? 'grid-cols-2 grid-rows-2' : 'grid-cols-2 grid-rows-2'
function cellClass(i: number) { if (count === 3 && i === 0) return 'row-span-2'; return '' }
function styleFor(img: GItem) { const ratio = img.aspectRatio || '1/1'; const [w, h] = ratio.split('/').map(Number); return { paddingTop: `${(h / w) * 100}%` } }

function openAt(startIndex: number) {
  const gid = props.gid || 'default'
  const list = props.items.map(i => ({ id: i.id, type: 'image' as const, url: i.url, alt: i.alt }))
  open(gid, list, startIndex)
}
</script>
