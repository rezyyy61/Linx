<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { formatBytes, iconForMime } from '../utils/file'
import type { EventDocument } from '../types'

defineProps<{ doc: EventDocument }>()
</script>

<template>
  <div class="flex items-center justify-between p-3 rounded-xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900">
    <div class="flex items-center gap-3 overflow-hidden">
      <Icon
        :icon="iconForMime(doc.mime)"
        class="w-6 h-6 text-neutral-700 dark:text-neutral-300"
      />
      <div class="truncate">
        <div class="text-sm font-medium text-neutral-900 dark:text-neutral-100 truncate">
          {{ doc.title }}
        </div>
        <div class="text-xs text-neutral-500 dark:text-neutral-400">
          {{ doc.mime }} • {{ formatBytes(doc.size) }}
        </div>
      </div>
    </div>
    <div class="flex items-center gap-2">
      <a
        :href="doc.url"
        target="_blank"
        rel="noopener"
        class="px-3 py-1 rounded-lg text-sm bg-neutral-200 dark:bg-neutral-700 text-neutral-800 dark:text-neutral-100 hover:opacity-90"
      >
        View
      </a>
      <a
        :href="doc.url"
        download
        class="px-3 py-1 rounded-lg text-sm bg-indigo-600 text-white hover:bg-indigo-700"
      >
        Download
      </a>
    </div>
  </div>
</template>
