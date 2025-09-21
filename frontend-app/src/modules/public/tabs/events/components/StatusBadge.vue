<script setup lang="ts">
defineProps<{ kind: 'live' | 'upcoming' | 'past' }>()
</script>

<template>
  <div
    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-medium"
    :class="{
      'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300': kind==='live',
      'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300': kind==='upcoming',
      'bg-neutral-200 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300': kind==='past'
    }"
  >
    <iconify-icon
      :icon="kind==='live' ? 'mdi:circle' : kind==='upcoming' ? 'mdi:clock-outline' : 'mdi:history'"
      class="w-3 h-3"
    />
    <span>{{ kind==='live' ? 'Live' : kind==='upcoming' ? 'Upcoming' : 'Past' }}</span>
  </div>
</template>

<script lang="ts">
import { Icon as IconifyIcon } from '@iconify/vue'
export default { components: { 'iconify-icon': IconifyIcon } }
</script>
