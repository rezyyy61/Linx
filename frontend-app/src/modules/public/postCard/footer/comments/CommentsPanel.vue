<template>
  <teleport to="body">
    <transition name="fade">
      <div
        v-if="open"
        class="fixed inset-0 z-[90] flex items-center justify-center"
      >
        <div
          class="absolute inset-0 bg-black/40 backdrop-blur-sm"
          @click="$emit('close')"
        />
        <div class="relative z-10 w-full max-w-2xl overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-2xl dark:border-zinc-800 dark:bg-zinc-950">
          <div class="sticky top-0 z-10 flex items-center justify-between border-b border-zinc-200 bg-white/90 p-4 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/90">
            <h3 class="text-sm font-semibold">
              Comments
            </h3>
            <button
              class="rounded-lg p-2 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-900"
              aria-label="Close"
              @click="$emit('close')"
            >
              <svg
                viewBox="0 0 24 24"
                class="h-5 w-5"
                fill="none"
                stroke="currentColor"
                stroke-width="1.5"
              ><path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M6 18L18 6M6 6l12 12"
              /></svg>
            </button>
          </div>
          <div class="max-h-[70vh] overflow-y-auto p-4">
            <CommentsList
              :post-id="postId"
              :page-size="3"
            />
          </div>
          <div class="border-t border-zinc-200 p-4 dark:border-zinc-800">
            <CommentComposer
              :logged-in="loggedIn"
              :avatar-url="avatarUrl"
              :avatar-color="avatarColor"
              @submit="onSubmitRoot"
              @login="goLogin"
            />
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup lang="ts">
import CommentsList from './CommentsList.vue'
import CommentComposer from './CommentComposer.vue'
import { useComments } from '@/modules/public/postCard/composables/useComments'
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth/auth'
import { useProfileStore } from '@/stores/profile/profile'

const props = defineProps<{ postId: string; open: boolean }>()
const emit = defineEmits<{ (e: 'close'): void; (e: 'added'): void }>()

const { add } = useComments(props.postId)

const auth = useAuthStore()
const profile = useProfileStore()
const loggedIn = computed(() => !!auth.user)
const avatarUrl = computed<string | null>(() => profile.meLite?.avatar ?? null)
const avatarColor = computed<string | null>(() => profile.meLite?.avatar_color ?? null)

async function onSubmitRoot(text: string) {
  await add(props.postId, text)
  emit('added')
}
function goLogin() { window.location.href = '/login' }
</script>

<style scoped>
.fade-enter-active,.fade-leave-active{transition:opacity .15s ease}
.fade-enter-from,.fade-leave-to{opacity:0}
</style>
