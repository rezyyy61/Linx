<!-- src/modules/notifications/components/CommentMentioned.vue -->
<template>
  <BaseNotification
    :item="item"
    :theme="violet"
    @main="openTarget"
    @mark-read="$emit('mark-read',$event)"
    @remove="$emit('remove',$event)"
  >
    <!-- Title -->
    <template #title>
      <div class="flex items-center gap-2">
        <span class="max-w-[200px] truncate font-semibold text-zinc-900 dark:text-zinc-100">
          @{{ displayTitle }}
        </span>
        <span
          class="inline-flex items-center gap-1 rounded-full bg-violet-100 px-2 py-0.5 text-[11px] font-medium text-violet-700 ring-1 ring-inset ring-violet-200/70 dark:bg-violet-500/15 dark:text-violet-300 dark:ring-violet-500/30"
        >
          <svg
            viewBox="0 0 24 24"
            class="h-3.5 w-3.5"
            fill="currentColor"
            aria-hidden="true"
          >
            <path d="M12 3a9 9 0 100 18c2.4 0 4.6-.94 6.24-2.49l-1.41-1.41A7 7 0 1112 5a7 7 0 015.66 2.94h-2.25a3.5 3.5 0 10-3.41 4.33H18a9 9 0 00-6-9.27z" />
          </svg>
          Mentioned
        </span>
      </div>
    </template>

    <!-- Body -->
    <template #body>
      <div class="mt-1">
        <p class="truncate text-[13px] leading-relaxed text-zinc-700 dark:text-zinc-200/90">
          {{ displayBody }}
        </p>

        <button
          class="mt-2 inline-flex items-center gap-1 rounded-full border border-violet-200 bg-white px-2.5 py-1 text-xs font-medium text-violet-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-violet-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500 active:translate-y-0 dark:border-violet-500/30 dark:bg-transparent dark:text-violet-300 dark:hover:bg-violet-500/10"
          @click="openTarget"
        >
          View comment
          <svg
            viewBox="0 0 24 24"
            class="h-3.5 w-3.5"
            fill="currentColor"
            aria-hidden="true"
          >
            <path d="M8 5l7 7-7 7-1.5-1.5L12 12 6.5 6.5 8 5z" />
          </svg>
        </button>
      </div>
    </template>
  </BaseNotification>
</template>

<script setup lang="ts">
import BaseNotification from "./BaseNotification.vue"
import type { NotificationVM } from "@/modules/notifications/types"
import { computed } from "vue"

const props = defineProps<{ item: NotificationVM }>()
const emit = defineEmits<{
  (e:"navigate", to:any):void;
  (e:"mark-read", id:number):void;
  (e:"remove", id:number):void
}>()

const violet = {
  accent: "bg-violet-500/70 dark:bg-violet-500/60",
  avatarBg: "bg-violet-100 dark:bg-violet-500/30 text-violet-800 dark:text-violet-100",
  badge: "border-violet-300/50 dark:border-violet-400/40 bg-violet-100/70 dark:bg-violet-500/20 text-violet-800 dark:text-violet-100",
  badgeSoft: "border-violet-300/40 dark:border-violet-400/30 bg-violet-100/50 dark:bg-violet-500/10 text-violet-800 dark:text-violet-100",
  icon: "mdi:at"
}

const displayTitle = computed(() => (props.item.title && props.item.title.trim()) ? props.item.title.trim() : "user")
const displayBody  = computed(() => (props.item.body && props.item.body.trim()) ? props.item.body.trim() : "mentioned you in a comment")

function openTarget() {
  if (!props.item.href) return
  const u = new URL(props.item.href, window.location.origin)
  const path = u.pathname
  const cid  = u.searchParams.get("comment")
  const hash = u.hash || (cid ? `#c-${cid}` : "")
  const to   = {
    path,
    ...(cid ? { query: { comment: Number(cid) } } : {}),
    ...(hash ? { hash } : {})
  }
  emit("navigate", to)
}
</script>
