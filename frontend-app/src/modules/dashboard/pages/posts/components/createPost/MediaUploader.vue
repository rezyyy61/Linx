<script setup lang="ts">
import { ref } from 'vue'
import { Icon } from '@iconify/vue'

const emit = defineEmits<{ (e: 'picked', files: File[]): void }>()

const inputImg = ref<HTMLInputElement | null>(null)
const inputVid = ref<HTMLInputElement | null>(null)
const inputAud = ref<HTMLInputElement | null>(null)
const inputDoc = ref<HTMLInputElement | null>(null)

const dragging = ref<null | 'image' | 'video' | 'audio' | 'document'>(null)

function pick(kind: 'image' | 'video' | 'audio' | 'document') {
  const map = { image: inputImg, video: inputVid, audio: inputAud, document: inputDoc }
  map[kind].value?.click()
}
function onPicked(e: Event) {
  const target = e.target as HTMLInputElement
  const files = Array.from(target.files || [])
  emit('picked', files)
  target.value = ''
}
function onDrop(e: DragEvent, _kind: 'image' | 'video' | 'audio' | 'document') {
  e.preventDefault()
  dragging.value = null
  const files = Array.from(e.dataTransfer?.files || [])
  emit('picked', files)
}
function onDragOver(e: DragEvent, kind: 'image' | 'video' | 'audio' | 'document') {
  e.preventDefault()
  dragging.value = kind
}
function onDragLeave() { dragging.value = null }

const acceptDoc = [
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
  <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
    <div
      class="group cursor-pointer rounded-2xl border border-gray-300 bg-white p-4 text-center transition hover:shadow dark:border-gray-700 dark:bg-gray-900"
      :class="dragging==='image' ? 'ring-2 ring-indigo-500' : ''"
      @click="pick('image')"
      @drop="onDrop($event,'image')"
      @dragover="onDragOver($event,'image')"
      @dragleave="onDragLeave"
    >
      <div class="mx-auto mb-2 flex h-20 w-20 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400">
        <Icon
          icon="solar:gallery-wide-bold-duotone"
          class="h-10 w-10"
        />
      </div>
      <div class="text-sm font-medium text-gray-800 dark:text-gray-100">
        Images
      </div>
      <input
        ref="inputImg"
        type="file"
        class="hidden"
        accept="image/*"
        multiple
        @change="onPicked"
      >
    </div>

    <div
      class="group cursor-pointer rounded-2xl border border-gray-300 bg-white p-4 text-center transition hover:shadow dark:border-gray-700 dark:bg-gray-900"
      :class="dragging==='video' ? 'ring-2 ring-indigo-500' : ''"
      @click="pick('video')"
      @drop="onDrop($event,'video')"
      @dragover="onDragOver($event,'video')"
      @dragleave="onDragLeave"
    >
      <div class="mx-auto mb-2 flex h-20 w-20 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400">
        <Icon
          icon="solar:clapperboard-play-bold-duotone"
          class="h-10 w-10"
        />
      </div>
      <div class="text-sm font-medium text-gray-800 dark:text-gray-100">
        Videos
      </div>
      <input
        ref="inputVid"
        type="file"
        class="hidden"
        accept="video/*"
        multiple
        @change="onPicked"
      >
    </div>

    <div
      class="group cursor-pointer rounded-2xl border border-gray-300 bg-white p-4 text-center transition hover:shadow dark:border-gray-700 dark:bg-gray-900"
      :class="dragging==='audio' ? 'ring-2 ring-indigo-500' : ''"
      @click="pick('audio')"
      @drop="onDrop($event,'audio')"
      @dragover="onDragOver($event,'audio')"
      @dragleave="onDragLeave"
    >
      <div class="mx-auto mb-2 flex h-20 w-20 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400">
        <Icon
          icon="solar:music-note-2-bold-duotone"
          class="h-10 w-10"
        />
      </div>
      <div class="text-sm font-medium text-gray-800 dark:text-gray-100">
        Audio
      </div>
      <input
        ref="inputAud"
        type="file"
        class="hidden"
        accept="audio/*"
        multiple
        @change="onPicked"
      >
    </div>

    <div
      class="group cursor-pointer rounded-2xl border border-gray-300 bg-white p-4 text-center transition hover:shadow dark:border-gray-700 dark:bg-gray-900"
      :class="dragging==='document' ? 'ring-2 ring-indigo-500' : ''"
      @click="pick('document')"
      @drop="onDrop($event,'document')"
      @dragover="onDragOver($event,'document')"
      @dragleave="onDragLeave"
    >
      <div class="mx-auto mb-2 flex h-20 w-20 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400">
        <Icon
          icon="solar:file-text-bold-duotone"
          class="h-10 w-10"
        />
      </div>
      <div class="text-sm font-medium text-gray-800 dark:text-gray-100">
        Documents
      </div>
      <input
        ref="inputDoc"
        type="file"
        class="hidden"
        :accept="acceptDoc"
        multiple
        @change="onPicked"
      >
    </div>
  </div>
</template>
