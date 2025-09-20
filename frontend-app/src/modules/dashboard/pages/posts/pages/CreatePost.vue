<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import type { Post } from '@/stores/post/Post'
import PostForm from "@/modules/dashboard/pages/posts/components/createPost/PostForm.vue";

const router = useRouter()
const errMsg = ref<string | null>(null)
const okMsg = ref<string | null>(null)

function onSaved(_p: Post) {
  okMsg.value = 'Post saved'
  errMsg.value = null
  router.push('/dashboard/posts')
}

function onPublished(_p: Post) {
  okMsg.value = 'Post published'
  errMsg.value = null
  router.push('/dashboard/posts')
}

function onDraftSaved(_p: Post) {
  okMsg.value = 'Post saved as draft'
  errMsg.value = null
  router.push('/dashboard/posts')
}

function onError(e: { status:number; message:string }) {
  errMsg.value = e.message || 'Error'
  okMsg.value = null
}
</script>

<template>
  <div class="max-w-4xl mx-auto p-6 space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-semibold">
        Create Post
      </h1>
    </div>

    <div
      v-if="errMsg"
      class="p-3 rounded-lg bg-red-50 text-red-700 text-sm"
    >
      {{ errMsg }}
    </div>
    <div
      v-if="okMsg"
      class="p-3 rounded-lg bg-green-50 text-green-700 text-sm"
    >
      {{ okMsg }}
    </div>

    <PostForm
      mode="create"
      @saved="onSaved"
      @published="onPublished"
      @draft_saved="onDraftSaved"
      @error="onError"
    />
  </div>
</template>
