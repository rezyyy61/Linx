<template>
  <div class="p-6 max-w-6xl mx-auto space-y-6">
    <header class="flex items-center justify-between">
      <div class="flex items-center gap-2">
        <h1 class="text-2xl font-semibold text-red-600 my-4">
          Posts
        </h1>
      </div>
      <nav class="flex gap-2">
        <RouterLink
          to="/dashboard/posts"
          class="px-3 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-800"
        >
          List
        </RouterLink>
        <RouterLink
          to="/dashboard/posts/create"
          class="px-3 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
        >
          Create
        </RouterLink>
      </nav>
    </header>
    <RouterView />
  </div>
</template>
<script setup lang="ts">
</script>
