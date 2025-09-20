<template>
  <div class="grid grid-cols-12 gap-6 p-4">
    <!-- Left Sidebar -->
    <aside class="col-span-3">
      <LeftSidebar />
    </aside>

    <!-- Main Feed -->
    <main class="col-span-6 space-y-6">
      <PostList />
      <LightboxRoot />
    </main>

    <!-- Right Sidebar -->
    <aside class="col-span-3">
      <RightSidebar />
    </aside>
  </div>
</template>

<script setup lang="ts">
import LeftSidebar from './pages/LeftSidebar.vue'
import RightSidebar from './pages/RightSidebar.vue'
import PostList from './pages/PostList.vue'
import LightboxRoot from "@/modules/public/postCard/lightbox/LightboxRoot.vue";
</script>
