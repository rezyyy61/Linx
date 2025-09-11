<template>
  <div
    ref="root"
    class="relative"
  >
    <button
      class="rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:text-zinc-300 dark:hover:bg-white/10"
      aria-label="More"
      @click="open=!open"
    >
      <svg
        viewBox="0 0 24 24"
        class="h-5 w-5"
        fill="currentColor"
      ><circle
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
      /></svg>
    </button>
    <transition name="fade">
      <div
        v-if="open"
        class="absolute right-0 z-20 mt-2 w-44 overflow-hidden rounded-xl border border-white/10 bg-slate-900/80 p-1 text-sm text-zinc-100 shadow-lg backdrop-blur-sm"
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
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'
const {isOwner} = defineProps<{ isOwner?: boolean }>()
const emit = defineEmits<{ (e: 'edit' | 'delete' | 'copy' | 'report' | 'share'): void }>()
const open = ref(false)
const root = ref<HTMLElement|null>(null)
function onDocClick(e: MouseEvent){ if(open.value && root.value && !root.value.contains(e.target as Node)) open.value=false }
onMounted(()=>document.addEventListener('click', onDocClick))
onBeforeUnmount(()=>document.removeEventListener('click', onDocClick))
function emitClose(t:'edit'|'delete'|'copy'|'report'|'share'){ open.value=false; emit(t) }
</script>

<style scoped>
.fade-enter-active,.fade-leave-active{transition:opacity .12s ease}
.fade-enter-from,.fade-leave-to{opacity:0}
</style>
