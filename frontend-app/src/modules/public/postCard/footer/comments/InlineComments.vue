<template>
  <div class="mt-3 rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
    <div class="max-h-[60vh] overflow-y-auto overflow-x-hidden overscroll-contain p-3">
      <CommentsList
        :post-id="postId"
        :page-size="3"
        :logged-in="loggedIn"
        :avatar-url="avatarUrl"
        :avatar-color="avatarColor"
        :display-name="displayName"
        @added="$emit('added')"
      />
    </div>
    <div class="border-t border-zinc-100 p-3 dark:border-zinc-800">
      <CommentComposer
        :logged-in="loggedIn"
        :avatar-url="avatarUrl"
        :avatar-color="avatarColor"
        :display-name="displayName"
        placeholder="Write a comment…"
        rounded
        toolbar
        @submit="onSubmitRoot"
        @login="goLogin"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import {computed, nextTick, provide} from "vue";
import CommentsList from "./CommentsList.vue";
import CommentComposer from "./CommentComposer.vue";
import { useComment } from "@/modules/public/postCard/composables/useComment";
import { useAuthStore } from "@/stores/auth/auth"
import { useProfileStore } from "@/stores/profile/profile"
import {useCommentRealtime} from "@/modules/public/postCard/composables/useCommentsRealtime";

const auth = useAuthStore()
const profile = useProfileStore()
const user = computed(() => auth.user)

const props = defineProps<{ postId: number | string }>();
const emit = defineEmits<{ (e: "added"): void; (e: "login"): void }>();

const loggedIn = computed(() => auth.isAuthenticated);
const displayName = computed(() => profile.meLite?.name || user.value?.name || "User")
const avatarUrl = computed<string>(() => profile.meLite?.avatar || "")
const avatarColor = computed<string | null>(() => profile.meLite?.avatar_color ?? null)

const ctx = useComment(props.postId);
provide("commentCtx", ctx);
useCommentRealtime(props.postId, ctx);

defineExpose({
  async reveal(commentId: number) {
    if (!ctx.roots.value.length) await ctx.fetchRoots()
    await nextTick()
    await ctx.reveal(Number(commentId))
  }
})
async function onSubmitRoot(text: string) {
  await ctx.submit(text, null);
  emit("added");
}
function goLogin() { emit("login"); }
</script>
