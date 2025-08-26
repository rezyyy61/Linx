<script setup lang="ts">
import { ref } from 'vue'

const props = withDefaults(defineProps<{ accept?: string; remaining?: number }>(), {
  accept: 'image/*,video/*,audio/*,application/pdf',
  remaining: Number.POSITIVE_INFINITY,
})
const emit = defineEmits<{ (e:'pick', files: File[]): void }>()
const dragOver = ref(false)
const inputRef = ref<HTMLInputElement|null>(null)

const pick = () => inputRef.value?.click()
const onInput = (e: Event) => {
  const t = e.target as HTMLInputElement
  if (!t.files) return
  emit('pick', Array.from(t.files))
  t.value = ''
}
const onDrop = (e: DragEvent) => {
  dragOver.value = false
  if (!e.dataTransfer) return
  emit('pick', Array.from(e.dataTransfer.files || []))
}
const onDragOver = (e: DragEvent) => { e.preventDefault(); dragOver.value = true }
const onDragLeave = () => { dragOver.value = false }
</script>

<template>
  <div
    class="relative rounded-2xl border-2 border-dashed bg-gray-50 p-6 transition focus-within:ring-2 focus-within:ring-emerald-500/30 dark:bg-gray-900 dark:border-gray-600"
    :class="dragOver ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-500/10' : 'border-gray-300 hover:border-gray-400 dark:hover:border-gray-500'"
    @dragover.prevent="onDragOver"
    @dragleave.prevent="onDragLeave"
    @drop.prevent="onDrop"
  >
    <div class="flex flex-col items-center justify-center text-center gap-3">
      <span
        class="iconify w-10 h-10 text-gray-500 dark:text-gray-300"
        data-icon="mdi:tray-arrow-up"
        aria-hidden="true"
      />
      <div class="text-sm text-gray-700 dark:text-gray-300">
        Drag & drop files here, or
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-xl px-3 py-1.5 bg-gray-900 text-white hover:bg-black dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white"
          aria-label="Select files"
          @click="pick"
        >
          <span
            class="iconify w-4 h-4"
            data-icon="mdi:paperclip"
            aria-hidden="true"
          />
          <span>Select files</span>
        </button>
        <div class="text-xs text-gray-500 dark:text-gray-400">
          Remaining: {{ Number.isFinite(remaining!) ? remaining : '∞' }}
        </div>
      </div>
      <input
        ref="inputRef"
        class="hidden"
        type="file"
        multiple
        :accept="props.accept"
        @change="onInput"
      >
    </div>
  </div>
</template>
