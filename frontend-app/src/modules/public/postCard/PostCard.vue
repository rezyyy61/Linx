<template>
  <article
    ref="root"
    class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm transition hover:shadow-md focus-within:ring-2 focus-within:ring-indigo-500 dark:border-zinc-800 dark:bg-zinc-900"
    tabindex="0"
  >
    <PostCardHeader
      :avatar="post.author.avatarUrl ?? undefined"
      :name="post.author.name"
      :username="post.author.username"
      :created-at="post.createdAt"
      :edited-at="post.editedAt ?? undefined"
      :is-pinned="!!post.isPinned"
      :verified="post.author.verified || false"
      :is-owner="false"
      @copy="() => {}"
      @share="onShare"
      @edit="() => {}"
      @delete="() => {}"
      @report="() => {}"
      @pin-toggle="() => {}"
    />

    <PostText
      :html="post.text || ''"
      :parse-entities="true"
      :treat-as-html="false"
      :clamp-lines="6"
      :reader-threshold-lines="24"
    />
    <section
      v-if="post.media?.length"
      class="mb-3"
    >
      <MediaRenderer
        :items="post.media || []"
        :gid="post.id"
      />
    </section>

    <PostCardFooter
      :likes="counts[post.id]?.likes ?? post.counts.likes"
      :comments="counts[post.id]?.comments ?? post.counts.comments"
      :shares="counts[post.id]?.shares ?? post.counts.shares"
      :saves="counts[post.id]?.saves ?? post.counts.saves"
      :views="counts[post.id]?.views ?? post.counts.views ?? 0"
      :liked="liked[post.id] || false"
      :saved="saved[post.id] || false"
      @like="onLike"
      @comment="onComment"
      @share="onShare"
      @save="onSave"
    />

    <SharePanel
      :open="isShareOpen"
      :url="shareUrl"
      :title="post.author.name"
      :text="post.text || ''"
      @close="isShareOpen=false"
      @shared="onShared"
    />


    <transition name="ic-slide">
      <InlineComments
        v-if="isCommentsOpen"
        :post-id="post.id"
        @added="onCommentAddedInline"
      />
    </transition>
  </article>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type { Post } from './types/post.types'
import { usePostActions } from './composables/usePostActions'
import { useViewCounter } from './composables/useViewCounter'
import MediaRenderer from './content/media/MediaRenderer.vue'
import PostCardFooter from "@/modules/public/postCard/footer/PostCardFooter.vue";
import InlineComments from "@/modules/public/postCard/footer/comments/InlineComments.vue";
import SharePanel from "@/modules/public/postCard/footer/share/SharePanel.vue";
import PostCardHeader from "@/modules/public/postCard/header/PostCardHeader.vue";
import PostText from "@/modules/public/postCard/content/text/PostText.vue";

const props = defineProps<{ post: Post }>()

const { liked, saved, counts, ensure, toggleLike, toggleSave, addShare, addView, addComment } = usePostActions()
ensure(props.post)

function onLike() { toggleLike(props.post) }
function onSave() { toggleSave(props.post) }
const isShareOpen = ref(false)
function onShare() { isShareOpen.value = true }
function onShared() { addShare(props.post); isShareOpen.value = false }

const shareUrl = computed(() => {
  const base = typeof window !== 'undefined' ? window.location.origin : ''
  return `${base}/p/${props.post.id}`
})


const isCommentsOpen = ref(false)
function onComment() { isCommentsOpen.value = !isCommentsOpen.value }
function onCommentAddedInline() { addComment(props.post, 1) }

const { el: root, hasCounted } = useViewCounter()
watch(hasCounted, v => { if (v) addView(props.post) })
</script>

<style scoped>
.ic-slide-enter-active,.ic-slide-leave-active{transition:all .18s ease}
.ic-slide-enter-from{opacity:0; transform:translateY(-4px)}
.ic-slide-leave-to{opacity:0; transform:translateY(-4px)}
</style>
