<template>
  <article
    ref="root"
    class="rounded-2xl border border-zinc-200 bg-white p-4 overflow-hidden shadow-sm transition hover:shadow-md focus-within:ring-2 focus-within:ring-indigo-500 dark:border-zinc-800 dark:bg-zinc-800"
    tabindex="0"
  >
    <PostCardHeader
      :avatar="post.author.avatarUrl ?? undefined"
      :color="post.author.avatarColor ?? undefined"
      :name="post.author.name"
      :username="post.author.username"
      :created-at="post.createdAt"
      :edited-at="post.editedAt ?? undefined"
      :is-pinned="!!post.isPinned"
      :verified="post.author.verified || false"
      :is-owner="isOwner"
      :follow-state="followState"
      @toggle-follow="onToggleFollow"
      @copy="() => {}"
      @share="onShare"
      @edit="onEdit"
      @delete="onDelete"
      @report="() => {}"
      @pin-toggle="() => {}"
    />

    <RepostBadge
      v-if="originalId"
      :original-id="originalId"
      :username="originalUser"
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
      class=" mt-2 mb-3"
    >
      <MediaRenderer
        :items="post.media || []"
        :gid="String(post.id)"
        :bleed="true"
      />
    </section>

    <div
      v-if="origLoading"
      class="mt-3"
    >
      <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 p-3 animate-pulse">
        <div class="h-4 w-40 rounded bg-zinc-200 dark:bg-zinc-700 mb-3" />
        <div class="h-3 w-full rounded bg-zinc-200 dark:bg-zinc-700 mb-2" />
        <div class="h-3 w-5/6 rounded bg-zinc-200 dark:bg-zinc-700" />
      </div>
    </div>

    <div
      v-else-if="original"
      class="mt-3"
    >
      <PostPreviewCompact
        :post="original"
        :to="originalTo"
      />
    </div>

    <LikesPreview :post-id="post.id" />

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

    <ShareModal
      :open="isShareOpen"
      :shareable-alias="'post'"
      :shareable-id="Number(post.id)"
      :title="post.author.name"
      :text="post.text || ''"
      :post="post"
      @close="isShareOpen=false"
      @shared="onShared"
    />

    <PublicPostComposer
      :open="isComposerOpen"
      mode="edit"
      :initial="composerInitial"
      @close="isComposerOpen=false"
    />

    <transition name="ic-slide">
      <InlineComments
        v-if="isCommentsOpen"
        ref="commentsRef"
        :post-id="post.id"
        @added="onCommentAddedInline"
      />
    </transition>
  </article>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import type { Post } from './types/post.types'
import { usePostActions } from './composables/usePostActions'
import { useViewCounter } from './composables/useViewCounter'
import MediaRenderer from './content/media/MediaRenderer.vue'
import PostCardFooter from '@/modules/public/postCard/footer/PostCardFooter.vue'
import InlineComments from '@/modules/public/postCard/footer/comments/InlineComments.vue'
import PostCardHeader from '@/modules/public/postCard/header/PostCardHeader.vue'
import PostText from '@/modules/public/postCard/content/text/PostText.vue'
import LikesPreview from '@/modules/public/postCard/footer/likes/LikesPreview.vue'
import { useAuthStore } from '@/stores/auth/auth'
import { useRoute } from 'vue-router'
import { useFollowStore } from '@/stores/follow'
import { ShareModal } from '@/modules/share'
import PostPreviewCompact from '@/modules/share/components/PostPreviewCompact.vue'
import RepostBadge from '@/modules/share/components/RepostBadge.vue'
import * as postsApi from '@/modules/public/postCard/api/posts'
import { usePostStore } from '@/stores/post/Post'
import PublicPostComposer from '@/modules/public/postCard/createPost/PublicPostComposer.vue'
import { useToast } from '@/modules/toast/useToast'

const props = defineProps<{ post: Post }>()

const { liked, saved, counts, ensure, toggleLike, toggleSave, addShare, addView, addComment } = usePostActions()
ensure(props.post)

const auth = useAuthStore()
const isOwner = computed(() => Number(props.post.author.id) === Number(auth.user?.id))

const follow = useFollowStore()
const isFollowing = computed(() => follow.isConnected(props.post.author.id))
const isPending = computed(() => follow.isPendingWith(props.post.author.id))
const followState = computed<'none' | 'pending' | 'following'>(() => (isFollowing.value ? 'following' : isPending.value ? 'pending' : 'none'))

async function onToggleFollow() {
  if (isFollowing.value) await follow.unfollow(props.post.author.id)
  else if (isPending.value) await follow.cancelRequest(props.post.author.id)
  else await follow.sendFollow(props.post.author.id)
}

const route = useRoute()
const commentsRef = ref<InstanceType<typeof InlineComments> | null>(null)
function onLike() { toggleLike(props.post) }
function onSave() { toggleSave(props.post) }
const isShareOpen = ref(false)
function onShare() { isShareOpen.value = true }
function onShared() { addShare(props.post); isShareOpen.value = false }

const isCommentsOpen = ref(false)
function onComment() { isCommentsOpen.value = !isCommentsOpen.value }
function onCommentAddedInline() { addComment(props.post, 1) }

const { el: root, hasCounted } = useViewCounter()
watch(hasCounted, v => { if (v) addView(props.post) })

const original = ref<any | null>(null)
const origLoading = ref(false)

const originalId = computed<number | null>(() => {
  const p: any = props.post as any
  if (p?.original && p.original.id) return Number(p.original.id)
  const id = p.repostOfId ?? p.repost_of_id ?? p.originalId ?? p.original_id ?? null
  return id ? Number(id) : null
})

const originalUser = computed<string | undefined>(() => {
  const u = (original.value?.author?.username) || (props as any).post?.original?.author?.username
  return typeof u === 'string' ? u : undefined
})

const originalTo = computed(() => (originalId.value ? `/p/${originalId.value}` : '#'))

async function loadOriginal() {
  if (!originalId.value) return
  if (original.value) return
  origLoading.value = true
  try {
    if ((props as any).post?.original) {
      original.value = (props as any).post.original
    } else {
      const data = await postsApi.get(String(originalId.value))
      original.value = data
    }
  } catch {
    original.value = null
  } finally {
    origLoading.value = false
  }
}

onMounted(async () => {
  try {
    if (auth.user) {
      if (!follow.followers.length) await follow.loadFollowers()
      if (!follow.followings.length) await follow.loadFollowings()
      await follow.loadOutgoingRequests()
    }
  } catch { /* empty */ }
  const q = route.query.comment
  const hash = (typeof window !== 'undefined' ? window.location.hash : '') || ''
  const hashId = hash.startsWith('#c-') ? Number(hash.slice(3)) : null
  if (q || hashId) {
    isCommentsOpen.value = true
    await nextTick()
    const target = Number(q || hashId)
    commentsRef.value?.reveal(target)
  }
  if (originalId.value) await loadOriginal()
})

watch(() => route.query.comment, async (val) => {
  if (!val) return
  isCommentsOpen.value = true
  await nextTick()
  commentsRef.value?.reveal(Number(val))
})

watch(originalId, async (v) => {
  if (v && !original.value) await loadOriginal()
})

const isComposerOpen = ref(false)
const composerInitial = ref<any | null>(null)
function normalizeInitial(p: any) {
  const media = Array.isArray(p?.media)
    ? p.media.map((m: any, i: number) => ({
      id: Number(m?.id ?? i),
      url: m?.url ?? m?.public_url ?? m?.original_url ?? m?.preview_url ?? null,
      mime_type: String(m?.mime_type ?? m?.mime ?? m?.type ?? '').toLowerCase() || undefined,
      width: m?.width ?? null,
      height: m?.height ?? null,
      order: typeof m?.order === 'number' ? m.order : i,
    }))
    : []
  return {
    id: Number(p?.id),
    content: p?.text ?? p?.content ?? '',
    visibility: p?.visibility ?? 'public',
    published_at: p?.publishedAt ?? p?.published_at ?? null,
    media,
  }
}
function onEdit() {
  if (!isOwner.value) return
  composerInitial.value = normalizeInitial(props.post as any)
  isComposerOpen.value = true
}

const postStore = usePostStore()
const toast = useToast()

function onDelete() {
  if (!isOwner.value) return

  toast.confirm(
    'Delete this post?',
    {
      confirmLabel: 'Delete',
      cancelLabel: 'Cancel',
      destructive: true,
      onConfirm: async () => {
        try {
          await postStore.remove(Number((props.post as any).id))
          toast.success('The post was removed.')
        } catch (e: any) {
          const msg = e?.message || 'Failed to delete post.'
          toast.error(msg)
        }
      },
    }
  )
}
</script>

<style scoped>
.ic-slide-enter-active,.ic-slide-leave-active{transition:all .18s ease}
.ic-slide-enter-from{opacity:0; transform:translateY(-4px)}
.ic-slide-leave-to{opacity:0; transform:translateY(-4px)}
</style>
