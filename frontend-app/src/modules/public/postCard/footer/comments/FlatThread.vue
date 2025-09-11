<template>
  <CommentItem
    :item="root"
    :is-mine="root.author.id===me().id"
    :children="flatReplies"
    :replying="replyToId===root.id"
    @reply="startReply"
    @like="onLike"
    @delete="onDelete"
    @report="onReport"
    @edit="onEdit"
  >
    <template #composer>
      <CommentComposer
        :initial-text="`@${root.author.username} `"
        :replying-to="`@${root.author.username}`"
        :avatar="meAvatar"
        compact
        placeholder="Write a reply…"
        @submit="onReplySubmit"
        @cancel="cancelReply"
      />
    </template>

    <template #children>
      <div class="space-y-3">
        <div
          v-for="r in flatReplies"
          :key="r.id"
        >
          <CommentItem
            :item="r"
            :is-mine="r.author.id===me().id"
            :replying="replyToId===r.id"
            @reply="startReply"
            @like="onLike"
            @delete="onDelete"
            @report="onReport"
            @edit="onEdit"
          >
            <template #composer>
              <CommentComposer
                :initial-text="`@${r.author.username} `"
                :replying-to="`@${r.author.username}`"
                :avatar="meAvatar"
                compact
                placeholder="Write a reply…"
                @submit="onReplySubmit"
                @cancel="cancelReply"
              />
            </template>
          </CommentItem>
        </div>
      </div>
    </template>
  </CommentItem>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useComments, type Comment, type CommentSort } from '@/modules/public/postCard/composables/useComments'
import CommentItem from './CommentItem.vue'
import CommentComposer from './CommentComposer.vue'

const props = defineProps<{ postId: string; root: Comment; sortBy: CommentSort }>()
const emit = defineEmits<{ (e:'added'): void }>()
const { replies, sort, like, update, remove, reply, me } = useComments()

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
const meAvatar = 'https://i.pravatar.cc/80?u=me'

const replyToId = ref<string | null>(null)
function startReply(c: Comment) { replyToId.value = c.id }
function cancelReply() { replyToId.value = null }

function onReplySubmit(text: string) {
  if (!replyToId.value) return
  reply(props.postId, replyToId.value, text)
  replyToId.value = null
  emit('added')
}

function onLike(c: Comment) { like(c.id, props.postId, 1) }
function onDelete(c: Comment) { remove(props.postId, c.id) }
function onReport(_c: Comment) { alert('Reported') }
function onEdit(payload: { id: string; text: string }) { update(props.postId, payload.id, payload.text) }
</script>
