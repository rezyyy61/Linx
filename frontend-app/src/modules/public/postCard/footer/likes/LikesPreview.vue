<template>
  <div class="mt-2 flex items-center justify-end">
    <button
      class="group inline-flex items-center gap-2 rounded-xl px-2 py-1 text-xs text-zinc-600 hover:bg-zinc-100 hover:text-indigo-600 dark:text-zinc-300 dark:hover:bg-zinc-800"
      @click="open=true"
    >
      <div class="flex -space-x-3">
        <div
          v-for="u in usersToShow"
          :key="u.id"
          class="inline-flex h-6 w-6 items-center justify-center rounded-full ring-2 ring-white dark:ring-zinc-900 overflow-hidden"
        >
          <AvatarUser
            :src="u.avatarUrl || undefined"
            :color="u.avatarColor || undefined"
            :name="u.name"
            size="sm"
            rounded="full"
            ring
          />
        </div>
        <div
          v-if="restCount>0"
          class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-zinc-200 text-[10px] font-semibold text-zinc-700 ring-2 ring-white dark:bg-zinc-700 dark:text-zinc-100 dark:ring-zinc-900"
        >
          +{{ restCount }}
        </div>
      </div>
      <span class="hidden sm:inline">{{ total }} likes</span>
    </button>
    <LikesDialog
      :open="open"
      :post-id="postId"
      @close="open=false"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onBeforeUnmount, ref, watchEffect } from 'vue'
import { usePostLikes } from '../../composables/usePostLikes'
import LikesDialog from './LikesDialog.vue'
import AvatarUser from "@/components/shared/AvatarUser.vue"

const props = defineProps<{ postId: string }>()
const open = ref(false)
const { previewByPost, fetchPreview, startRealtime, stopRealtime } = usePostLikes()
const preview = computed(() => previewByPost.value[props.postId])
const usersToShow = computed(() => (preview.value?.users || []).slice(0,2))
const total = computed(() => preview.value?.total || 0)
const restCount = computed(() => Math.max(0, total.value - usersToShow.value.length))

onMounted(async () => {
  await startRealtime()
  await fetchPreview(props.postId)
})
onBeforeUnmount(async () => { await stopRealtime() })
watchEffect(() => { if (!preview.value) fetchPreview(props.postId) })
</script>
