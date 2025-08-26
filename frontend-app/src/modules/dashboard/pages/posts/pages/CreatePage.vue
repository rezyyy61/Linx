<template>
  <section class="space-y-6">
    <div class="flex items-center gap-2">
      <Icon
        icon="mdi:plus-circle-outline"
        class="w-6 h-6 text-gray-700 dark:text-gray-200"
      />
      <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
        Create a Post
      </h2>
    </div>
    <PostComposerForm @submit="onSubmit" />
  </section>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { useRouter } from 'vue-router'
import PostComposerForm from '@/modules/dashboard/pages/posts/components/PostComposerForm.vue'
import type { Post } from '@/stores/post/post'

const router = useRouter()
function onSubmit(_post: Post) {
  router.back()
}
</script>
