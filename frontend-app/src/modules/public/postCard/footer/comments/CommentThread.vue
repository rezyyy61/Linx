<template>
  <CommentItem
    :item="item"
    :is-mine="item.author.id===me().id"
    :children="children"
    :replying="isReplying"
    @reply="toggleReply"
    @like="onLike"
    @delete="onDelete"
    @report="onReport"
    @edit="onEdit"
  >
    <template #composer>
      <CommentComposer
        :initial-text="`@${item.author.username} `"
        :replying-to="`@${item.author.username}`"
        :avatar="meAvatar"
        compact
        placeholder="Write a reply…"
        @submit="onReplySubmit"
        @cancel="toggleReply()"
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
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useComments, type Comment, type CommentSort } from '@/modules/public/postCard/composables/useComments'
import CommentItem from "@/modules/public/postCard/footer/comments/CommentItem.vue";
import CommentComposer from "@/modules/public/postCard/footer/comments/CommentComposer.vue";

const props = defineProps<{ postId: string; item: Comment; sortBy: CommentSort }>()
const emit = defineEmits<{ (e:'added'): void }>()

const { replies, sort, like, update, remove, reply, me } = useComments()
const children = computed(() => sort(replies(props.postId, props.item.id), props.sortBy))

const isReplying = ref(false)
function toggleReply() { isReplying.value = !isReplying.value }

function onLike(c: Comment) { like(c.id, props.postId, 1) }
function onDelete(c: Comment) { remove(props.postId, c.id) }
function onReport(_c: Comment) { alert('Reported') } // underscore برای ساکت‌کردن eslint
function onEdit(payload: { id: string; text: string }) { update(props.postId, payload.id, payload.text) }

function onReplySubmit(text: string) {
  reply(props.postId, props.item.id, text)
  isReplying.value = false
  emit('added')
}

const meAvatar = 'https://i.pravatar.cc/80?u=me'
</script>
