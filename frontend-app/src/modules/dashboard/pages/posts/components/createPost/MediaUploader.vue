<script setup lang="ts">
import { ref, computed, isRef } from 'vue'
import { Icon } from '@iconify/vue'

const emit = defineEmits<{ (e: 'picked', files: File[]): void }>()
const props = withDefaults(
  defineProps<{
    remaining?: { total: number; image: number; video: number; audio: number; document: number } | any
  }>(),
  {
    remaining: () => ({ total: 10, image: 10, video: 1, audio: 1, document: 3 }),
  }
)

const remaining = computed(
  () =>
    (isRef(props.remaining) ? props.remaining.value : props.remaining) ??
    { total: 0, image: 0, video: 0, audio: 0, document: 0 }
)

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
  'application/vnd.ms-powerpoint.presentation.macroenabled.12',
].join(',')

const canImg = computed(() => remaining.value.image > 0 && remaining.value.total > 0)
const canVid = computed(() => remaining.value.video > 0 && remaining.value.total > 0)
const canAud = computed(() => remaining.value.audio > 0 && remaining.value.total > 0)
const canDoc = computed(() => remaining.value.document > 0 && remaining.value.total > 0)
</script>


<template>
  <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
    <div
      class="group cursor-pointer rounded-2xl border border-gray-300 bg-white p-4 text-center transition hover:shadow disabled:opacity-50 dark:border-gray-700 dark:bg-gray-900"
      :class="[dragging==='image' ? 'ring-2 ring-indigo-500' : '', !canImg ? 'pointer-events-none opacity-50' : '']"
      @click="canImg && pick('image')"
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
        Images <span class="text-xs text-gray-500">({{ remaining.image }})</span>
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
      class="group cursor-pointer rounded-2xl border border-gray-300 bg-white p-4 text-center transition hover:shadow disabled:opacity-50 dark:border-gray-700 dark:bg-gray-900"
      :class="[dragging==='video' ? 'ring-2 ring-indigo-500' : '', !canVid ? 'pointer-events-none opacity-50' : '']"
      @click="canVid && pick('video')"
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
        Videos <span class="text-xs text-gray-500">({{ remaining.video }})</span>
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
      class="group cursor-pointer rounded-2xl border border-gray-300 bg-white p-4 text-center transition hover:shadow disabled:opacity-50 dark:border-gray-700 dark:bg-gray-900"
      :class="[dragging==='audio' ? 'ring-2 ring-indigo-500' : '', !canAud ? 'pointer-events-none opacity-50' : '']"
      @click="canAud && pick('audio')"
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
        Audio <span class="text-xs text-gray-500">({{ remaining.audio }})</span>
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
      class="group cursor-pointer rounded-2xl border border-gray-300 bg-white p-4 text-center transition hover:shadow disabled:opacity-50 dark:border-gray-700 dark:bg-gray-900"
      :class="[dragging==='document' ? 'ring-2 ring-indigo-500' : '', !canDoc ? 'pointer-events-none opacity-50' : '']"
      @click="canDoc && pick('document')"
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
        Documents <span class="text-xs text-gray-500">({{ remaining.document }})</span>
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
