<template>
  <div
    ref="root"
    class="relative"
  >
    <button
      ref="btn"
      class="rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:text-zinc-300 dark:hover:bg-white/10"
      aria-label="More"
      @click="toggle"
    >
      <svg
        viewBox="0 0 24 24"
        class="h-5 w-5"
        fill="currentColor"
      >
        <circle
          cx="5"
          cy="12"
          r="1.5"
        /><circle
          cx="12"
          cy="12"
          r="1.5"
        /><circle
          cx="19"
          cy="12"
          r="1.5"
        />
      </svg>
    </button>

    <teleport to="body">
      <transition name="fade">
        <div
          v-if="open"
          class="fixed z-[1000] w-44 overflow-hidden rounded-xl border border-white/10 bg-slate-900/80 p-1 text-sm text-zinc-100 shadow-lg backdrop-blur-sm"
          :style="{ top: y+'px', left: x+'px' }"
        >
          <button
            v-if="isOwner"
            class="flex w-full items-center gap-2 rounded-lg px-3 py-2 hover:bg-white/10"
            @click="emitClose('edit')"
          >
            Edit
          </button>
          <button
            v-if="isOwner"
            class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-rose-300 hover:bg-rose-500/10"
            @click="emitClose('delete')"
          >
            Delete
          </button>
          <div class="my-1 h-px bg-white/10" />
          <button
            class="flex w-full items-center gap-2 rounded-lg px-3 py-2 hover:bg-white/10"
            @click="emitClose('copy')"
          >
            Copy link
          </button>
          <button
            class="flex w-full items-center gap-2 rounded-lg px-3 py-2 hover:bg-white/10"
            @click="emitClose('share')"
          >
            Share
          </button>
          <button
            class="flex w-full items-center gap-2 rounded-lg px-3 py-2 hover:bg-white/10"
            @click="emitClose('report')"
          >
            Report
          </button>
        </div>
      </transition>
    </teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, toRefs } from 'vue'

const _props = withDefaults(defineProps<{ isOwner?: boolean }>(), { isOwner: false })
const { isOwner } = toRefs(_props)

const emit = defineEmits<{ (e: 'edit' | 'delete' | 'copy' | 'report' | 'share'): void }>()

const open = ref(false)
const root = ref<HTMLElement|null>(null)
const btn = ref<HTMLElement|null>(null)
const x = ref(0)
const y = ref(0)

function place() {
  const el = btn.value
  if (!el) return
  const r = el.getBoundingClientRect()
  const menuW = 176
  const gap = 8
  x.value = Math.max(8, Math.min(window.innerWidth - menuW - 8, r.right - menuW))
  y.value = r.bottom + gap
}

function toggle() {
  open.value = !open.value
  if (open.value) {
    place()
    setTimeout(() => document.addEventListener('click', onDocClick), 0)
    window.addEventListener('scroll', onScroll, true)
    window.addEventListener('resize', place)
  } else {
    cleanupListeners()
  }
}

function onDocClick(e: MouseEvent){
  const target = e.target as Node
  if (!open.value) return
  if (root.value?.contains(target)) return
  open.value = false
  cleanupListeners()
}

function onScroll() { if (open.value) place() }

function cleanupListeners() {
  document.removeEventListener('click', onDocClick)
  window.removeEventListener('scroll', onScroll, true)
  window.removeEventListener('resize', place)
}

onMounted(() => {})
onBeforeUnmount(() => cleanupListeners())

function emitClose(t:'edit'|'delete'|'copy'|'report'|'share'){
  open.value = false
  cleanupListeners()
  emit(t)
}
</script>

<style scoped>
.fade-enter-active,.fade-leave-active{transition:opacity .12s ease}
.fade-enter-from,.fade-leave-to{opacity:0}
</style>
