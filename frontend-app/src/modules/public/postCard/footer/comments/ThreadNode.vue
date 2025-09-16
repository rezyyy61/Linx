<template>
  <div class="relative">
    <span
      v-if="showConnector"
      class="pointer-events-none absolute left-[22px] top-8 bottom-2 w-px bg-zinc-200 dark:bg-zinc-700"
    />

    <CommentItem
      :item="comment"
      :is-mine="isMine(comment)"
      :replying="showComposer"
      :is-child="isChild"
      :post-id="postId"
      @reply="openComposer"
      @like="onLike"
      @delete="onDelete"
      @report="onReport"
      @edit="onEdit"
      @show-likers="openLikers"
    >
      <template #composer>
        <CommentComposer
          :logged-in="loggedIn"
          :avatar-url="avatarUrl"
          :avatar-color="avatarColor"
          compact
          placeholder="Write a reply…"
          :initial-text="initialReplyText"
          :replying-to="replyLabel"
          @submit="onReplySubmit"
          @cancel="closeComposer"
        />
      </template>

      <template #children>
        <div
          v-if="expanded && children.length"
          class="space-y-3"
        >
          <ThreadNode
            v-for="child in children"
            :key="child.id"
            :comment="child"
            :post-id="postId"
            :logged-in="loggedIn"
            :avatar-url="avatarUrl"
            :avatar-color="avatarColor"
            :is-child="true"
            @added="emit('added')"
          />
        </div>

        <button
          v-else-if="!expanded && childCount>0"
          class="ml-10 inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800"
          @click="expand"
        >
          <svg
            viewBox="0 0 24 24"
            class="h-4 w-4"
          ><path
            fill="currentColor"
            d="M9 6l6 6-6 6"
          /></svg>
          View {{ childCount }} repl<span v-if="childCount>1">ies</span>
        </button>
      </template>
    </CommentItem>

    <LikersModal
      v-if="likersOpen"
      :open="likersOpen"
      :post-id="postId"
      :comment-id="comment.id"
      @close="likersOpen=false"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, inject, ref } from "vue"
import CommentItem from "./CommentItem.vue"
import CommentComposer from "./CommentComposer.vue"
import LikersModal from "./LikersModal.vue"
import ThreadNode from "./ThreadNode.vue"
import type { Comment } from "@/modules/public/postCard/types/comment.types"
import { useAuthStore } from "@/stores/auth/auth"

const { comment, postId, loggedIn = false, avatarUrl = null, avatarColor = null, isChild = false } = defineProps<{
  comment: Comment
  postId: number | string
  loggedIn?: boolean
  avatarUrl?: string | null
  avatarColor?: string | null
  isChild?: boolean
}>()

const emit = defineEmits<{ (e: "added"): void }>()

const auth = useAuthStore()
const ctx = inject<any>("commentCtx")

const showComposer = ref(false)
const expanded = ref(false)
const likersOpen = ref(false)

const children = computed<Comment[]>(() => ctx.getChildren(comment.id))
const childCount = computed(() => comment.repliesCount || 0)
const showConnector = computed(() => childCount.value > 0 || showComposer.value || expanded.value)

function isMine(c: Comment) { return auth.user?.id === c.user?.id }

const replyLabel = computed(() => {
  const slug = comment.user?.slug || comment.user?.name || "user"
  return `@${String(slug).replace(/\s+/g, "")}`
})
const initialReplyText = computed(() => `${replyLabel.value} `)

function openComposer() { showComposer.value = true }
function closeComposer() { showComposer.value = false }

async function onReplySubmit(text: string) {
  await ctx.submit(text, comment.id)
  expanded.value = true
  await ctx.fetchChildren(comment.id)
  showComposer.value = false
  emit("added")
}

async function expand() {
  expanded.value = true
  await ctx.fetchChildren(comment.id)
}

async function onLike(c: Comment) { await ctx.toggleLikeOn(c.id) }
async function onDelete(c: Comment) { await ctx.remove(c.id) }
function onReport() {}
async function onEdit(payload: { id: number; body: string }) { await ctx.edit(payload.id, payload.body) }
function openLikers() { likersOpen.value = true }
</script>

