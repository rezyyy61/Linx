<script setup lang="ts">
import ThreadNode from "./ThreadNode.vue"
import type { Comment } from "@/modules/public/postCard/types/comment.types"

const { postId, root, loggedIn, avatarUrl = null, avatarColor = null, sortBy: _sortBy } = defineProps<{
  postId: number | string
  root: Comment
  sortBy: "relevant" | "newest" | "top"
  loggedIn: boolean
  avatarUrl?: string | null
  avatarColor?: string | null
}>()

const emit = defineEmits<{ (e: "added"): void }>()
</script>

<template>
  <ThreadNode
    :comment="root"
    :post-id="postId"
    :logged-in="loggedIn"
    :avatar-url="avatarUrl"
    :avatar-color="avatarColor"
    :is-child="false"
    @added="emit('added')"
  />
</template>
