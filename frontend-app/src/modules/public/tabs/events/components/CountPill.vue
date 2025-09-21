<script setup lang="ts">
defineProps<{ icon: string; text: string }>()
</script>

<template>
  <div class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs bg-neutral-100 dark:bg-neutral-800 text-neutral-700 dark:text-neutral-300">
    <iconify-icon
      :icon="icon"
      class="w-4 h-4"
    />
    <span>{{ text }}</span>
  </div>
</template>

<script lang="ts">
import { Icon as IconifyIcon } from '@iconify/vue'
export default { components: { 'iconify-icon': IconifyIcon } }
</script>
