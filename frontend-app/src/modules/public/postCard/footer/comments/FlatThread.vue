<template>
  <CommentItem
    :item="root"
    :is-mine="root.author.id===me().id"
    :replying="replyToId===root.id"
    :is-child="false"
    :post-id="postId"
    @reply="startReply"
    @like="onLike"
    @delete="onDelete"
    @report="onReport"
    @edit="onEdit"
    @show-likers="openLikers"
  >
    <template #composer>
      <CommentComposer
        :initial-text="`@${root.author.username} `"
        :replying-to="`@${root.author.username}`"
        :logged-in="loggedIn"
        :avatar-url="avatarUrl"
        :avatar-color="avatarColor"
        compact
        placeholder="Write a reply…"
        @submit="onReplySubmit"
        @cancel="cancelReply"
        @login="goLogin"
      />
    </template>
  </CommentItem>

  <div
    v-if="flatReplies.length"
    class="mt-3 ml-10 border-l border-zinc-200 pl-3 dark:border-zinc-800"
  >
    <div class="space-y-3">
      <div
        v-for="r in flatReplies"
        :key="r.id"
      >
        <CommentItem
          :item="r"
          :is-mine="r.author.id===me().id"
          :replying="replyToId===r.id"
          :is-child="true"
          :post-id="postId"
          @reply="startReply"
          @like="onLike"
          @delete="onDelete"
          @report="onReport"
          @edit="onEdit"
          @show-likers="openLikers"
        >
          <template #composer>
            <CommentComposer
              :initial-text="`@${r.author.username} `"
              :replying-to="`@${r.author.username}`"
              :logged-in="loggedIn"
              :avatar-url="avatarUrl"
              :avatar-color="avatarColor"
              compact
              placeholder="Write a reply…"
              @submit="onReplySubmit"
              @cancel="cancelReply"
              @login="goLogin"
            />
          </template>
        </CommentItem>
      </div>
    </div>
  </div>

  <LikersModal
    v-if="likersCommentId"
    :key="postId + ':' + likersCommentId"
    :open="likersOpen"
    :post-id="postId"
    :comment-id="likersCommentId"
    @close="closeLikers"
  />
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useComments, type CommentSort } from '@/modules/public/postCard/composables/useComments'
import type { Comment } from '@/modules/public/postCard/types/comment.types'
import CommentItem from './CommentItem.vue'
import CommentComposer from './CommentComposer.vue'
import LikersModal from './LikersModal.vue'
import { useAuthStore } from '@/stores/auth/auth'
import { useProfileStore } from '@/stores/profile/profile'
import { useToast } from '@/modules/toast/useToast'

const props = defineProps<{ postId: string; root: Comment; sortBy: CommentSort }>()
const emit = defineEmits<{ (e:'added'): void }>()
const { replies, sort, like, update, remove, reply, me } = useComments()
const toast = useToast()

function collectAll(id: string): Comment[] {
  const direct = sort(replies(props.postId, id), props.sortBy)
  const acc: Comment[] = []
  for (const c of direct) {
    acc.push(c)
    const rest = collectAll(c.id)
    if (rest.length) acc.push(...rest)
  }
  return acc
}

const flatReplies = computed(() => collectAll(props.root.id))

const auth = useAuthStore()
const profile = useProfileStore()
const loggedIn = computed(() => !!auth.user)
const avatarUrl = computed<string | null>(() => profile.meLite?.avatar ?? null)
const avatarColor = computed<string | null>(() => profile.meLite?.avatar_color ?? null)

const replyToId = ref<string | null>(null)
function startReply(c: Comment) { replyToId.value = c.id }
function cancelReply() { replyToId.value = null }

async function onReplySubmit(text: string) {
  if (!replyToId.value) return
  await reply(props.postId, replyToId.value, text)
  replyToId.value = null
  emit('added')
}

function onLike(c: Comment) { like(c.id, props.postId) }

function onDelete(c: Comment) {
  toast.confirm('Delete this comment?', {
    confirmLabel: 'Delete',
    cancelLabel: 'Cancel',
    destructive: true,
    onConfirm: async () => {
      await remove(props.postId, c.id)
      toast.success('Comment deleted')
    },
  })
}
function onReport(_c: Comment) { alert('Reported') }
function onEdit(payload: { id: string; text: string }) { update(props.postId, payload.id, payload.text) }

const likersOpen = ref(false)
const likersCommentId = ref<string | null>(null)
function openLikers(c: Comment) { likersCommentId.value = c.id; likersOpen.value = true }
function closeLikers() { likersOpen.value = false; likersCommentId.value = null }

function goLogin() { window.location.href = '/login' }
</script>
