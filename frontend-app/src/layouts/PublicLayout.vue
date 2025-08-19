<script setup lang="ts">
import PublicNavbar from "@/components/shared/PublicNavbar.vue"
</script>

<template>
  <div class="min-h-screen flex flex-col bg-gray-50 dark:bg-zinc-800">
    <PublicNavbar />
    <main class="container mx-auto max-w-8xl px-4 py-8 flex-1">
      <RouterView />
    </main>
  </div>
</template>
