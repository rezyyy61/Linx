<script setup lang="ts">
import PostCard from './PostCard.vue'

type MediaItem = {
  id: number
  url?: string
  mime_type?: string
  width?: number | null
  height?: number | null
}

type Post = {
  id: number
  user_id: number
  content: string | null
  visibility: 'public'|'private'|'friends'
  status: 'draft'|'published'|'archived'
  published_at: string | null
  created_at?: string
  updated_at?: string
  media: MediaItem[]
}

defineProps<{
  posts: Post[]
  clickable?: boolean
}>()

</script>

<template>
  <div class="grid auto-rows-fr grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 items-stretch">
    <PostCard
      v-for="p in posts"
      :key="p.id"
      :post="p"
      :clickable="clickable"
      class="h-full"
    />
  </div>
</template>
