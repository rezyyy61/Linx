<template>
  <div class="relative">
    <article
      class="rounded-xl border border-zinc-200 bg-white p-3 overflow-hidden hover:shadow-sm transition ring-0 focus-within:ring-2 focus-within:ring-indigo-500 dark:border-zinc-800 dark:bg-zinc-800"
      tabindex="0"
    >
      <PostCardHeader
        :avatar="post.author?.avatarUrl || undefined"
        :color="post.author?.avatarColor || undefined"
        :name="post.author?.name || ''"
        :username="post.author?.username || ''"
        :created-at="post.createdAt"
        :edited-at="post.editedAt || undefined"
        :is-pinned="!!post.isPinned"
        :verified="!!post.author?.verified"
        :is-owner="false"
        :follow-state="'none'"
        @toggle-follow="() => {}"
        @copy="() => {}"
        @share="() => {}"
        @edit="() => {}"
        @delete="() => {}"
        @report="() => {}"
        @pin-toggle="() => {}"
      />
      <PostText
        :html="post.text || ''"
        :parse-entities="true"
        :treat-as-html="true"
        :clamp-lines="6"
        :reader-threshold-lines="24"
      />
      <section
        v-if="post.media?.length"
        class="-mx-3 mt-2"
      >
        <MediaRenderer
          :items="post.media || []"
          :gid="String(post.id)"
          :bleed="true"
        />
      </section>
    </article>

    <RouterLink
      v-if="to"
      :to="to"
      class="absolute inset-0 rounded-xl ring-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
      :aria-label="aria"
    />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import PostCardHeader from '@/modules/public/postCard/header/PostCardHeader.vue'
import PostText from '@/modules/public/postCard/content/text/PostText.vue'
import MediaRenderer from '@/modules/public/postCard/content/media/MediaRenderer.vue'

const props = defineProps<{ post: any; to?: string | null }>()

const to = computed(() => props.to || null)
const aria = computed(() => {
  const u = props.post?.author?.username ? ` @${props.post.author.username}` : ''
  return `Open original post by${u}`
})
</script>
