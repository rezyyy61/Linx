<template>
  <div class="space-y-4">
    <PostList :items="items" />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import PostList from '@/modules/dashboard/pages/posts/components/PostList.vue'

type MediaItem = { id: number; url?: string; type?: string; order?: number }
type Post = { id: number; content: string; visibility: string; status: string; media: MediaItem[] }

const items = ref<Post[]>([
  { id: 1, content: 'Hello world', visibility: 'public', status: 'published', media: [] },
  { id: 2, content: 'Second post', visibility: 'friends', status: 'draft', media: [] }
])
</script>
