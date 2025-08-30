<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Icon } from '@iconify/vue'

const { t, te } = useI18n()
const tr = (k: string) => te(`post.${k}`) ? t(`post.${k}`) : t(k)

const emit = defineEmits<{ (e:'picked', files: File[]): void }>()
const input = ref<HTMLInputElement|null>(null)
const dragging = ref(false)

function pick() { input.value?.click() }
function onPicked(e: Event) {
  const target = e.target as HTMLInputElement
  const files = Array.from(target.files || [])
  emit('picked', files)
  target.value = ''
}
function onDrop(e: DragEvent) {
  e.preventDefault()
  dragging.value = false
  const files = Array.from(e.dataTransfer?.files || [])
  emit('picked', files)
}
function onDragOver(e: DragEvent) { e.preventDefault(); dragging.value = true }
function onDragLeave() { dragging.value = false }

const accept = [
  'image/*','video/*','audio/*',
  'application/pdf',
  'application/msword',
  'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
  'application/vnd.ms-excel',
  'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
  'application/vnd.ms-powerpoint',
  'application/vnd.openxmlformats-officedocument.presentationml.presentation',
  'text/plain',
  'application/vnd.ms-excel.sheet.macroenabled.12',
  'application/vnd.ms-powerpoint.presentation.macroenabled.12'
].join(',')
</script>

<template>
  <div
    class="group relative rounded-2xl border-2 border-dashed p-6 text-center transition
           border-gray-300 hover:border-indigo-400 hover:bg-indigo-50/40
           dark:border-gray-700 dark:hover:border-indigo-500 dark:hover:bg-indigo-500/10"
    :class="dragging ? 'border-indigo-500 bg-indigo-50/60 dark:border-indigo-400 dark:bg-indigo-500/10' : ''"
    @click="pick"
    @drop="onDrop"
    @dragover="onDragOver"
    @dragleave="onDragLeave"
  >
    <div class="flex flex-col items-center justify-center gap-2">
      <Icon
        icon="solar:upload-minimalistic-bold-duotone"
        class="h-10 w-10 text-indigo-600 dark:text-indigo-400"
      />
      <div class="text-sm text-gray-700 dark:text-gray-200">
        {{ tr('media.uploader.drop_or_click') }}
      </div>
      <div class="text-xs text-gray-500 dark:text-gray-400">
        {{ tr('media.uploader.hint') }}
      </div>
      <button
        type="button"
        class="inline-flex items-center px-3 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
      >
        <Icon
          icon="solar:folder-with-files-bold-duotone"
          class="h-5 w-5"
        />
        <span>{{ tr('media.uploader.pick_button') }}</span>
      </button>
    </div>
    <input
      ref="input"
      type="file"
      class="hidden"
      :accept="accept"
      multiple
      @change="onPicked"
    >
  </div>
</template>
