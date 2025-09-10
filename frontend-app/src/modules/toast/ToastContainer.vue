<template>
  <Teleport to="body">
    <TransitionGroup
      tag="div"
      class="fixed z-[1000] right-3 md:right-4 top-[calc(env(safe-area-inset-top)+3.5rem)] md:top-16 w-[92vw] max-w-sm space-y-3 pointer-events-none"
      enter-active-class="transform transition duration-200"
      enter-from-class="opacity-0 translate-y-2"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transform transition duration-200"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 translate-y-2"
    >
      <div
        v-for="t in normal"
        :key="t.id"
        class="relative pointer-events-auto rounded-xl border shadow-lg p-3 pr-10 pl-3.5 flex items-start gap-3 bg-white/95 dark:bg-zinc-900/90 border-zinc-200/70 dark:border-white/10"
        :role="t.type==='error' ? 'alert' : 'status'"
        :aria-live="t.type==='error' ? 'assertive' : 'polite'"
      >
        <div
          class="absolute inset-y-0 left-0 w-1 rounded-l-xl"
          :class="tone(t.type).bar"
        />
        <div :class="['w-9 h-9 rounded-lg flex items-center justify-center shrink-0', tone(t.type).bg]">
          <Icon
            :icon="tone(t.type).icon"
            width="18"
            height="18"
            :class="tone(t.type).fg"
          />
        </div>
        <div class="min-w-0 flex-1">
          <p
            v-if="t.title"
            class="text-sm font-semibold text-zinc-900 dark:text-zinc-50 truncate"
          >
            {{ t.title }}
          </p>
          <p class="text-sm text-zinc-700 dark:text-zinc-200 whitespace-pre-line break-words">
            {{ t.message }}
          </p>
          <button
            v-if="t.actionLabel"
            class="mt-2 text-xs px-2 py-1 rounded-md border border-zinc-300/50 dark:border-white/15 hover:bg-zinc-50/70 dark:hover:bg-white/5"
            @click="onAction(t.id)"
          >
            {{ t.actionLabel }}
          </button>
        </div>
        <button
          class="absolute top-2.5 right-2.5 p-1.5 rounded-md hover:bg-zinc-100 dark:hover:bg-white/10"
          @click="remove(t.id)"
        >
          <Icon
            icon="mdi:close"
            width="16"
            height="16"
          />
        </button>
        <div
          v-if="t.duration && t.duration > 0"
          class="absolute left-3 right-10 bottom-2 h-0.5 rounded bg-zinc-200/60 dark:bg-white/10 overflow-hidden"
        >
          <div
            class="h-full rounded toast-progress"
            :class="tone(t.type).progress"
            :style="progressStyle(t.duration)"
          />
        </div>
      </div>
    </TransitionGroup>

    <TransitionGroup
      tag="div"
      class="fixed inset-0 z-[1001] pointer-events-none"
      enter-active-class="transform transition duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transform transition duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-for="c in confirms"
        :key="c.id"
        class="pointer-events-auto fixed inset-0 flex items-center justify-center p-3 md:p-6"
        role="dialog"
        aria-modal="true"
      >
        <div
          class="absolute inset-0 bg-black/40 backdrop-blur-sm"
          @click="onCancel(c.id)"
        />
        <div
          class="relative w-full h-auto max-w-lg rounded-2xl border shadow-2xl p-4 md:p-5 bg-white/98 dark:bg-zinc-900/95"
          :class="c.destructive ? 'border-rose-300/60 dark:border-rose-400/30' : 'border-indigo-300/60 dark:border-indigo-400/30'"
        >
          <button
            class="absolute top-3 right-3 p-1.5 rounded-md hover:bg-zinc-100 dark:hover:bg-white/10"
            @click="onCancel(c.id)"
          >
            <Icon
              icon="mdi:close"
              width="18"
              height="18"
            />
          </button>

          <div class="flex items-start gap-3">
            <div
              class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0"
              :class="c.destructive ? 'bg-rose-50 dark:bg-rose-400/10' : 'bg-indigo-50 dark:bg-indigo-400/10'"
            >
              <Icon
                :icon="c.destructive ? 'mdi:alert' : 'mdi:help-circle-outline'"
                width="20"
                height="20"
                :class="c.destructive ? 'text-rose-700 dark:text-rose-200' : 'text-indigo-700 dark:text-indigo-200'"
              />
            </div>
            <div class="min-w-0 flex-1">
              <p
                v-if="c.title"
                class="text-base font-semibold text-zinc-900 dark:text-zinc-50"
              >
                {{ c.title }}
              </p>
              <p class="mt-0.5 text-sm text-zinc-700 dark:text-zinc-200 whitespace-pre-line break-words">
                {{ c.message }}
              </p>
            </div>
          </div>

          <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-end gap-2">
            <button
              class="px-3 py-2 rounded-lg border text-sm"
              :class="c.destructive
                ? 'border-rose-300/60 dark:border-rose-400/30 bg-rose-50/80 dark:bg-rose-400/10 text-rose-700 dark:text-rose-200'
                : 'border-indigo-300/60 dark:border-indigo-400/30 bg-indigo-50/80 dark:bg-indigo-400/10 text-indigo-700 dark:text-indigo-200'"
              @click="onConfirm(c.id)"
            >
              {{ c.confirmLabel || 'Confirm' }}
            </button>
            <button
              class="px-3 py-2 rounded-lg border text-sm border-zinc-300/60 dark:border-white/15 text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50/70 dark:hover:bg-white/5"
              @click="onCancel(c.id)"
            >
              {{ c.cancelLabel || 'Cancel' }}
            </button>
          </div>
        </div>
      </div>
    </TransitionGroup>
  </Teleport>
</template>

<script setup lang="ts">
import { computed } from "vue"
import { Icon } from "@iconify/vue"
import { storeToRefs } from "pinia"
import { useToastStore } from "./store"
import type { ToastType } from "./types"

const store = useToastStore()
const { items } = storeToRefs(store)

const normal = computed(() => items.value.filter(x => x.type !== "confirm"))
const confirms = computed(() => items.value.filter(x => x.type === "confirm"))

function tone(type: ToastType) {
  if (type === "success") return { bg: "bg-emerald-50 dark:bg-emerald-400/10", fg: "text-emerald-700 dark:text-emerald-200", icon: "mdi:check-circle", bar: "bg-emerald-400/80 dark:bg-emerald-400/70", progress: "bg-emerald-500/80 dark:bg-emerald-400/90" }
  if (type === "warning") return { bg: "bg-amber-50 dark:bg-amber-400/10", fg: "text-amber-700 dark:text-amber-200", icon: "mdi:alert-outline", bar: "bg-amber-400/80 dark:bg-amber-400/70", progress: "bg-amber-500/80 dark:bg-amber-400/90" }
  if (type === "error") return { bg: "bg-rose-50 dark:bg-rose-400/10", fg: "text-rose-700 dark:text-rose-200", icon: "mdi:close-circle", bar: "bg-rose-400/80 dark:bg-rose-400/70", progress: "bg-rose-500/80 dark:bg-rose-400/90" }
  return { bg: "bg-indigo-50 dark:bg-indigo-400/10", fg: "text-indigo-700 dark:text-indigo-200", icon: "mdi:information-outline", bar: "bg-indigo-400/80 dark:bg-indigo-400/70", progress: "bg-indigo-500/80 dark:bg-indigo-400/90" }
}

function remove(id: number) { store.remove(id) }
function onAction(id: number) {
  const t = store.items.find(x => x.id === id)
  if (!t) return
  t.onAction?.()
  store.remove(id)
}
function onConfirm(id: number) {
  const t = store.items.find(x => x.id === id)
  if (!t) return
  Promise.resolve(t.onConfirm?.()).finally(() => store.remove(id))
}
function onCancel(id: number) {
  const t = store.items.find(x => x.id === id)
  if (!t) return
  t.onCancel?.()
  store.remove(id)
}
function progressStyle(ms?: number) { return { animationDuration: `${ms ?? 0}ms` } }
</script>

<style scoped>
@keyframes toastShrink { from { width: 100%; } to { width: 0%; } }
.toast-progress { animation-name: toastShrink; animation-timing-function: linear; animation-fill-mode: forwards; }
</style>
