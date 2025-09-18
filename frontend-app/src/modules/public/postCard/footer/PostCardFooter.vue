<template>
  <footer class="mt-2 flex items-center justify-between text-sm text-zinc-600 dark:text-zinc-300">
    <div class="flex items-center gap-3 sm:gap-5">
      <LikeButton
        :count="likes"
        :active="liked"
        @click="$emit('like')"
      />
      <CommentButton
        :count="comments"
        @click="$emit('comment')"
      />
      <ShareButton
        :count="shares"
        @click="$emit('share')"
      />
      <SaveButton
        :count="saves"
        :active="saved"
        @click="$emit('save')"
      />
    </div>
    <div class="text-xs text-zinc-500">
      {{ fmt(views || 0) }} views
    </div>
  </footer>
</template>

<script setup lang="ts">
import { formatNumber as fmt } from '@/modules/public/postCard/utils/formatNumber'
import LikeButton from './actions/LikeButton.vue'
import CommentButton from './actions/CommentButton.vue'
import ShareButton from './actions/ShareButton.vue'
import SaveButton from './actions/SaveButton.vue'

const { likes, comments, shares, saves, liked, saved, views } = defineProps({
  likes: { type: Number, required: true },
  comments: { type: Number, required: true },
  shares: { type: Number, required: true },
  saves: { type: Number, required: true },
  liked: { type: Boolean, required: true },
  saved: { type: Boolean, required: true },
  views: { type: Number, default: 0 }
})

defineEmits<{
  (e: 'like'): void
  (e: 'comment'): void
  (e: 'share'): void
  (e: 'save'): void
}>()
</script>
