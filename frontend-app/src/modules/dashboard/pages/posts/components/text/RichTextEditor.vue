<template>
  <div :class="['rte-wrapper w-full', isFullScreen ? 'fixed inset-0 z-[60] bg-gray-100/95 dark:bg-gray-900/95 backdrop-blur-sm p-4 sm:p-6' : '']">
    <div :class="['rte-panel mx-auto bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-2xl overflow-hidden', isFullScreen ? 'w-full max-w-5xl h-[85vh] shadow-2xl ring-1 ring-gray-200 dark:ring-gray-700' : '']">
      <div
        ref="toolbarEl"
        class="ql-toolbar ql-snow flex flex-wrap gap-2 items-center px-2 py-2 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700"
      >
        <select class="ql-header" />
        <button class="ql-bold" />
        <button class="ql-italic" />
        <button class="ql-underline" />
        <button class="ql-strike" />
        <button class="ql-blockquote" />
        <button
          class="ql-list"
          value="ordered"
        />
        <button
          class="ql-list"
          value="bullet"
        />
        <button class="ql-clean" />
        <button
          type="button"
          class="ml-auto inline-flex items-center gap-2 px-3 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700"
          @click="toggleFullScreen"
        >
          <svg
            v-if="!isFullScreen"
            xmlns="http://www.w3.org/2000/svg"
            class="w-4 h-4 text-gray-800 dark:text-gray-50"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 8V6a2 2 0 012-2h2m8 0h2a2 2 0 012 2v2m0 8v2a2 2 0 01-2 2h-2m-8 0H6a2 2 0 01-2-2v-2"
            />
          </svg>
          <svg
            v-else
            xmlns="http://www.w3.org/2000/svg"
            class="w-4 h-4 text-gray-800 dark:text-gray-50"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 9h-4v-4m10 0h4v4m0 6v4h-4m-6 0h-4v-4"
            />
          </svg>
          <span class="text-xs font-medium">{{ isFullScreen ? 'Exit' : 'Full' }}</span>
        </button>
      </div>

      <div class="rte-body flex-1 min-h-[220px]">
        <div
          ref="editorEl"
          class="rte-editor h-full p-4"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import Quill from 'quill'

const props = defineProps<{ modelValue?: string }>()
const emit = defineEmits<{ (e: 'update:modelValue', v: string): void }>()

const editor = ref<Quill | null>(null)
const editorEl = ref<HTMLElement | null>(null)
const toolbarEl = ref<HTMLElement | null>(null)
const isFullScreen = ref(false)

const toggleFullScreen = () => { isFullScreen.value = !isFullScreen.value }

const rtlRegex = /[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF]/
const updateDirection = (text: string) => {
  const dir = rtlRegex.test(text) ? 'rtl' : 'ltr'
  const align = dir === 'rtl' ? 'right' : 'left'
  if (editor.value) {
    editor.value.root.setAttribute('dir', dir)
    ;(editor.value.root.style as any).textAlign = align
  }
}

onMounted(() => {
  if (!editorEl.value || !toolbarEl.value) return
  editor.value = new Quill(editorEl.value, {
    theme: 'snow',
    modules: { toolbar: toolbarEl.value },
  })
  editor.value.root.innerHTML = props.modelValue || '<p></p>'
  updateDirection(editor.value.getText())
  editor.value.on('text-change', () => {
    if (!editor.value) return
    emit('update:modelValue', editor.value.root.innerHTML)
    updateDirection(editor.value.getText())
  })
})

watch(() => props.modelValue, (v) => {
  if (!editor.value) return
  if (v !== editor.value.root.innerHTML) {
    editor.value.root.innerHTML = v || '<p></p>'
    updateDirection(editor.value.getText())
  }
})
</script>

<style>
@import 'quill/dist/quill.snow.css';

.rte-panel { display: flex; flex-direction: column; }
.rte-body { display: flex; flex-direction: column; }
.rte-panel .ql-container { border: 0; border-radius: 0 0 1rem 1rem; flex: 1; min-height: 0; }
.rte-panel .ql-editor { height: 100%; }
.rte-wrapper .ql-toolbar { border: 0; border-bottom: 1px solid rgba(229,231,235,1); border-radius: 1rem 1rem 0 0; }
.dark .rte-wrapper .ql-toolbar { border-bottom-color: rgba(55,65,81,1); }

/* Overlay bg already on wrapper; remove old overlay class */
.rte-overlay {}

.dark .rte-wrapper .ql-snow .ql-picker { color: #e5e7eb; }
.dark .rte-wrapper .ql-snow .ql-stroke,
.dark .rte-wrapper .ql-snow .ql-stroke-miter { stroke: #e5e7eb; }
.dark .rte-wrapper .ql-snow .ql-fill,
.dark .rte-wrapper .ql-snow .ql-stroke.ql-fill { fill: #e5e7eb; }
.dark .rte-wrapper .ql-toolbar .ql-picker-label,
.dark .rte-wrapper .ql-toolbar button { color: #e5e7eb; }
.dark .rte-wrapper .ql-toolbar button:hover .ql-stroke,
.dark .rte-wrapper .ql-toolbar button:focus .ql-stroke,
.dark .rte-wrapper .ql-toolbar .ql-picker-label:hover .ql-stroke { stroke: #60a5fa; }
.dark .rte-wrapper .ql-toolbar button:hover .ql-fill,
.dark .rte-wrapper .ql-toolbar button:focus .ql-fill,
.dark .rte-wrapper .ql-toolbar .ql-picker-label:hover .ql-fill { fill: #60a5fa; }
.dark .rte-wrapper .ql-toolbar button.ql-active .ql-stroke,
.dark .rte-wrapper .ql-toolbar .ql-picker-label.ql-active .ql-stroke { stroke: #60a5fa; }
.dark .rte-wrapper .ql-toolbar button.ql-active .ql-fill,
.dark .rte-wrapper .ql-toolbar .ql-picker-label.ql-active .ql-fill { fill: #60a5fa; }
.dark .rte-wrapper .ql-snow .ql-picker-options { background-color: #111827; border-color: #374151; color: #e5e7eb; }
.dark .rte-wrapper .ql-snow .ql-picker-item { color: #e5e7eb; }
.dark .rte-wrapper .ql-snow .ql-picker-item:hover,
.dark .rte-wrapper .ql-snow .ql-picker-item.ql-selected { color: #60a5fa; }
</style>
