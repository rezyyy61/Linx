<script setup lang="ts">
defineProps<{
  capacity?: number
  going: number
  price?: number
}>()
</script>

<template>
  <div class="flex gap-2 flex-wrap text-xs">
    <span class="px-2 py-1 rounded-full bg-neutral-200 dark:bg-neutral-700 text-neutral-700 dark:text-neutral-300">
      {{ going }} going
    </span>
    <span
      v-if="capacity"
      class="px-2 py-1 rounded-full bg-neutral-200 dark:bg-neutral-700 text-neutral-700 dark:text-neutral-300"
    >
      Capacity: {{ capacity }}
    </span>
    <span class="px-2 py-1 rounded-full bg-neutral-200 dark:bg-neutral-700 text-neutral-700 dark:text-neutral-300">
      {{ price && price > 0 ? price + ' €' : 'Free' }}
    </span>
  </div>
</template>
