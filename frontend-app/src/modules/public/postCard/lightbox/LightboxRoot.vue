<template>
  <teleport to="body">
    <transition name="lb-fade">
      <div
        v-if="isOpen"
        class="fixed inset-0 z-[100] select-none"
        @touchstart.passive="onStart"
        @touchmove.prevent="onMove"
        @touchend.passive="onEnd"
      >
        <div
          class="absolute inset-0 bg-black/80"
          @click="close"
        />

        <button
          class="fixed z-[110] rounded-full p-2 text-white/90 bg-black/30 hover:bg-black/40 backdrop-blur-sm"
          :style="safeBtnStyle"
          aria-label="Close"
          @click="close"
        >
          <svg
            viewBox="0 0 24 24"
            class="h-6 w-6"
            fill="none"
            stroke="currentColor"
            stroke-width="1.5"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>

        <button
          v-if="items.length>1"
          class="absolute left-2 top-1/2 z-[110] -translate-y-1/2 rounded-lg p-2 text-white/90 hover:bg-white/10"
          aria-label="Prev"
          @click="prev"
        >
          <svg
            viewBox="0 0 24 24"
            class="h-7 w-7"
            fill="none"
            stroke="currentColor"
            stroke-width="1.5"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M15 19l-7-7 7-7"
            />
          </svg>
        </button>
        <button
          v-if="items.length>1"
          class="absolute right-2 top-1/2 z-[110] -translate-y-1/2 rounded-lg p-2 text-white/90 hover:bg-white/10"
          aria-label="Next"
          @click="next"
        >
          <svg
            viewBox="0 0 24 24"
            class="h-7 w-7"
            fill="none"
            stroke="currentColor"
            stroke-width="1.5"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M9 5l7 7-7 7"
            />
          </svg>
        </button>

        <div class="absolute inset-0 flex items-center justify-center p-4">
          <div class="relative w-full max-w-5xl">
            <div
              class="overflow-hidden rounded-2xl bg-black/40 shadow-2xl"
              role="dialog"
              aria-modal="true"
            >
              <div
                v-if="current?.type==='image'"
                class="relative w-full"
              >
                <img
                  :src="current.url"
                  :alt="current.alt || ''"
                  class="mx-auto max-h-[80vh] w-auto object-contain"
                  draggable="false"
                >
              </div>
              <div
                v-else-if="current?.type==='video'"
                class="relative w-full"
              >
                <video
                  class="mx-auto max-h-[80vh] w-auto"
                  :poster="current.poster"
                  controls
                  playsinline
                  preload="metadata"
                  controlslist="nodownload"
                >
                  <source
                    :src="current.url"
                    type="video/mp4"
                  >
                </video>
              </div>
            </div>
            <div class="mt-3 text-center text-xs text-white/70">
              <span>{{ index+1 }}</span>/<span>{{ items.length }}</span>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useLightbox } from '../composables/useLightbox'
const { isOpen, items, index, current, close, next, prev } = useLightbox()

const startX = ref(0)
const startY = ref(0)
const dx = ref(0)
const dy = ref(0)

function onStart(e: TouchEvent) {
  startX.value = e.touches[0].clientX
  startY.value = e.touches[0].clientY
  dx.value = 0
  dy.value = 0
}
function onMove(e: TouchEvent) {
  dx.value = e.touches[0].clientX - startX.value
  dy.value = e.touches[0].clientY - startY.value
}
function onEnd() {
  if (Math.abs(dx.value) > 60 && Math.abs(dx.value) > Math.abs(dy.value)) {
    if (dx.value < 0) next(); else prev()
  } else if (dy.value > 80) {
    close()
  }
  startX.value = 0; startY.value = 0; dx.value = 0; dy.value = 0
}

const safeBtnStyle = computed(() => ({
  top: 'max(0.75rem, env(safe-area-inset-top))',
  right: 'max(0.75rem, env(safe-area-inset-right))'
}))
</script>

<style scoped>
.lb-fade-enter-active,.lb-fade-leave-active{transition:opacity .18s ease}
.lb-fade-enter-from,.lb-fade-leave-to{opacity:0}
</style>
