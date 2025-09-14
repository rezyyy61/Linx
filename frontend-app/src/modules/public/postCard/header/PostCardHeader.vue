<template>
  <header class="mb-3 flex items-start gap-3">
    <AvatarUser
      :src="avatar "
      :color="color"
      :name="name"
      size="sm"
      rounded="full"
      ring
      zoomable
    />
    <div class="min-w-0 flex-1">
      <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
          <div class="truncate text-sm font-semibold text-zinc-900 dark:text-zinc-100">
            {{ name }}
          </div>
          <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
            {{ time }}
          </div>
        </div>
        <div class="flex items-center gap-2">
          <button
            v-if="!isOwner"
            class="rounded-full px-3 py-1 text-xs font-medium text-white hover:opacity-95 dark:text-white"
            :class="isFollowing ? 'bg-zinc-800 dark:bg-white/10' : 'bg-indigo-600'"
            @click="$emit('toggle-follow')"
          >
            {{ isFollowing ? 'Unfollow' : 'Follow' }}
          </button>
          <PostHeaderMenu
            :is-owner="isOwner"
            @edit="$emit('edit')"
            @delete="$emit('delete')"
            @copy="$emit('copy')"
            @report="$emit('report')"
            @share="$emit('share')"
          />
        </div>
      </div>
    </div>
  </header>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import PostHeaderMenu from './PostHeaderMenu.vue'
import { useTimeAgo } from '../composables/useTimeAgo'
import AvatarUser from "@/components/shared/AvatarUser.vue";
const props = defineProps<{
  avatar?: string
  color?: string
  name: string
  createdAt: string
  isFollowing?: boolean
  isOwner?: boolean
}>()
defineEmits<{ (e:'toggle-follow'):void; (e:'edit'):void; (e:'delete'):void; (e:'copy'):void; (e:'report'):void; (e:'share'):void }>()
const { timeAgo } = useTimeAgo()
const time = computed(()=> timeAgo(props.createdAt))
</script>
