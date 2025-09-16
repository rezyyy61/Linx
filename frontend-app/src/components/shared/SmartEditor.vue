<template>
  <div :class="['w-full', isFull ? 'fixed inset-0 z-[60] bg-black/40 backdrop-blur-sm p-4 sm:p-6' : '']">
    <div
      :class="[
        'mx-auto rounded-2xl overflow-hidden border bg-white dark:bg-zinc-700 border-zinc-300 dark:border-zinc-800',
        isFull ? 'w-full max-w-5xl h-[85vh] shadow-2xl ring-1 ring-zinc-200/40 dark:ring-zinc-800 flex flex-col' : ''
      ]"
    >
      <!-- Header -->
      <div
        :class="[
          'flex items-center gap-2 px-2',
          isFull ? 'py-2 border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/60' : 'py-2'
        ]"
      >
        <div class="ml-auto flex items-center gap-2">
          <span class="text-xs text-zinc-400">{{ charCount }}/{{ maxLen }}</span>
          <button
            type="button"
            :class="[
              'inline-flex items-center gap-2 rounded-lg text-xs font-medium transition',
              isFull
                ? 'px-3 py-1.5 bg-zinc-800 text-white hover:bg-zinc-700'
                : 'px-2 py-1 text-zinc-500 hover:text-zinc-700 dark:text-zinc-300 dark:hover:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-zinc-800'
            ]"
            @click="toggleFull"
          >
            <svg
              v-if="!isFull"
              xmlns="http://www.w3.org/2000/svg"
              class="w-4 h-4"
              viewBox="0 0 24 24"
              fill="currentColor"
            >
              <path d="M7 3h3v2H7v3H5V5a2 2 0 0 1 2-2Zm7 0h3a2 2 0 0 1 2 2v3h-2V5h-3V3ZM5 16h2v3h3v2H7a2 2 0 0 1-2-2v-3Zm12 3v-3h2v3a2 2 0 0 1-2 2h-3v-2h3Z" />
            </svg>
            <svg
              v-else
              xmlns="http://www.w3.org/2000/svg"
              class="w-4 h-4"
              viewBox="0 0 24 24"
              fill="currentColor"
            >
              <path d="M10 4H6v4H4V4a2 2 0 0 1 2-2h4v2Zm8 0v4h-2V4h-4V2h4a2 2 0 0 1 2 2ZM6 16H4v4a2 2 0 0 0 2 2h4v-2H6v-4Zm10 4h-4v2h4a2 2 0 0 0 2-2v-4h-2v4Z" />
            </svg>
            <span v-if="isFull">Exit</span>
          </button>
        </div>
      </div>

      <!-- Body -->
      <div :class="['flex-1', isFull ? 'min-h-0 p-4' : bodyPadding]">
        <div
          :class="[
            'relative w-full rounded-xl border border-zinc-300 dark:border-zinc-800 bg-white dark:bg-zinc-900 focus-within:ring-2 focus-within:ring-indigo-500 transition',
            isFull ? 'h-full flex-1' : ''
          ]"
        >
          <textarea
            ref="ta"
            :class="[
              'w-full resize-none bg-transparent outline-none placeholder:text-zinc-400 dark:placeholder:text-zinc-400 text-sm leading-6 p-3 rounded-xl box-border',
              isFull ? 'h-full' : ''
            ]"
            :placeholder="props.placeholder || ''"
            :dir="dir"
            :style="{ textAlign: textAlign }"
            :value="val"
            @input="onInput"
            @keydown="onKeydown"
            @keyup="onKeyup"
            @click="onCaretMove"
            @blur="onBlur"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, watch, computed, nextTick } from 'vue'

type Variant = 'input' | 'textarea' | 'rich'

const props = defineProps<{
  modelValue?: string
  variant?: Variant
  placeholder?: string
  maxLen?: number
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', v: string): void
  (e: 'ready', payload: unknown): void
  (e: 'submit', plainText: string): void
  (e: 'mention-query', payload: { q: string; x: number; y: number }): void
  (e: 'mention-close'): void
}>()

// State
const ta = ref<HTMLTextAreaElement | null>(null)
const isFull = ref(false)
const val = ref<string>(props.modelValue ?? '')
const dir = ref<'ltr' | 'rtl'>('ltr')
const textAlign = ref<'left' | 'right'>('left')
const charCount = computed(() => val.value.length)

const variant = computed<Variant>(() => props.variant ?? 'input')
const maxLen = computed(() => props.maxLen ?? 5000)
const bodyPadding = computed(() => (variant.value === 'rich' ? 'p-4' : variant.value === 'textarea' ? 'p-3' : 'p-2'))

function toggleFull() {
  isFull.value = !isFull.value
  nextTick(() => autoResize())
}

// RTL
const rtl = /[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF]/
function refreshDir() {
  const d = rtl.test(val.value) ? 'rtl' : 'ltr'
  dir.value = d as any
  textAlign.value = d === 'rtl' ? 'right' : 'left'
}

// Auto-resize
function autoResize() {
  if (!ta.value) return
  if (isFull.value) {
    // در حالت Full، ارتفاع باید کل فضا رو پر کنه
    ta.value.style.height = '100%'
  } else {
    // در حالت معمولی، با محتوا رشد کنه
    ta.value.style.height = '0px'
    ta.value.style.height = Math.max(48, ta.value.scrollHeight) + 'px'
  }
}

// Helpers
function clampLen(s: string) {
  return s.length > maxLen.value ? s.slice(0, maxLen.value) : s
}

const mentionRe = /@([A-Za-z0-9_.-]{1,64})$/
function rtrimNewline(s: string) { return s.replace(/\r?\n$/, '') }

function getCaretIndex(): number {
  if (!ta.value) return 0
  return ta.value.selectionStart ?? 0
}
function setCaretIndex(i: number) {
  if (!ta.value) return
  const idx = Math.max(0, Math.min(i, val.value.length))
  ta.value.setSelectionRange(idx, idx)
}

// مختصات کرسر برای پاپ‌آور
function caretViewportCoords(targetIdx: number): { x: number; y: number } | null {
  if (!ta.value) return null
  const taEl = ta.value
  const taRect = taEl.getBoundingClientRect()
  const cs = window.getComputedStyle(taEl)

  const mir = document.createElement('div')
  mir.style.position = 'absolute'
  mir.style.left = `${taRect.left + window.scrollX}px`
  mir.style.top = `${taRect.top + window.scrollY}px`
  mir.style.width = `${taEl.clientWidth}px`
  mir.style.height = `${taEl.clientHeight}px`
  mir.style.whiteSpace = 'pre-wrap'
  mir.style.wordBreak = 'break-word'
  mir.style.overflow = 'hidden'
  mir.style.visibility = 'hidden'
  mir.style.pointerEvents = 'none'
  mir.style.zIndex = '-1'

  const propsToCopy = [
    'fontSize','fontFamily','fontWeight','fontStyle','letterSpacing','textTransform',
    'lineHeight','textAlign','paddingTop','paddingRight','paddingBottom','paddingLeft',
    'borderTopWidth','borderRightWidth','borderBottomWidth','borderLeftWidth','boxSizing',
  ] as const
  for (const p of propsToCopy) (mir.style as any)[p] = (cs as any)[p]
  mir.style.direction = dir.value
  mir.style.textAlign = textAlign.value

  const before = val.value.slice(0, targetIdx).replace(/\n$/g, '\n ')
  const afterFirst = val.value.slice(targetIdx, targetIdx + 1) || ' '
  mir.textContent = before
  const span = document.createElement('span')
  span.textContent = afterFirst === '\n' ? ' ' : afterFirst
  mir.appendChild(span)

  document.body.appendChild(mir)
  const spanRect = span.getBoundingClientRect()
  document.body.removeChild(mir)

  return { x: spanRect.left, y: spanRect.bottom }
}

function probeMention() {
  const idx = getCaretIndex()
  const L = Math.min(64, idx)
  const before = rtrimNewline(val.value.slice(idx - L, idx))
  const m = mentionRe.exec(before)
  if (!m || !(m[1] || '').length) { emit('mention-close'); return }
  const pos = caretViewportCoords(idx)
  if (!pos) { emit('mention-close'); return }
  emit('mention-query', { q: m[1] || '', x: pos.x, y: pos.y })
}

function replaceMentionWithSlug(slug: string) {
  const idx = getCaretIndex()
  const L = Math.min(64, idx)
  const before = rtrimNewline(val.value.slice(idx - L, idx))
  const m = mentionRe.exec(before)
  const matchLen = m ? m[0].length : 0
  const start = idx - matchLen
  const token = '@' + slug + ' '
  const next = val.value.slice(0, start) + token + val.value.slice(idx)
  val.value = clampLen(next)
  emit('update:modelValue', val.value)
  refreshDir()
  nextTick(() => {
    setCaretIndex(start + token.length)
    autoResize()
    probeMention()
  })
}

// Events
function onInput(e: Event) {
  const t = (e.target as HTMLTextAreaElement)
  val.value = clampLen(t.value)
  emit('update:modelValue', val.value)
  refreshDir()
  autoResize()
  probeMention()
}
function onKeydown(e: KeyboardEvent) {
  if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
    e.preventDefault()
    const plain = (val.value || '').slice(0, maxLen.value)
    if (plain.trim()) emit('submit', plain)
  }
}
function onKeyup() { probeMention() }
function onCaretMove() { probeMention() }
function onBlur() { emit('mention-close') }

// Public API
function insertMention(slug: string) { replaceMentionWithSlug(slug) }
function focus() {
  ta.value?.focus()
  nextTick(() => {
    const end = val.value.length
    setCaretIndex(end)
    probeMention()
  })
}
defineExpose({ insertMention, focus })

// Lifecycle
onMounted(() => {
  refreshDir()
  autoResize()
  emit('ready', { type: 'textarea', el: ta.value })
})
watch(() => props.modelValue, (v) => {
  if (v == null) v = ''
  if (v !== val.value) {
    val.value = clampLen(v)
    nextTick(() => {
      refreshDir()
      autoResize()
    })
  }
})
watch(isFull, () => {
  // هر تغییرِ Full/Normal => سایزبندی مجدد
  nextTick(() => autoResize())
})
</script>

<style>
textarea::placeholder{color:#9ca3af}
.dark textarea::placeholder{color:#a1a1aa}
textarea::-webkit-scrollbar{width:8px}
textarea::-webkit-scrollbar-thumb{background:#d4d4d8;border-radius:6px}
.dark textarea::-webkit-scrollbar-thumb{background:#3f3f46}
</style>
