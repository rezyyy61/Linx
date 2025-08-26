<script setup lang="ts">
const model = defineModel<'public' | 'friends' | 'private'>({ required: true })
</script>

<template>
  <div class="w-full">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Visibility</label>
    <div class="inline-flex overflow-hidden rounded-xl border border-gray-300 dark:border-gray-600">
      <button
        type="button"
        class="px-3 py-2 text-sm flex items-center gap-2"
        :class="model === 'public' ? 'bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
        @click="model = 'public'"
      >
        <span
          class="iconify w-4 h-4"
          data-icon="mdi:earth"
        />
        <span>Public</span>
      </button>
      <button
        type="button"
        class="px-3 py-2 text-sm flex items-center gap-2 border-l border-gray-300 dark:border-gray-600"
        :class="model === 'friends' ? 'bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
        @click="model = 'friends'"
      >
        <span
          class="iconify w-4 h-4"
          data-icon="mdi:account-multiple"
        />
        <span>Friends</span>
      </button>
      <button
        type="button"
        class="px-3 py-2 text-sm flex items-center gap-2 border-l border-gray-300 dark:border-gray-600"
        :class="model === 'private' ? 'bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
        @click="model = 'private'"
      >
        <span
          class="iconify w-4 h-4"
          data-icon="mdi:lock"
        />
        <span>Private</span>
      </button>
    </div>
  </div>
</template>
