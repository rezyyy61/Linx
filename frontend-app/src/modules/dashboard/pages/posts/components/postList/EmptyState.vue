<template>
  <div class="rounded-2xl border border-dashed border-gray-300 p-10 text-center bg-white dark:bg-gray-900 dark:border-gray-700">
    <div class="flex flex-col items-center gap-3">
      <Icon
        :icon="icon"
        class="w-10 h-10 text-gray-400"
      />
      <div class="text-gray-700 dark:text-gray-200 font-medium">
        {{ title }}
      </div>
      <div class="text-sm text-gray-500 dark:text-gray-400">
        {{ subtitle }}
      </div>
      <slot name="action" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'
defineProps<{ icon: string; title: string; subtitle?: string }>()
</script>
