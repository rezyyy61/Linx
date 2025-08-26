<script setup lang="ts">
import { computed } from 'vue'
import { Icon } from '@iconify/vue'
import PosterCarousel from '@/modules/dashboard/pages/posts/components/MediaCarousel.vue'
import { excerptFromHtml, directionFor } from '@/utils/text-utils'
import type { Post } from '@/stores/post/post'

type MediaItem = { id:number; url?:string; mime_type?:string; width?:number; height?:number; title?:string; poster?:string; poster_url?:string; thumb?:string; thumbnail_url?:string; preview_url?:string; processed?:any; meta?:any }

const props = withDefaults(defineProps<{ post: Post; mine?: boolean }>(), { mine: false })
const emit = defineEmits<{ open: []; edit: []; delete: [] }>()

const html = computed(() => props.post.content || '')
const summary = computed(() => excerptFromHtml(html.value, 180, { preserveWords: true, suffix: '…', preserveLineBreaks: false }))
const dir = computed(() => directionFor(html.value))

const carouselItems = computed(() => {
  const list = (props.post.media as unknown as MediaItem[]) || []
  return list
    .filter(m => !!m.url)
    .map((m) => {
      const mime = m.mime_type || ''
      const kind =
        mime.startsWith('video/') ? 'video' :
          mime.startsWith('image/') ? 'image' :
            mime.startsWith('audio/') ? 'audio' : 'document'
      const poster =
        m.poster || m.poster_url || m.preview_url || m.thumbnail_url || m.thumb || m.processed?.poster_url || m.meta?.poster_url || undefined
      return {
        url: m.url as string,
        mime,
        width: m.width,
        height: m.height,
        title: m.title || '',
        poster,
        kind,
      }
    })
})

const created = computed(() => {
  const d = props.post.created_at ? new Date(props.post.created_at as any) : null
  return d && !isNaN(d.getTime()) ? d.toLocaleString() : ''
})

const statusChip = computed(() => {
  if (props.post.status === 'draft') return { t: 'Draft', cls: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-800' }
  if ((props.post as any).status === 'archived') return { t: 'Archived', cls: 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700' }
  return { t: 'Published', cls: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-300 dark:border-emerald-800' }
})

const visChip = computed(() =>
  props.post.visibility === 'private'
    ? { t: 'Private', icon: 'mdi:lock-outline' }
    : props.post.visibility === 'friends'
      ? { t: 'Friends', icon: 'mdi:account-multiple-outline' }
      : { t: 'Public', icon: 'mdi:earth' }
)
</script>

<template>
  <article class="rounded-2xl overflow-hidden border border-gray-200 bg-white dark:bg-gray-900 dark:border-gray-700 flex flex-col">
    <div class="px-4 pt-4 space-y-2">
      <div class="flex items-center justify-between gap-2">
        <div class="text-xs text-gray-500 dark:text-gray-400">
          {{ created }}
        </div>
        <div class="shrink-0 flex items-center gap-1">
          <span
            class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[11px]"
            :class="statusChip.cls"
          >{{ statusChip.t }}</span>
          <span class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[11px] bg-gray-50 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700">
            <Icon
              :icon="visChip.icon"
              class="w-3.5 h-3.5"
            /> {{ visChip.t }}
          </span>
        </div>
      </div>

      <div
        v-if="summary"
        :dir="dir"
        class="text-sm text-gray-900 dark:text-gray-100"
      >
        {{ summary }}
      </div>
    </div>

    <div
      v-if="carouselItems.length"
      class="p-4"
    >
      <PosterCarousel
        :items="carouselItems"
        :height="420"
      />
    </div>

    <div class="mt-auto px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex items-center gap-2">
      <button
        type="button"
        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm bg-gray-900 text-white hover:bg-black dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white"
        @click="emit('open')"
      >
        <Icon
          icon="mdi:open-in-new"
          class="w-4 h-4"
        /> Open
      </button>
      <button
        v-if="mine"
        type="button"
        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm bg-gray-100 text-gray-900 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-100 dark:hover:bg-gray-700"
        @click="emit('edit')"
      >
        <Icon
          icon="mdi:pencil"
          class="w-4 h-4"
        /> Edit
      </button>
      <button
        v-if="mine"
        type="button"
        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm bg-red-600 text-white hover:bg-red-700"
        @click="emit('delete')"
      >
        <Icon
          icon="mdi:trash-can-outline"
          class="w-4 h-4"
        /> Delete
      </button>
    </div>
  </article>
</template>
