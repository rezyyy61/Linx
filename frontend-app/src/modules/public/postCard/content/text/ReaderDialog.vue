<template>
  <teleport to="body">
    <transition name="fade">
      <div
        v-if="open"
        class="fixed inset-0 z-[95]"
      >
        <div
          class="absolute inset-0 bg-black/50 backdrop-blur-sm"
          @click="$emit('close')"
        />
        <div class="absolute inset-0 flex flex-col">
          <div class="mx-auto mt-6 w-[96vw] sm:w-[90vw] lg:w-[80vw] max-w-[78rem] rounded-2xl border border-white/10 bg-slate-900/70 shadow-[inset_0_1px_0_rgba(255,255,255,.06)] backdrop-blur-sm">
            <div class="flex items-center justify-between gap-3 px-4 py-3">
              <div class="flex min-w-0 items-center gap-2">
                <h3 class="truncate text-sm font-semibold text-zinc-100">
                  Reader
                </h3>
                <div class="text-xs text-zinc-400">
                  ~{{ readingTime }} min
                </div>
              </div>
              <div class="flex flex-wrap items-center gap-2">
                <div class="flex items-center gap-2">
                  <div class="select-wrap">
                    <select
                      v-model="prefs.fontFamily"
                      class="select"
                    >
                      <option value="sans">
                        Sans
                      </option>
                      <option value="serif">
                        Serif
                      </option>
                    </select>
                  </div>
                  <div class="select-wrap">
                    <select
                      v-model.number="prefs.fontSize"
                      class="select"
                    >
                      <option :value="14">
                        14
                      </option>
                      <option :value="16">
                        16
                      </option>
                      <option :value="18">
                        18
                      </option>
                      <option :value="20">
                        20
                      </option>
                      <option :value="22">
                        22
                      </option>
                      <option :value="24">
                        24
                      </option>
                    </select>
                  </div>
                  <div class="select-wrap">
                    <select
                      v-model.number="prefs.lineHeight"
                      class="select"
                    >
                      <option :value="1.5">
                        LH 1.5
                      </option>
                      <option :value="1.7">
                        LH 1.7
                      </option>
                      <option :value="1.9">
                        LH 1.9
                      </option>
                    </select>
                  </div>
                  <div class="select-wrap">
                    <select
                      v-model="prefs.width"
                      class="select"
                    >
                      <option value="narrow">
                        Narrow
                      </option>
                      <option value="normal">
                        Normal
                      </option>
                      <option value="wide">
                        Wide
                      </option>
                    </select>
                  </div>
                  <div class="select-wrap hidden sm:inline-flex">
                    <select
                      v-model="prefs.theme"
                      class="select"
                    >
                      <option value="auto">
                        Auto
                      </option>
                      <option value="dark">
                        Dark
                      </option>
                      <option value="light">
                        Light
                      </option>
                      <option value="sepia">
                        Sepia
                      </option>
                    </select>
                  </div>
                </div>
                <div class="mx-1 hidden h-5 w-px bg-white/10 sm:block" />
                <div class="flex items-center gap-2">
                  <button
                    class="tool"
                    @click="toggleSearch"
                  >
                    Search
                  </button>
                  <button
                    class="tool"
                    @click="toggleToc"
                  >
                    TOC
                  </button>
                </div>
                <div class="mx-1 h-5 w-px bg-white/10" />
                <div class="flex items-center gap-2">
                  <button
                    class="tool"
                    @click="copy"
                  >
                    Copy
                  </button>
                  <button
                    class="tool"
                    @click="printDoc"
                  >
                    Print
                  </button>
                  <button
                    class="tool"
                    @click="downloadHtml"
                  >
                    HTML
                  </button>
                  <button
                    class="tool"
                    @click="downloadTxt"
                  >
                    TXT
                  </button>
                </div>
                <button
                  class="rounded-md p-2 text-zinc-100 hover:bg-white/10"
                  aria-label="Close"
                  @click="$emit('close')"
                >
                  <svg
                    viewBox="0 0 24 24"
                    class="h-5 w-5"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.5"
                  ><path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M6 18L18 6M6 6l12 12"
                  /></svg>
                </button>
              </div>
            </div>

            <div
              v-if="searchOpen"
              class="px-4"
            >
              <div class="flex items-center gap-2 rounded-lg border border-white/10 bg-white/10 p-2">
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="Search…"
                  class="focus-ring flex-1 bg-transparent text-sm text-zinc-100 outline-none placeholder:text-zinc-400"
                  @keydown.enter.prevent="goTo(0)"
                >
                <div class="w-16 text-center text-xs text-zinc-300">
                  {{ hitsCount ? currentHit+1 : 0 }}/{{ hitsCount }}
                </div>
                <button
                  class="tool"
                  @click="prevHit"
                >
                  Prev
                </button>
                <button
                  class="tool"
                  @click="nextHit"
                >
                  Next
                </button>
                <button
                  class="tool !text-rose-200 hover:!bg-rose-500/10"
                  @click="clearSearch"
                >
                  Clear
                </button>
              </div>
            </div>

            <div class="relative mt-3 grid grid-cols-1 gap-0 px-2 pb-3 sm:px-4">
              <div class="absolute inset-x-0 top-0 h-1">
                <div
                  class="h-1 bg-indigo-500/80 transition-all"
                  :style="{width: progress+'%'}"
                />
              </div>
              <div
                class="grid grid-cols-1 gap-4 sm:grid-cols-[1fr,17rem]"
                :class="{'sm:grid-cols-1': !tocOpen}"
              >
                <div
                  ref="scrollArea"
                  class="focus-ring max-h-[70vh] overflow-y-auto rounded-xl border border-white/10 bg-white/[0.03] p-5"
                  :class="contentThemeClass"
                >
                  <div
                    ref="contentEl"
                    v-safe-html="effectiveHtml"
                    :dir="dirAttr"
                    :style="contentStyle"
                    class="prose-base mx-auto whitespace-pre-wrap break-words"
                    :class="[widthClass, proseClass, dirAttr==='rtl' ? 'text-right' : 'text-left']"
                  />
                </div>

                <aside
                  v-if="tocOpen"
                  class="focus-ring max-h-[70vh] overflow-y-auto rounded-xl border border-white/10 bg-white/[0.03] p-3 text-sm"
                >
                  <div class="mb-2 text-xs font-medium text-zinc-400">
                    Contents
                  </div>
                  <ul class="space-y-1">
                    <li
                      v-for="t in toc"
                      :key="t.id"
                    >
                      <button
                        class="w-full truncate rounded-md px-2 py-1 text-left text-zinc-100 hover:bg-white/10"
                        @click="scrollToId(t.id)"
                      >
                        {{ t.text }}
                      </button>
                    </li>
                  </ul>
                </aside>
              </div>
            </div>
          </div>
        </div>

        <div class="fixed bottom-6 left-1/2 z-[96] -translate-x-1/2">
          <div class="flex items-center gap-2 rounded-full border border-white/10 bg-slate-900/80 px-2 py-1.5 text-xs text-zinc-100 backdrop-blur-sm">
            <button
              class="pill"
              :disabled="!ttsSupported"
              @click="ttsToggle"
            >
              {{ ttsState==='playing'?'Pause':'Play' }}
            </button>
            <button
              class="pill"
              :disabled="!ttsSupported"
              @click="ttsStop"
            >
              Stop
            </button>
            <div class="mx-1 h-4 w-px bg-white/10" />
            <button
              class="pill"
              @click="decSize"
            >
              A-
            </button>
            <button
              class="pill"
              @click="incSize"
            >
              A+
            </button>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import type { Directive, CSSProperties } from 'vue'

const props = defineProps<{ open: boolean; html: string; sanitize?: boolean }>()
const emit = defineEmits<{ (e:'close'): void }>()

function sanitizeHtml(input: string) {
  const doc = new DOMParser().parseFromString(input || '', 'text/html')
  const dangerous = ['script','style','iframe','object','embed','link','meta']
  doc.querySelectorAll(dangerous.join(',')).forEach(n => n.remove())
  doc.querySelectorAll('*').forEach(el => {
    ;[...el.attributes].forEach(a => {
      const n = a.name.toLowerCase()
      const v = a.value || ''
      if (n.startsWith('on')) el.removeAttribute(a.name)
      if ((n === 'href' || n === 'src') && /^\s*javascript:/i.test(v)) el.removeAttribute(a.name)
      if (n === 'src' && v && !/^(https?:|data:image\/|\/)/i.test(v)) el.removeAttribute(a.name)
      if (n === 'href' && v && !/^(https?:|mailto:|\/)/i.test(v)) el.removeAttribute(a.name)
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
    if (el.tagName.toLowerCase() === 'pre') {
      el.classList.add('rounded-xl','overflow-auto','p-4','bg-black/40','text-white')
    }
    if (el.tagName.toLowerCase() === 'code') {
      el.classList.add('rounded','px-1','py-0.5','bg-black/30')
    }
  })
  return doc.body.innerHTML
}

const rawHtml = computed(() => props.html || '')
const safeHtml = computed(() => props.sanitize === false ? rawHtml.value : sanitizeHtml(rawHtml.value))
const highlightedHtml = ref('')
const effectiveHtml = computed(() => highlightedHtml.value || safeHtml.value)

const prefs = ref({ fontFamily: 'sans', fontSize: 18, lineHeight: 1.7 as number, width: 'normal', theme: 'auto' })
const storeKey = 'reader.prefs.v2'
function loadPrefs() { try { const s = localStorage.getItem(storeKey); if (s) Object.assign(prefs.value, JSON.parse(s)) } catch { /* empty */ } }
function savePrefs() { try { localStorage.setItem(storeKey, JSON.stringify(prefs.value)) } catch { /* empty */ } }
watch(prefs, savePrefs, { deep: true })

const widthClass = computed(() => prefs.value.width === 'narrow' ? 'max-w-prose' : prefs.value.width === 'wide' ? 'max-w-4xl' : 'max-w-3xl')
const proseClass = computed(() => prefs.value.theme === 'light' || prefs.value.theme === 'sepia' ? 'prose prose-zinc dark:prose-invert max-w-none prose-img:rounded-xl prose-pre:rounded-xl' : 'prose prose-invert max-w-none prose-img:rounded-xl prose-pre:rounded-xl')
const contentStyle = computed<CSSProperties>(() => ({
  fontFamily: prefs.value.fontFamily === 'serif'
    ? 'ui-serif, Georgia, Cambria, "Times New Roman", Times, serif'
    : 'ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, "Helvetica Neue", Arial, "Noto Sans", "Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol"',
  fontSize: `${Math.max(14, Math.min(26, prefs.value.fontSize))}px`,
  lineHeight: String(prefs.value.lineHeight),
  wordBreak: 'break-word'
}))

const contentThemeClass = computed(() => {
  if (prefs.value.theme === 'light') return 'bg-white text-zinc-900'
  if (prefs.value.theme === 'sepia') return 'bg-amber-50 text-zinc-900'
  return 'text-zinc-100'
})

function textFromHtml(html: string) {
  const doc = new DOMParser().parseFromString(html || '', 'text/html')
  return (doc.body.textContent || '').trim()
}
const dirAttr = computed<'rtl'|'ltr'>(() => {
  const t = textFromHtml(effectiveHtml.value)
  const rtl = (t.match(/[\u0590-\u05FF\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\uFB50-\uFDFF\uFE70-\uFEFF]/gu) || []).length
  const ltr = (t.match(/[A-Za-z]/g) || []).length
  return rtl > ltr ? 'rtl' : 'ltr'
})

const scrollArea = ref<HTMLElement|null>(null)
const contentEl = ref<HTMLElement|null>(null)
const progress = ref(0)
function onScroll() {
  if (!scrollArea.value) return
  const s = scrollArea.value
  const total = s.scrollHeight - s.clientHeight
  progress.value = total > 0 ? Math.min(100, Math.max(0, (s.scrollTop / total) * 100)) : 0
}

const readingTime = computed(() => {
  const tmp = document.createElement('div')
  tmp.innerHTML = safeHtml.value
  const text = tmp.textContent || ''
  const words = text.trim().split(/\s+/).filter(Boolean).length
  return Math.max(1, Math.round(words / 220))
})

const toc = ref<{id:string; text:string}[]>([])
function buildToc() {
  if (!contentEl.value) return
  toc.value = []
  const hs = contentEl.value.querySelectorAll('h1,h2,h3')
  hs.forEach((h, i) => {
    if (!h.id) h.id = 'h-' + i + '-' + Math.random().toString(36).slice(2,7)
    toc.value.push({ id: h.id, text: (h.textContent || '').trim() })
  })
}
function scrollToId(id: string) {
  if (!scrollArea.value || !contentEl.value) return
  const el = contentEl.value.querySelector('#' + CSS.escape(id)) as HTMLElement | null
  if (!el) return
  const top = el.offsetTop - 8
  scrollArea.value.scrollTo({ top, behavior: 'smooth' })
}

const tocOpen = ref(false)
function toggleToc(){ tocOpen.value = !tocOpen.value }

const searchOpen = ref(false)
const searchQuery = ref('')
const hits = ref<HTMLElement[]>([])
const hitsCount = computed(()=> hits.value.length)
const currentHit = ref(0)
function toggleSearch(){ searchOpen.value = !searchOpen.value; if(!searchOpen.value) clearSearch(); else nextTick(()=>focusSearch()) }
function focusSearch(){ const el = document.querySelector('input[placeholder="Search…"]') as HTMLInputElement|null; el?.focus() }
function clearSearch(){ searchQuery.value = ''; highlightedHtml.value = ''; hits.value = []; currentHit.value = 0 }

function escapeRegExp(s: string){ return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') }
function highlight() {
  const q = searchQuery.value.trim()
  if (!q) { highlightedHtml.value = ''; hits.value = []; currentHit.value = 0; return }
  const root = document.createElement('div')
  root.innerHTML = safeHtml.value
  const tw = document.createTreeWalker(root, NodeFilter.SHOW_TEXT)
  const re = new RegExp(escapeRegExp(q), 'gi')
  const found: HTMLElement[] = []
  let n: Node | null
  while ((n = tw.nextNode())) {
    const text = n.nodeValue || ''
    if (!re.test(text)) { re.lastIndex = 0; continue }
    const frag = document.createDocumentFragment()
    let last = 0
    text.replace(re, (m, offset) => {
      const pre = text.slice(last, offset)
      if (pre) frag.appendChild(document.createTextNode(pre))
      const mark = document.createElement('mark')
      mark.className = 'bg-amber-300/60 dark:bg-amber-400/40 text-inherit rounded px-0.5'
      mark.textContent = m
      frag.appendChild(mark)
      found.push(mark)
      last = offset + m.length
      return m
    })
    const tail = text.slice(last)
    if (tail) frag.appendChild(document.createTextNode(tail))
    if (n.parentNode) n.parentNode.replaceChild(frag, n)
    re.lastIndex = 0
  }
  highlightedHtml.value = root.innerHTML
  nextTick(() => {
    if (!contentEl.value) return
    hits.value = Array.from(contentEl.value.querySelectorAll('mark')) as HTMLElement[]
    goTo(0)
  })
}
watch(searchQuery, () => highlight())

function goTo(i: number) {
  if (!scrollArea.value) return
  if (!hits.value.length) return
  currentHit.value = Math.max(0, Math.min(i, hits.value.length - 1))
  const target = hits.value[currentHit.value]
  if (target) target.scrollIntoView({ behavior: 'smooth', block: 'center' })
}
function nextHit(){ if (!hits.value.length) return; goTo((currentHit.value + 1) % hits.value.length) }
function prevHit(){ if (!hits.value.length) return; goTo((currentHit.value - 1 + hits.value.length) % hits.value.length) }

function copy() {
  const tmp = document.createElement('div')
  tmp.innerHTML = safeHtml.value
  const v = tmp.textContent || ''
  if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(v)
  else {
    const ta = document.createElement('textarea'); ta.value = v; ta.style.position='fixed'; ta.style.opacity='0'
    document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta)
  }
}

function printDoc() {
  const w = window.open('', '_blank')
  if (!w) return
  const themeCss = prefs.value.theme === 'sepia' ? 'background:#fff3e0;color:#111' : prefs.value.theme === 'light' ? 'background:#fff;color:#111' : 'background:#0b1220;color:#e5e7eb'
  const htmlDoc = `
  <html><head><meta charset="utf-8"><title>Reader</title>
  <style>
    body{${themeCss};margin:24px;font-family:${contentStyle.value.fontFamily};font-size:${prefs.value.fontSize}px;line-height:${prefs.value.lineHeight}}
    img{max-width:100%;border-radius:8px}
    a{color:#4f46e5}
    pre{background:#0b1220;color:#e5e7eb;border-radius:12px;padding:16px;overflow:auto}
    code{background:rgba(0,0,0,.08);border-radius:8px;padding:2px 4px}
  </style></head>
  <body dir="${dirAttr.value}">${safeHtml.value}</body></html>`
  w.document.write(htmlDoc)
  w.document.close()
  w.focus()
  w.print()
}

function downloadHtml() {
  const blob = new Blob([safeHtml.value], { type: 'text/html' })
  const a = document.createElement('a')
  a.href = URL.createObjectURL(blob)
  a.download = 'post.html'
  a.click()
  URL.revokeObjectURL(a.href)
}
function downloadTxt() {
  const tmp = document.createElement('div')
  tmp.innerHTML = safeHtml.value
  const v = tmp.textContent || ''
  const blob = new Blob([v], { type: 'text/plain;charset=utf-8' })
  const a = document.createElement('a')
  a.href = URL.createObjectURL(blob)
  a.download = 'post.txt'
  a.click()
  URL.revokeObjectURL(a.href)
}

const ttsSupported = typeof window !== 'undefined' && 'speechSynthesis' in window
const ttsState = ref<'idle'|'playing'|'paused'>('idle')
let utter: SpeechSynthesisUtterance | null = null
function ttsToggle() {
  if (!ttsSupported) return
  if (ttsState.value === 'playing') { window.speechSynthesis.pause(); ttsState.value = 'paused'; return }
  if (ttsState.value === 'paused') { window.speechSynthesis.resume(); ttsState.value = 'playing'; return }
  const tmp = document.createElement('div'); tmp.innerHTML = safeHtml.value
  const text = (tmp.textContent || '').trim()
  if (!text) return
  utter = new SpeechSynthesisUtterance(text)
  utter.rate = 1; utter.pitch = 1
  utter.onend = () => { ttsState.value = 'idle' }
  utter.onerror = () => { ttsState.value = 'idle' }
  window.speechSynthesis.cancel()
  window.speechSynthesis.speak(utter)
  ttsState.value = 'playing'
}
function ttsStop() {
  if (!ttsSupported) return
  window.speechSynthesis.cancel()
  ttsState.value = 'idle'
}

function incSize(){ prefs.value.fontSize = Math.min(26, prefs.value.fontSize + 2) }
function decSize(){ prefs.value.fontSize = Math.max(12, prefs.value.fontSize - 2) }

function onKey(e: KeyboardEvent) {
  if (!props.open) return
  if (e.key === 'Escape') { e.preventDefault(); ttsStop(); emit('close') }
  if (e.key === '+' || (e.key === '=' && (e.ctrlKey || e.metaKey))) { e.preventDefault(); incSize() }
  if (e.key === '-' || (e.key === '_' && (e.ctrlKey || e.metaKey))) { e.preventDefault(); decSize() }
  if (e.key.toLowerCase() === 'f' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); if(!searchOpen.value) toggleSearch() }
}

onMounted(() => {
  loadPrefs()
  document.addEventListener('keydown', onKey)
})
onBeforeUnmount(() => {
  document.removeEventListener('keydown', onKey)
  ttsStop()
})
watch(() => props.open, async v => {
  if (!v) return
  highlightedHtml.value = ''
  await nextTick()
  buildToc()
  nextTick(() => { onScroll() })
})

const vSafeHtml: Directive<HTMLElement, string> = {
  mounted(el, binding) { el.innerHTML = binding.value || '' },
  updated(el, binding) { if (binding.value !== binding.oldValue) el.innerHTML = binding.value || '' }
}
</script>

<style scoped>
.fade-enter-active,.fade-leave-active{transition:opacity .15s ease}
.fade-enter-from,.fade-leave-to{opacity:0}
.select-wrap{position:relative;display:inline-flex}
.select{appearance:none;-webkit-appearance:none;-moz-appearance:none}
.select{border-radius:.375rem;border-width:1px;border-color:rgba(255,255,255,.1);background:rgba(255,255,255,.1);padding:.25rem .75rem;font-size:.75rem;line-height:1rem;color:#e5e7eb}
.select:focus{outline:2px solid transparent;outline-offset:2px;box-shadow:0 0 0 2px rgba(99,102,241,.6)}
.select option{background:#0b1220;color:#e5e7eb}
.tool{border-radius:.375rem;padding:.25rem .5rem;font-size:.75rem;line-height:1rem;color:#e5e7eb}
.tool:hover{background:rgba(255,255,255,.1)}
.pill{border-radius:9999px;padding:.375rem}
.focus-ring:focus-within{box-shadow:0 0 0 2px rgba(99,102,241,.6)}
.prose-base :where(h1){font-size:1.5rem;line-height:1.25}
.prose-base :where(h2){font-size:1.25rem;line-height:1.3}
.prose-base :where(h3){font-size:1.125rem;line-height:1.35}
.prose-base :where(p,li){font-size:1rem}
.prose-base :where(ul){list-style:disc;padding-inline-start:1.25rem}
.prose-base :where(ol){list-style:decimal;padding-inline-start:1.25rem}
.prose-base :where(blockquote){border-inline-start:3px solid rgba(255,255,255,.2);padding-inline-start:.75rem;opacity:.9}
.prose-base :where(a){text-decoration:underline;text-underline-offset:3px}
</style>
