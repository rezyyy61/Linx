<template>
  <div class="space-y-2">
    <div
      v-for="m in items"
      :key="m.id"
      class="p-3 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/70 dark:bg-zinc-800/50 text-sm text-zinc-800 dark:text-zinc-100 flex items-start justify-between"
    >
      <div class="pr-3">
        <p class="font-medium">
          {{ m.from }}
        </p>
        <p class="text-zinc-500 dark:text-zinc-400 truncate max-w-[240px]">
          {{ m.preview }}
        </p>
      </div>
      <span class="text-xs text-zinc-400">{{ m.time }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{ items: Array<any> }>()
</script>
