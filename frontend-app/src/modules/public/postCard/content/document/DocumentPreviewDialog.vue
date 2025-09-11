<template>
  <teleport to="body">
    <transition name="fade">
      <div
        v-if="open"
        class="fixed inset-0 z-[95]"
      >
        <div
          class="absolute inset-0 bg-black/60 backdrop-blur-sm"
          @click="$emit('close')"
        />
        <div class="absolute inset-0 flex items-center justify-center p-4">
          <div class="w-[96vw] sm:w-[90vw] lg:w-[80vw] max-w-[90rem] overflow-hidden rounded-2xl border border-white/10 bg-slate-900/70 shadow-[inset_0_1px_0_rgba(255,255,255,.06)] backdrop-blur-sm">
            <div class="flex items-center justify-between gap-3 px-4 py-3">
              <div class="truncate text-sm font-semibold text-zinc-100">
                {{ filename }}
              </div>
              <button
                class="rounded-lg p-2 text-zinc-200 hover:bg-white/10"
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
            <div class="h-[78vh] w-full bg-black/30">
              <iframe
                :src="src"
                class="h-full w-full"
                title="Document"
              />
            </div>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup lang="ts">
defineProps<{ open: boolean; src: string; filename?: string }>()
defineEmits<{ (e:'close'): void }>()
</script>

<style scoped>
.fade-enter-active,.fade-leave-active{transition:opacity .15s ease}
.fade-enter-from,.fade-leave-to{opacity:0}
</style>
