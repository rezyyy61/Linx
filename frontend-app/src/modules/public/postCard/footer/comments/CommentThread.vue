<template>
  <CommentItem
    :item="item"
    :is-mine="item.author.id===me().id"
    :children="children"
    :replying="isReplying"
    :post-id="postId"
    @reply="toggleReply"
    @like="onLike"
    @delete="onDelete"
    @report="onReport"
    @edit="onEdit"
    @show-likers="openLikers"
  >
    <template #composer>
      <CommentComposer
        :initial-text="`@${item.author.username} `"
        :replying-to="`@${item.author.username}`"
        :logged-in="loggedIn"
        :avatar-url="avatarUrl"
        :avatar-color="avatarColor"
        compact
        placeholder="Write a reply…"
        @submit="onReplySubmit"
        @cancel="toggleReply()"
        @login="goLogin"
      />
    </template>

    <template #children>
      <div class="space-y-3">
        <CommentThread
          v-for="child in children"
          :key="child.id"
          :post-id="postId"
          :item="child"
          :sort-by="sortBy"
          @added="$emit('added')"
        />
      </div>
    </template>
  </CommentItem>

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
import CommentItem from "./CommentItem.vue";
import CommentComposer from "./CommentComposer.vue";
import LikersModal from './LikersModal.vue'
import { useAuthStore } from '@/stores/auth/auth'
import { useProfileStore } from '@/stores/profile/profile'
import { useToast } from '@/modules/toast/useToast'

const props = defineProps<{ postId: string; item: Comment; sortBy: CommentSort }>()
const emit = defineEmits<{ (e:'added'): void }>()

const { replies, sort, like, update, remove, reply, me } = useComments()
const children = computed(() => sort(replies(props.postId, props.item.id), props.sortBy))
const toast = useToast()

const auth = useAuthStore()
const profile = useProfileStore()
const loggedIn = computed(() => !!auth.user)
const avatarUrl = computed<string | null>(() => profile.meLite?.avatar ?? null)
const avatarColor = computed<string | null>(() => profile.meLite?.avatar_color ?? null)

const isReplying = ref(false)
function toggleReply() { isReplying.value = !isReplying.value }

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

async function onReplySubmit(text: string) {
  await reply(props.postId, props.item.id, text)
  isReplying.value = false
  emit('added')
}

const likersOpen = ref(false)
const likersCommentId = ref<string | null>(null)
function openLikers(c: Comment) { likersCommentId.value = c.id; likersOpen.value = true }
function closeLikers() { likersOpen.value = false; likersCommentId.value = null }

function goLogin() { window.location.href = '/login' }
</script>
