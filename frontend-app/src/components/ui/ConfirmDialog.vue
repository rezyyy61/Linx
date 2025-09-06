<template>
  <teleport to="body">
    <transition name="cd-fade">
      <div
        v-if="open"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4"
        @keydown.esc="$emit('cancel')"
      >
        <div
          class="absolute inset-0 bg-black/50"
          @click="$emit('cancel')"
        />
        <div
          class="relative w-full max-w-sm rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white/90 dark:bg-zinc-900/90 backdrop-blur shadow-2xl"
          role="dialog"
          aria-modal="true"
        >
          <header class="px-4 pt-4 pb-2">
            <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
              {{ title }}
            </h3>
          </header>

          <div class="px-4 pb-4 text-sm text-zinc-600 dark:text-zinc-300">
            <p>{{ message }}</p>
          </div>

          <div class="px-4 pb-4 flex items-center justify-end gap-2">
            <button
              class="px-3 py-2 rounded-lg text-sm bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-900 dark:text-zinc-100 transition"
              @click="$emit('cancel')"
            >
              {{ cancelText }}
            </button>

            <button
              :class="confirmClass"
              @click="$emit('confirm')"
            >
              {{ confirmText }}
            </button>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(defineProps<{
  open?: boolean
  title?: string
  message?: string
  cancelText?: string
  confirmText?: string
  variant?: 'danger' | 'primary'
}>(), {
  open: false,
  title: 'Are you sure?',
  message: 'This action cannot be undone.',
  cancelText: 'Cancel',
  confirmText: 'Confirm',
  variant: 'danger'
})

defineEmits<{ (e:'cancel'):void; (e:'confirm'):void }>()

const confirmClass = computed(() =>
  props.variant === 'danger'
    ? 'px-3 py-2 rounded-lg text-sm bg-rose-600 hover:bg-rose-500 text-white transition'
    : 'px-3 py-2 rounded-lg text-sm bg-indigo-600 hover:bg-indigo-500 text-white transition'
)
</script>

<style scoped>
.cd-fade-enter-from,.cd-fade-leave-to{opacity:0;transform:translateY(6px) scale(.98)}
.cd-fade-enter-active,.cd-fade-leave-active{transition:.16s ease}
</style>
