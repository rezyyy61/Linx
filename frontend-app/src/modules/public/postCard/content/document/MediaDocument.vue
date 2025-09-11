<template>
  <div class="overflow-hidden rounded-xl border border-white/10 bg-white/[0.03] p-3 dark:border-white/10">
    <div class="flex items-center gap-3">
      <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-lg border border-white/10 bg-white/5">
        <img
          v-if="media.thumbnailUrl"
          :src="media.thumbnailUrl"
          alt=""
          class="h-full w-full object-cover"
        >
        <Icon
          v-else
          :icon="iconName"
          class="h-7 w-7 text-zinc-300"
        />
      </div>
      <div class="min-w-0 flex-1">
        <div class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">
          {{ media.filename }}
        </div>
        <div class="text-xs text-zinc-500 dark:text-zinc-400">
          {{ subtype }} {{ sizeText }}
        </div>
      </div>
      <div class="flex items-center gap-2">
        <button
          v-if="previewable"
          class="rounded-lg border border-white/10 bg-white/10 px-3 py-1.5 text-xs text-zinc-100 hover:bg-white/15"
          @click="open=true"
        >
          Preview
        </button>
        <a
          :href="media.url"
          target="_blank"
          rel="noopener"
          class="rounded-lg border border-white/10 bg-white/10 px-3 py-1.5 text-xs text-zinc-100 hover:bg-white/15"
        >Download</a>
      </div>
    </div>
    <DocumentPreviewDialog
      v-if="previewable"
      :open="open"
      :src="media.url"
      :filename="media.filename"
      @close="open=false"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { Icon } from '@iconify/vue'
import type { DocumentMedia } from '../../types/media.types'
import { humanFileSize } from '../../utils/format'
import DocumentPreviewDialog from './DocumentPreviewDialog.vue'

const props = defineProps<{ media: DocumentMedia }>()
const open = ref(false)

const mime = computed(() => (props.media.mime || '').toLowerCase())
const ext = computed(() => {
  const n = props.media.filename.toLowerCase()
  const i = n.lastIndexOf('.')
  return i > -1 ? n.slice(i + 1) : ''
})
const subtype = computed(() => ext.value ? ext.value.toUpperCase() : (mime.value.split('/')[1] || '').toUpperCase())
const sizeText = computed(() => humanFileSize(props.media.sizeBytes))
const previewable = computed(() => mime.value === 'application/pdf' || ext.value === 'pdf')
const iconName = computed(() => {
  if (ext.value === 'pdf') return 'mdi:file-pdf-box'
  if (['doc','docx'].includes(ext.value)) return 'mdi:file-word-box'
  if (['xls','xlsx','csv'].includes(ext.value)) return 'mdi:file-excel-box'
  if (['ppt','pptx'].includes(ext.value)) return 'mdi:file-powerpoint-box'
  return 'mdi:file-document-outline'
})
</script>
