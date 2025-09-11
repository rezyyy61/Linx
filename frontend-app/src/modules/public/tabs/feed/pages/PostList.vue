<template>
  <div class="space-y-4">
    <PostCard
      v-for="p in posts"
      :key="p.id"
      :post="p"
    />
  </div>
</template>

<script setup lang="ts">

import PostCard from "@/modules/public/postCard/PostCard.vue";
import mock from '@/modules/public/postCard/mocks/posts.mock'
import type { Post } from '@/modules/public/postCard/types/post.types'

const posts = mock as Post[]
</script>
