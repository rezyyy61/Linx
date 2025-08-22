<script setup lang="ts">
defineOptions({ name: 'UiNotifications' })
import { Icon } from '@iconify/vue'
import { useNotify } from '../../lib/notify/useNotify'
import type { ToastType } from '../../lib/notify/types'

const { state, remove, _arm, _clear } = useNotify()

function iconFor(type: ToastType) {
  return type === 'success' ? 'mdi:check-circle-outline'
    : type === 'error' ? 'mdi:alert-circle-outline'
      : type === 'warning' ? 'mdi:alert-outline'
        : 'mdi:information-outline'
}

function tone(type: ToastType) {
  switch (type) {
    case 'success':
      return {
        text: 'text-emerald-700 dark:text-emerald-300',
        chip: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
        ring: 'ring-emerald-200/70 dark:ring-emerald-900/40',
        bar: 'bg-emerald-500'
      }
    case 'error':
      return {
        text: 'text-rose-700 dark:text-rose-300',
        chip: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
        ring: 'ring-rose-200/70 dark:ring-rose-900/40',
        bar: 'bg-rose-500'
      }
    case 'warning':
      return {
        text: 'text-amber-700 dark:text-amber-300',
        chip: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
        ring: 'ring-amber-200/70 dark:ring-amber-900/40',
        bar: 'bg-amber-500'
      }
    default:
      return {
        text: 'text-sky-700 dark:text-sky-300',
        chip: 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300',
        ring: 'ring-sky-200/70 dark:ring-sky-900/40',
        bar: 'bg-sky-500'
      }
  }
}
</script>

<template>
  <teleport to="body">
    <div
      dir="rtl"
      class="fixed top-5 left-5 z-[10000] space-y-4"
      aria-live="polite"
      aria-atomic="false"
    >
      <TransitionGroup
        name="toast"
        tag="div"
      >
        <div
          v-for="t in state.toasts"
          :key="t.id"
          class="relative pointer-events-auto w-[520px] sm:w-[460px] rounded-3xl backdrop-blur-md
                 bg-white/95 dark:bg-zinc-900/90 shadow-xl ring-1 p-4 sm:p-5
                 flex items-start gap-4 text-right"
          :class="tone(t.type).ring"
          role="status"
          @mouseenter="_clear(t.id)"
          @mouseleave="_arm(t)"
        >
          <div class="shrink-0">
            <div
              class="inline-flex h-9 w-9 items-center justify-center rounded-full"
              :class="tone(t.type).chip"
            >
              <Icon
                :icon="iconFor(t.type)"
                class="h-5 w-5"
              />
            </div>
          </div>

          <div class="flex-1 min-w-0">
            <p
              v-if="t.title"
              class="font-medium text-lg"
              :class="tone(t.type).text"
            >
              {{ t.title }}
            </p>
            <p
              v-if="t.description"
              class="mt-1 text-[15px] leading-relaxed text-zinc-700 dark:text-zinc-300 break-words"
            >
              {{ t.description }}
            </p>

            <div
              v-if="t.action"
              class="mt-3"
            >
              <button
                type="button"
                class="inline-flex h-9 items-center gap-2 rounded-lg px-3 text-[13px] font-medium
           text-white shadow-sm ring-1 ring-indigo-600/20
           bg-gradient-to-r from-indigo-600 to-violet-600
           hover:from-indigo-600/90 hover:to-violet-600/90
           focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500
           focus-visible:ring-offset-2 focus-visible:ring-offset-white
           dark:focus-visible:ring-offset-zinc-900
           active:scale-[0.99]"
                @click="t.action.onClick()"
              >
                <Icon
                  icon="mdi:email-send-outline"
                  class="h-4 w-4"
                />
                <span>{{ t.action.label }}</span>
              </button>
            </div>
            <div
              v-if="t.duration"
              class="mt-3 h-1 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-800"
            >
              <div
                class="h-full animate-[toastProgress_var(--d)_linear]"
                :class="tone(t.type).bar"
                :style="{ '--d': `${t.duration}ms` }"
              />
            </div>
          </div>

          <button
            type="button"
            aria-label="بستن"
            title="بستن"
            class="absolute top-3 left-3 opacity-60 hover:opacity-100 transition"
            @click="remove(t.id)"
          >
            <Icon
              icon="mdi:close"
              class="h-5 w-5"
            />
          </button>
        </div>
      </TransitionGroup>
    </div>
  </teleport>
</template>

<style scoped>
.toast-enter-from,.toast-leave-to{ opacity:0; transform: translateY(-6px) scale(.98) }
.toast-enter-active,.toast-leave-active{ transition: all .18s ease }
@keyframes toastProgress { from{ transform: translateX(-100%) } to{ transform: translateX(0) } }
</style>
