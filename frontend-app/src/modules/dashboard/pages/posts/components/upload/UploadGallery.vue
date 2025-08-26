<script setup lang="ts">
import { computed, ref, onMounted, watch } from 'vue'
import type { ExistingItem } from '@/modules/dashboard/pages/posts/pages/useUploadHelpers'
import { useFileCaches, useVideoPoster } from '@/modules/dashboard/pages/posts/pages/useUploadHelpers'

const props = withDefaults(defineProps<{
  files: File[]
  existing: ExistingItem[]
}>(), {})

const emit = defineEmits<{
  (e:'remove:file', index: number): void
  (e:'remove:existing', index: number): void
}>()

const { fileUrl, posterOf, posterCache } = useFileCaches()
const { generateVideoPoster } = useVideoPoster(fileUrl, posterCache)
const expanded = ref(false)

const items = computed(() => {
  const a = props.files.map((f, i) => ({ kind: 'new' as const, i, type: f.type || '', src: f.type.startsWith('image/') ? fileUrl(f) : '', file: f }))
  const b = props.existing.map((m, i) => ({ kind: 'old' as const, i, type: m.mime_type || '', src: m.url || '' }))
  return [...a, ...b]
})
const total = computed(() => items.value.length)
const hiddenCount = computed(() => Math.max(0, total.value - 4))
const visibleItems = computed(() => {
  if (expanded.value) return items.value
  if (total.value <= 4) return items.value
  return items.value.slice(0, 4)
})

function tileClass(idx: number, len: number) {
  if (len === 1 && idx === 0) return 'col-span-2'
  if (len === 3 && idx === 2) return 'col-span-2'
  return 'col-span-1'
}
function onRemove(it: any) {
  if (it.kind === 'new') emit('remove:file', it.i)
  else emit('remove:existing', it.i)
}
function isVideo(it: any) { return it.type?.startsWith('video/') }
function isImage(it: any) { return it.type?.startsWith('image/') }

function ensurePosters() {
  for (const it of items.value) {
    if (it.kind === 'new' && isVideo(it)) void generateVideoPoster(it.file)
  }
}
onMounted(ensurePosters)
watch(items, ensurePosters)
</script>

<template>
  <div
    v-if="total"
    class="space-y-2"
  >
    <div class="grid grid-cols-2 gap-3">
      <div
        v-for="(it, idx) in visibleItems"
        :key="`${it.kind}-${it.i}-${idx}`"
        class="group relative overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800"
        :class="tileClass(idx, visibleItems.length)"
      >
        <div class="aspect-video w-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
          <img
            v-if="isImage(it)"
            :src="it.src"
            class="w-full h-full object-cover"
            alt=""
          >
          <img
            v-else-if="it.kind==='new' && isVideo(it) && posterOf(it.file)"
            :src="posterOf(it.file)"
            class="w-full h-full object-cover"
            alt=""
          >
          <video
            v-else-if="it.kind==='new' && isVideo(it)"
            :src="fileUrl(it.file)"
            class="w-full h-full object-cover"
            preload="metadata"
            muted
            playsinline
            controls
          />
          <span
            v-else-if="isVideo(it)"
            class="iconify w-10 h-10 text-gray-500"
            data-icon="mdi:filmstrip"
            aria-hidden="true"
          />
          <span
            v-else
            class="iconify w-10 h-10 text-gray-500"
            data-icon="mdi:file"
            aria-hidden="true"
          />
        </div>

        <button
          type="button"
          class="absolute top-2 right-2 inline-flex items-center justify-center rounded-full bg-white/90 dark:bg-gray-900/80 p-1 shadow hover:scale-105"
          aria-label="Remove"
          @click="onRemove(it)"
        >
          <span
            class="iconify w-4 h-4 text-gray-700 dark:text-gray-200"
            data-icon="mdi:close"
            aria-hidden="true"
          />
        </button>

        <button
          v-if="!expanded && idx===3 && hiddenCount>0"
          type="button"
          class="absolute inset-0 grid place-items-center bg-black/40 text-white text-sm"
          @click="expanded = true"
        >
          +{{ hiddenCount }} more
        </button>
      </div>
    </div>

    <div
      v-if="expanded && hiddenCount>0"
      class="flex justify-end"
    >
      <button
        type="button"
        class="text-xs px-2 py-1 rounded-md bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600"
        @click="expanded = false"
      >
        Collapse
      </button>
    </div>
  </div>
</template>
