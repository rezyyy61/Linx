<template>
  <a
    ref="root"
    class="block overflow-hidden rounded-xl border border-zinc-200 hover:border-indigo-300 hover:shadow-sm dark:border-zinc-800 dark:hover:border-indigo-700"
    :href="safe"
    target="_blank"
    rel="noopener noreferrer"
  >
    <div
      v-if="isInView"
      class="flex flex-col sm:flex-row"
    >
      <div class="sm:w-48">
        <div
          class="relative w-full"
          style="padding-top: 52%"
        >
          <img
            :src="media.image || fallback"
            alt=""
            class="absolute inset-0 h-full w-full object-cover"
            loading="lazy"
            draggable="false"
          >
        </div>
      </div>
      <div class="flex-1 p-3">
        <div class="line-clamp-1 text-sm font-semibold text-zinc-900 dark:text-zinc-100">
          {{ media.title || domain }}
        </div>
        <div class="mt-1 line-clamp-2 text-xs text-zinc-600 dark:text-zinc-300">
          {{ media.description || media.url }}
        </div>
        <div class="mt-2 text-xs text-zinc-500">{{ domain }}</div>
      </div>
    </div>
    <div
      v-else
      class="flex"
    >
      <div class="w-48">
        <div
          class="h-full w-full animate-pulse bg-zinc-200 dark:bg-zinc-800"
          style="padding-top: 52%"
        />
      </div>
      <div class="flex-1 space-y-2 p-3">
        <div class="h-4 w-2/3 animate-pulse rounded bg-zinc-200 dark:bg-zinc-800" />
        <div class="h-3 w-full animate-pulse rounded bg-zinc-200 dark:bg-zinc-800" />
        <div class="h-3 w-3/4 animate-pulse rounded bg-zinc-200 dark:bg-zinc-800" />
      </div>
    </div>
  </a>
</template>

<script setup lang="ts">
import type { LinkMedia } from '@/modules/public/postCard/types/media.types'
import { safeUrl } from '@/modules/public/postCard/utils/safeUrl'
import { useInView } from '@/modules/public/postCard/composables/useInView'

const props = defineProps<{ media: LinkMedia }>()
const safe = safeUrl(props.media.url)
const domain = new URL(safe).hostname.replace(/^www\./, '')
const fallback = `https://picsum.photos/seed/${encodeURIComponent(domain)}/600/315`
const { el: root, isInView } = useInView()
</script>
