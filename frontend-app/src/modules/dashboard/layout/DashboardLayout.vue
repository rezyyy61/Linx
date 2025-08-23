<template>
  <div class="min-h-screen w-full text-gray-900 dark:text-slate-100 dark:bg-slate-900  transition-colors">
    <div class="flex">
      <SidebarPage class="bg-white/80 backdrop-blur-md border-r border-slate-200/60 dark:bg-gray-800/80 dark:border-slate-800" />
      <div class="flex-1 flex flex-col">
        <PublicNavbar class="bg-white/80 backdrop-blur-md border-b border-slate-200/60 dark:bg-zinc-900 dark:border-slate-800" />
        <div class="p-4 md:p-6">
          <main class="w-full mx-auto py-8 ">
            <router-view />
          </main>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import SidebarPage from "../components/SidebarPage.vue";
import PublicNavbar from "@/components/shared/PublicNavbar.vue";
</script>
