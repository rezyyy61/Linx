<template>
  <article class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
    <div class="mb-3 flex items-start gap-3">
      <div class="h-10 w-10 animate-pulse rounded-full bg-zinc-200 dark:bg-zinc-800" />
      <div class="flex-1 space-y-2">
        <div class="h-3 w-40 animate-pulse rounded bg-zinc-200 dark:bg-zinc-800" />
        <div class="h-3 w-24 animate-pulse rounded bg-zinc-200 dark:bg-zinc-800" />
      </div>
      <div class="h-5 w-5 animate-pulse rounded bg-zinc-200 dark:bg-zinc-800" />
    </div>
    <div class="mb-3 space-y-2">
      <div class="h-3 w-full animate-pulse rounded bg-zinc-200 dark:bg-zinc-800" />
      <div class="h-3 w-3/4 animate-pulse rounded bg-zinc-200 dark:bg-zinc-800" />
    </div>
    <MediaSkeleton />
    <div class="mt-3 flex items-center gap-5">
      <div class="h-4 w-10 animate-pulse rounded bg-zinc-200 dark:bg-zinc-800" />
      <div class="h-4 w-10 animate-pulse rounded bg-zinc-200 dark:bg-zinc-800" />
      <div class="h-4 w-10 animate-pulse rounded bg-zinc-200 dark:bg-zinc-800" />
    </div>
  </article>
</template>

<script setup lang="ts">
import MediaSkeleton from './MediaSkeleton.vue'
</script>
