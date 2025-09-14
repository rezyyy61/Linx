<template>
  <section
    v-if="safeHtml"
    class="mb-3"
    :dir="dirAttr"
  >
    <div
      ref="box"
      v-safe-html="safeHtml"
      class="prose prose-zinc max-w-none whitespace-pre-wrap text-[15px] leading-relaxed text-zinc-800 dark:prose-invert dark:text-zinc-200"
      :class="dirAttr==='rtl' ? 'text-right' : 'text-left'"
      :style="clamped ? { maxHeight: maxH + 'px', overflow: 'hidden' } : {}"
    />
    <div
      v-if="isLong"
      class="mt-2 flex items-center justify-end gap-2"
    >
      <button
        v-if="!isVeryLong"
        class="text-xs font-medium text-indigo-600 hover:underline dark:text-indigo-400"
        @click="clamped = !clamped"
      >
        {{ clamped ? 'Show more' : 'Show less' }}
      </button>
      <button
        class="rounded-lg border border-white/10 bg-white/10 px-3 py-1.5 text-xs font-medium text-zinc-900 hover:bg-white/20 dark:bg-white/10 dark:text-zinc-100"
        @click="openReader = true"
      >
        Read
      </button>
    </div>

    <ReaderDialog
      :open="openReader"
      :html="safeHtmlFull"
      @close="openReader = false"
    />
  </section>
</template>

<script setup lang="ts">
import { ref, computed, nextTick, watch, onMounted, onBeforeUnmount } from 'vue'
import type { Directive } from 'vue'
import ReaderDialog from './ReaderDialog.vue'
import { parseRichText } from '../text/parse/index'

const props = defineProps<{
  html: string
  clampLines?: number
  readerThresholdLines?: number
  sanitize?: boolean
  treatAsHtml?: boolean
  parseEntities?: boolean
}>()

const clampLines = computed(() => props.clampLines ?? 6)
const readerThresholdLines = computed(() => props.readerThresholdLines ?? 24)

function sanitizeHtml(input: string) {
  const doc = new DOMParser().parseFromString(input || '', 'text/html')
  const dangerous = ['script', 'style', 'iframe', 'object', 'embed', 'link', 'meta']
  doc.querySelectorAll(dangerous.join(',')).forEach(n => n.remove())
  doc.querySelectorAll('*').forEach(el => {
    ;[...el.attributes].forEach(a => {
      const n = a.name.toLowerCase()
      const v = a.value || ''
      if (n.startsWith('on')) el.removeAttribute(a.name)
      if ((n === 'href' || n === 'src') && /^\s*javascript:/i.test(v)) el.removeAttribute(a.name)
      if (n === 'src' && v && !/^(https?:|data:image\/|\/)/i.test(v)) el.removeAttribute(a.name)
      if (n === 'href' && v && !/^(https?:|mailto:|\/)/i.test(v)) el.removeAttribute(a.name)
      if (n === 'style') {
        // فقط خاصیت‌های background رو پاک کن
        const cleaned = v
          .replace(/background(-color)?\s*:[^;]+;?/gi, '')
          .replace(/background\s*:[^;]+;?/gi, '')
          .trim()
        if (cleaned) el.setAttribute('style', cleaned); else el.removeAttribute('style')
      }

    })
    if (el.tagName.toLowerCase() === 'a') {
      const a = el as HTMLAnchorElement
      if (a.href) { a.rel = 'noopener nofollow ugc'; a.target = '_blank' }
    }
    if (el.tagName.toLowerCase() === 'img') {
      const im = el as HTMLImageElement
      im.loading = 'lazy'; im.decoding = 'async'; im.referrerPolicy = 'no-referrer'
      im.classList.add('rounded-xl','max-w-full','h-auto')
    }

  })
  return doc.body.innerHTML
}

function enrichHtmlPreservingTags(html: string): string {
  if (!html) return ''
  const doc = new DOMParser().parseFromString(html, 'text/html')
  const SKIP = new Set(['a','code','pre','script','style'])
  function hasSkipAncestor(n: Node) {
    let p = (n.parentElement as HTMLElement | null)
    while (p) {
      if (SKIP.has(p.tagName.toLowerCase())) return true
      p = p.parentElement
    }
    return false
  }
  const walker = doc.createTreeWalker(doc.body, NodeFilter.SHOW_TEXT)
  const nodes: Text[] = []
  let cur: Node | null
  while ((cur = walker.nextNode())) {
    const t = cur as Text
    if (!t.nodeValue?.trim()) continue
    if (hasSkipAncestor(t)) continue
    nodes.push(t)
  }
  for (const tn of nodes) {
    const raw = tn.nodeValue || ''
    const htmlFrag = parseRichText(raw)
    if (htmlFrag === escapeHtmlFallback(raw)) continue
    const span = doc.createElement('span')
    span.innerHTML = htmlFrag
    tn.replaceWith(...Array.from(span.childNodes))
  }
  return doc.body.innerHTML
}

function escapeHtmlFallback(s: string) {
  return s
    .replace(/&/g,'&amp;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;')
    .replace(/'/g,'&#39;')
    .replace(/\n/g,'<br/>')
}

const processedHtml = computed(() => {
  const input = props.html || ''
  const seemsHtml = props.treatAsHtml ?? /<\/?[a-z][\s\S]*>/i.test(input)
  const parseOn = props.parseEntities !== false
  if (!parseOn) return input
  if (!input.trim()) return ''
  const out = !seemsHtml ? parseRichText(input) : enrichHtmlPreservingTags(input)
  if (!/<a\s/i.test(out) && /(@[A-Za-z0-9_]{2,30}|#[\p{L}\d_]{2,50}|https?:\/\/|www\.)/u.test(input)) {
    return parseRichText(input)
  }
  return out
})

const safeHtmlFull = computed(() => props.sanitize === false ? processedHtml.value : sanitizeHtml(processedHtml.value))
const safeHtml = computed(() => safeHtmlFull.value)

function textFromHtml(html: string) {
  const doc = new DOMParser().parseFromString(html || '', 'text/html')
  return (doc.body.textContent || '').trim()
}
const dirAttr = computed<'rtl'|'ltr'>(() => {
  const t = textFromHtml(safeHtmlFull.value)
  const rtl = (t.match(/[\u0590-\u05FF\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\uFB50-\uFDFF\uFE70-\uFEFF]/gu) || []).length
  const ltr = (t.match(/[A-Za-z]/g) || []).length
  return rtl > ltr ? 'rtl' : 'ltr'
})

const box = ref<HTMLElement | null>(null)
const maxH = ref(0)
const isLong = ref(false)
const isVeryLong = ref(false)
const clamped = ref(true)
const openReader = ref(false)

async function measure() {
  await nextTick()
  if (!box.value) return
  const cs = getComputedStyle(box.value)
  const lh = parseFloat(cs.lineHeight || '24') || 24
  maxH.value = Math.round(lh * clampLines.value)
  const lines = Math.ceil(box.value.scrollHeight / lh)
  isLong.value = lines > clampLines.value + 0.1
  isVeryLong.value = lines > readerThresholdLines.value + 0.1
  if (isVeryLong.value) clamped.value = true
}

let ro: ResizeObserver | null = null
onMounted(() => {
  measure()
  ro = new ResizeObserver(() => measure())
  if (box.value) ro.observe(box.value)
  setTimeout(measure, 0)
  setTimeout(measure, 200)
})
onBeforeUnmount(() => {
  if (ro && box.value) ro.unobserve(box.value)
  ro = null
})
watch([safeHtml, clampLines, readerThresholdLines], () => measure())

const vSafeHtml: Directive<HTMLElement, string> = {
  mounted(el, binding) { el.innerHTML = binding.value || '' },
  updated(el, binding) { if (binding.value !== binding.oldValue) el.innerHTML = binding.value || '' }
}
</script>

<style scoped>
/* --- hard reset for entities (override any old chip styles) --- */
:deep(a[data-entity]),
:deep(span[data-entity]) {
  display: inline !important;
  background: transparent !important;
  border: 0 !important;
  border-radius: 0 !important;
  padding: 0 !important;
  box-shadow: none !important;
}

/* link style (no bg) */
:deep(a[data-entity="link"]) {
  color: #4338ca !important;
  text-decoration: underline !important;
  word-break: break-word;
  overflow-wrap: anywhere;
}
:deep(.dark a[data-entity="link"]) { color: #a5b4fc !important; }

/* mention/hashtag only color change (no bg/border) */
:deep(a[data-entity="mention"]),
:deep(span[data-entity="mention"]) {
  color: #1d4ed8 !important;
}
:deep(.dark a[data-entity="mention"]),
:deep(.dark span[data-entity="mention"]) {
  color: #93c5fd !important;
}

:deep(a[data-entity="hashtag"]),
:deep(span[data-entity="hashtag"]) {
  color: #6d28d9 !important;
}
:deep(.dark a[data-entity="hashtag"]),
:deep(.dark span[data-entity="hashtag"]) {
  color: #c4b5fd !important;
}

/* optional hover tone */
:deep(a[data-entity]:hover),
:deep(span[data-entity]:hover) {
  filter: brightness(1.05);
}

</style>
