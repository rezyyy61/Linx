<template>
  <section class="space-y-6">
    <div class="flex items-center gap-2">
      <Icon
        icon="mdi:file-document-edit-outline"
        class="w-6 h-6 text-gray-700 dark:text-gray-200"
      />
      <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
        Edit Post
      </h2>
      <span class="text-sm text-gray-500 dark:text-gray-400">#{{ id }}</span>
    </div>

    <div
      v-if="loading"
      class="rounded-2xl border border-gray-200 bg-white dark:bg-gray-900 dark:border-gray-700 p-6"
    >
      <div class="flex items-center gap-3">
        <Icon
          icon="mdi:loading"
          class="w-5 h-5 animate-spin text-emerald-600"
        />
        <div class="text-sm text-gray-700 dark:text-gray-300">
          Loading post…
        </div>
      </div>
    </div>

    <!-- نکته: PostForm فقط وقتی mount می‌شود که post آماده است -->
    <PostForm
      v-else-if="post"
      :initial="post"
      mode="edit"
      @saved="onSaved"
      @published="onSaved"
      @error="onError"
    />
  </section>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { usePostStore, type Post } from '@/stores/post/post'
import PostForm from '@/modules/dashboard/pages/posts/components/createPost/PostForm.vue'

const route = useRoute()
const router = useRouter()
const store = usePostStore()

const id = Number(route.params.id)
const loading = ref(false)
const post = ref<Post | null>(null)

async function load() {
  loading.value = true
  try {
    post.value = await store.fetchOne(id)
  } finally {
    loading.value = false
  }
}

function onSaved(p: Post) {
  router.push(`/dashboard/posts/${p.id}`)
}

function onError(err: { status:number; message:string }) {
  // این‌جا اگر خواستی نوتیفیکیشن یا toast بزن
  console.error('edit error', err)
}

onMounted(load)
</script>
