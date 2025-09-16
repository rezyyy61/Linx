<!-- src/modules/public/postCard/pages/PostPage.vue -->
<template>
  <div class="mx-auto max-w-2xl p-4">
    <PostCard
      v-if="post"
      :post="post"
    />
    <div
      v-else
      class="text-center text-zinc-500 py-10"
    >
      Loading…
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import PostCard from '@/modules/public/postCard/PostCard.vue'
import * as postsApi from '@/modules/public/postCard/api/posts'

// props از route props میاد چون توی route props: true گذاشتیم
const props = defineProps<{ id: number; comment?: number | null }>()

const post = ref<any|null>(null)

onMounted(async () => {
  post.value = await postsApi.get(String(props.id))
})
</script>
