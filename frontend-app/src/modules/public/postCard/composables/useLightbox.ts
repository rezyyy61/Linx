import { ref, computed, onMounted, onBeforeUnmount } from 'vue'

export type LightboxItem =
  | { id: string; type: 'image'; url: string; alt?: string }
  | { id: string; type: 'video'; url: string; poster?: string }

const isOpen = ref(false)
const groupId = ref<string | null>(null)
const items = ref<LightboxItem[]>([])
const index = ref(0)

function lockScroll() {
  const w = window.innerWidth - document.documentElement.clientWidth
  document.body.style.overflow = 'hidden'
  if (w > 0) document.body.style.paddingRight = `${w}px`
}
function unlockScroll() {
  document.body.style.overflow = ''
  document.body.style.paddingRight = ''
}

export function useLightbox() {
  function open(gid: string, its: LightboxItem[], startIndex = 0) {
    groupId.value = gid
    items.value = its
    index.value = Math.max(0, Math.min(startIndex, its.length - 1))
    isOpen.value = true
    lockScroll()
  }
  function close() {
    isOpen.value = false
    items.value = []
    index.value = 0
    groupId.value = null
    unlockScroll()
  }
  function next() {
    if (!items.value.length) return
    index.value = (index.value + 1) % items.value.length
  }
  function prev() {
    if (!items.value.length) return
    index.value = (index.value - 1 + items.value.length) % items.value.length
  }
  const current = computed(() => items.value[index.value])

  function onKey(e: KeyboardEvent) {
    if (!isOpen.value) return
    if (e.key === 'Escape') close()
    if (e.key === 'ArrowRight') next()
    if (e.key === 'ArrowLeft') prev()
  }

  onMounted(() => window.addEventListener('keydown', onKey))
  onBeforeUnmount(() => window.removeEventListener('keydown', onKey))

  return { isOpen, items, index, current, open, close, next, prev }
}
