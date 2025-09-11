<template>
  <div class="mt-3 rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
    <div class="max-h-[60vh] overflow-y-auto overscroll-contain p-3">
      <CommentsList
        :post-id="postId"
        :page-size="3"
        @added="$emit('added')"
      />
    </div>
    <div class="border-t border-zinc-100 p-3 dark:border-zinc-800">
      <CommentComposer
        :avatar="meAvatar"
        @submit="onSubmitRoot"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import CommentsList from './CommentsList.vue'
import CommentComposer from './CommentComposer.vue'
import { useComments } from '@/modules/public/postCard/composables/useComments'

const props = defineProps<{ postId: string }>()
const emit = defineEmits<{ (e:'added'): void }>()
const { add } = useComments()
const meAvatar = 'https://i.pravatar.cc/80?u=me'
function onSubmitRoot(text: string) { add(props.postId, text); emit('added') }
</script>
