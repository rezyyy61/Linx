<script setup lang="ts">
const model = defineModel<'draft' | 'published' | 'archived'>({ required: true })
</script>

<template>
  <div class="w-full">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
    <div class="inline-flex overflow-hidden rounded-xl border border-gray-300 dark:border-gray-600">
      <button
        type="button"
        class="px-3 py-2 text-sm"
        :class="model === 'draft' ? 'bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
        @click="model = 'draft'"
      >
        Draft
      </button>
      <button
        type="button"
        class="px-3 py-2 text-sm border-l border-gray-300 dark:border-gray-600"
        :class="model === 'published' ? 'bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
        @click="model = 'published'"
      >
        Published
      </button>
      <button
        type="button"
        class="px-3 py-2 text-sm border-l border-gray-300 dark:border-gray-600"
        :class="model === 'archived' ? 'bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
        @click="model = 'archived'"
      >
        Archived
      </button>
    </div>
  </div>
</template>
