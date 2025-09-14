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
        :logged-in="loggedIn"
        :avatar-url="avatarUrl"
        :avatar-color="avatarColor"
        @submit="onSubmitRoot"
        @login="goLogin"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import CommentsList from './CommentsList.vue'
import CommentComposer from './CommentComposer.vue'
import { useComments } from '@/modules/public/postCard/composables/useComments'
import { useCommentsRealtime } from '@/modules/public/postCard/composables/useCommentsRealtime'
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth/auth'
import { useProfileStore } from '@/stores/profile/profile'

const props = defineProps<{ postId: string }>()
const emit = defineEmits<{ (e:'added'): void }>()

const { add, upsertFromRealtime, deleteFromRealtime, onCountsRealtime } = useComments(props.postId)
useCommentsRealtime({
  onCreated: (c) => upsertFromRealtime(c),
  onDeleted: (id, postId) => deleteFromRealtime(id, postId),
  onCounts: (id, postId, counts) => onCountsRealtime(id, postId, counts),
})

const auth = useAuthStore()
const profile = useProfileStore()

const loggedIn = computed(() => !!auth.user)
const avatarUrl = computed<string | null>(() => profile.meLite?.avatar ?? null)
const avatarColor = computed<string | null>(() => profile.meLite?.avatar_color ?? null)

async function onSubmitRoot(text: string) {
  await add(props.postId, text)
  emit('added')
}

function goLogin() {
  window.location.href = '/login'
}
</script>
