<template>
  <div :class="['w-full', isFull ? 'fixed inset-0 z-[60] bg-black/40 backdrop-blur-sm p-4 sm:p-6' : '']">
    <div
      :class="[
        'mx-auto rounded-2xl overflow-hidden border bg-white dark:bg-zinc-700 border-zinc-300 dark:border-zinc-800',
        isFull ? 'w-full max-w-5xl h-[85vh] shadow-2xl ring-1 ring-zinc-200/40 dark:ring-zinc-800' : ''
      ]"
    >
      <div
        :class="[
          'flex items-center gap-2 px-2',
          isFull ? 'py-2 border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/60' : 'py-2'
        ]"
      >
        <div
          ref="toolbarEl"
          class="ql-toolbar ql-snow flex flex-wrap gap-2 items-center px-0 py-0"
          :class="showToolbar ? '' : 'hidden'"
        >
          <select class="ql-header" />
          <button class="ql-bold" />
          <button class="ql-italic" />
          <button class="ql-underline" />
          <button class="ql-strike" />
          <button class="ql-blockquote" />
          <button class="ql-list" value="ordered" />
          <button class="ql-list" value="bullet" />
          <button class="ql-clean" />
        </div>

        <button
          type="button"
          :class="[
            'ml-auto inline-flex items-center gap-2 rounded-lg text-xs font-medium transition',
            isFull
              ? 'px-3 py-1.5 bg-zinc-800 text-white hover:bg-zinc-700'
              : 'px-2 py-1 text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800'
          ]"
          @click="toggleFull"
        >
          <svg v-if="!isFull" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
            <path d="M7 3h3v2H7v3H5V5a2 2 0 0 1 2-2Zm7 0h3a2 2 0 0 1 2 2v3h-2V5h-3V3ZM5 16h2v3h3v2H7a2 2 0 0 1-2-2v-3Zm12 3v-3h2v3a2 2 0 0 1-2 2h-3v-2h3Z" />
          </svg>
          <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
            <path d="M10 4H6v4H4V4a2 2 0 0 1 2-2h4v2Zm8 0v4h-2V4h-4V2h4a2 2 0 0 1 2 2ZM6 16H4v4a2 2 0 0 0 2 2h4v-2H6v-4Zm10 4h-4v2h4a2 2 0 0 0 2-2v-4h-2v4Z" />
          </svg>
          <span v-if="isFull">Exit</span>
        </button>
      </div>

      <div :class="['flex-1', isFull ? 'min-h-[220px]' : bodyMinHeight]">
        <div
          ref="editorEl"
          :class="[
            'h-full ql-container ql-snow !border-0',
            isCompact ? 'px-3 py-2' : 'p-4'
          ]"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, watch, computed } from 'vue'
import Quill from 'quill'

type Variant = 'input' | 'textarea' | 'rich'

const props = defineProps<{
  modelValue?: string
  variant?: Variant
  placeholder?: string
  maxLen?: number
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', v: string): void
  (e: 'ready', q: unknown): void
  (e: 'submit', plainText: string): void
}>()

const editor = ref<any>(null)
const editorEl = ref<HTMLElement | null>(null)
const toolbarEl = ref<HTMLElement | null>(null)
const isFull = ref(false)

const variant = computed<Variant>(() => props.variant ?? 'input')
const maxLen = computed(() => props.maxLen ?? 5000)

const isCompact = computed(() => !isFull.value && variant.value === 'input')
const showToolbar = computed(() => isFull.value || variant.value === 'rich')
const bodyMinHeight = computed(() => {
  if (variant.value === 'rich') return 'min-h-[180px]'
  if (variant.value === 'textarea') return 'min-h-[112px]'
  return 'min-h-[48px]'
})

const rtl = /[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF]/
function setDir() {
  if (!editor.value) return
  const dir = rtl.test(editor.value.getText()) ? 'rtl' : 'ltr'
  const align = dir === 'rtl' ? 'right' : 'left'
  editor.value.root.setAttribute('dir', dir)
  ;(editor.value.root.style as any).textAlign = align
}

function htmlToPlain(input: string): string {
  if (!input) return ''
  let s = input
    .replace(/<\/p>\s*<p>/gi, '\n\n')
    .replace(/<br\s*\/?>/gi, '\n')
    .replace(/<\/(div|li|h[1-6])>/gi, '\n')
    .replace(/<li>/gi, '- ')
    .replace(/<[^>]+>/g, '')
  const div = document.createElement('div')
  div.innerHTML = s
  return div.textContent || div.innerText || ''
}

function toggleFull() { isFull.value = !isFull.value }

onMounted(() => {
  if (!editorEl.value || !toolbarEl.value) return
  editor.value = new Quill(editorEl.value, {
    theme: 'snow',
    placeholder: props.placeholder || '',
    modules: { toolbar: toolbarEl.value }
  })
  editor.value.root.innerHTML = (props.modelValue && props.modelValue.trim()) ? props.modelValue : '<p><br/></p>'
  setDir()
  editor.value.on('text-change', () => {
    if (!editor.value) return
    emit('update:modelValue', editor.value.root.innerHTML)
    setDir()
  })
  editor.value.keyboard.addBinding({ key: 13, shortKey: true }, () => {
    const plain = htmlToPlain(editor.value!.root.innerHTML).slice(0, maxLen.value)
    if (plain.trim()) emit('submit', plain)
    return false
  })
  emit('ready', editor.value!)
})

watch(() => props.modelValue, (v) => {
  if (!editor.value) return
  const next = (v && v.trim()) ? v : '<p><br/></p>'
  if (next !== editor.value.root.innerHTML) {
    editor.value.root.innerHTML = next
    setDir()
  }
})
</script>

<style>
@import 'quill/dist/quill.snow.css';
.ql-container{min-height:0}
.ql-editor{height:100%;min-height:0}
.ql-editor.ql-blank::before{color:#9ca3af;font-style:italic;pointer-events:none}
.dark .ql-editor.ql-blank::before{color:#a1a1aa}
.dark .ql-snow .ql-picker{color:#e4e4e7}
.dark .ql-snow .ql-stroke,.dark .ql-snow .ql-stroke-miter{stroke:#e4e4e7}
.dark .ql-snow .ql-fill,.dark .ql-snow .ql-stroke.ql-fill{fill:#e4e4e7}
.dark .ql-toolbar .ql-picker-label,.dark .ql-toolbar button{color:#e4e4e7}
.dark .ql-toolbar button:hover .ql-stroke,.dark .ql-toolbar button:focus .ql-stroke{stroke:#a1a1aa}
.dark .ql-toolbar button:hover .ql-fill,.dark .ql-toolbar button:focus .ql-fill{fill:#a1a1aa}
.dark .ql-snow .ql-picker-options{background-color:#09090b;border-color:#27272a;color:#e4e4e7}
.dark .ql-snow .ql-picker-item{color:#e4e4e7}
.dark .ql-snow .ql-picker-item:hover,.dark .ql-snow .ql-picker-item.ql-selected{color:#a1a1aa}
</style>
