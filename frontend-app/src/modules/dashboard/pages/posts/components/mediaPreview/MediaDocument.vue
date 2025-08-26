<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{ src: string; filename?: string; mime?: string; height?: number }>()
const name = computed(() => props.filename || props.src.split('/').pop() || 'document')
const ext = computed(() => (name.value.split('.').pop() || '').toLowerCase())
const isPdf = computed(() => (props.mime || '').includes('pdf') || ext.value === 'pdf')
const h = computed(() => props.height ?? 480)
</script>

<template>
  <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden">
    <div
      v-if="isPdf"
      class="w-full"
    >
      <iframe
        :src="src"
        class="w-full"
        :style="{ height: h + 'px' }"
      />
    </div>
    <div
      v-else
      class="p-4 flex items-center gap-4"
    >
      <div class="w-12 h-12 rounded-lg bg-gray-100 dark:bg-gray-800 grid place-items-center">
        <span
          class="iconify w-6 h-6 text-gray-600 dark:text-gray-300"
          data-icon="mdi:file-document-outline"
        />
      </div>
      <div class="min-w-0 flex-1">
        <div class="truncate text-sm text-gray-900 dark:text-gray-100">
          {{ name }}
        </div>
        <div class="text-xs text-gray-500 dark:text-gray-400 uppercase">
          {{ ext }}
        </div>
      </div>
      <a
        :href="src"
        target="_blank"
        rel="noreferrer"
        class="inline-flex items-center gap-2 rounded-lg px-3 py-1.5 bg-gray-900 text-white hover:bg-black dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white"
      >
        <span
          class="iconify w-4 h-4"
          data-icon="mdi:open-in-new"
        />
        <span>Open</span>
      </a>
    </div>
  </div>
</template>
