<template>
  <Transition name="fade">
    <div
      v-if="message"
      :class="klass"
      role="status"
      aria-live="polite"
    >
      <slot name="icon" />
      <span>{{ message }}</span>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import {computed} from "vue";

defineOptions({ name: 'UiFormAlert' })
const props = withDefaults(defineProps<{ message?: string | null; tone?: 'info'|'danger'|'warning' }>(), { tone: 'info' })
const klass = computed(() => {
  if (props.tone === 'danger') return 'mt-4 rounded-md bg-red-50 p-3 text-sm text-red-800 ring-1 ring-inset ring-red-200 dark:bg-red-950/30 dark:text-red-200 dark:ring-red-900 flex items-start gap-2'
  if (props.tone === 'warning') return 'mt-4 rounded-md bg-amber-50 p-3 text-sm text-amber-800 ring-1 ring-inset ring-amber-200 dark:bg-amber-950/30 dark:text-amber-200 dark:ring-amber-900 flex items-start gap-2'
  return 'mt-4 rounded-md bg-blue-50 p-3 text-sm text-blue-800 ring-1 ring-inset ring-blue-200 dark:bg-blue-950/40 dark:text-blue-200 dark:ring-blue-900 flex items-start gap-2'
})
</script>
