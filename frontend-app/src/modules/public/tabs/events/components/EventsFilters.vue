<script setup lang="ts">
const STATUSES = ['all','upcoming','ongoing','past'] as const
type Status = (typeof STATUSES)[number]

const query = defineModel<string>('query')
const status = defineModel<Status>('status')
</script>

<template>
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
    <input
      v-model="query"
      type="text"
      placeholder="Search events..."
      class="w-full md:w-1/3 rounded-xl border border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
    >

    <div class="flex gap-2 text-sm">
      <button
        v-for="s in STATUSES"
        :key="s"
        :class="[
          'px-3 py-1 rounded-full',
          status === s ? 'bg-indigo-600 text-white' : 'bg-neutral-200 dark:bg-neutral-700 text-neutral-700 dark:text-neutral-300'
        ]"
        @click="status = s"
      >
        {{ s }}
      </button>
    </div>
  </div>
</template>
