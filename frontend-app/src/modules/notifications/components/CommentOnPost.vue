<!-- src/modules/notifications/components/CommentOnPost.vue -->
<template>
  <BaseNotification
    :item="item"
    :theme="theme"
    @main="openTarget"
    @mark-read="$emit('mark-read',$event)"
    @remove="$emit('remove',$event)"
  >
    <template #title>
      <div class="flex items-center gap-2">
        <span class="max-w-[200px] truncate font-semibold text-zinc-900 dark:text-zinc-100">@{{ item.title || 'user' }}</span>
        <span class="inline-flex items-center gap-1 rounded-full bg-indigo-100 px-2 py-0.5 text-[11px] font-medium text-indigo-700 ring-1 ring-inset ring-indigo-200/70 dark:bg-indigo-500/15 dark:text-indigo-300 dark:ring-indigo-500/30">
          <svg
            viewBox="0 0 24 24"
            class="h-3.5 w-3.5"
            fill="currentColor"
          ><path d="M21 6.5a2.5 2.5 0 0 0-2.5-2.5h-13A2.5 2.5 0 0 0 3 6.5v8A2.5 2.5 0 0 0 5.5 17H8l3.3 3.3c.63.63 1.7.19 1.7-.71V17h5.5A2.5 2.5 0 0 0 21 14.5v-8z" /></svg>
          Commented
        </span>
      </div>
    </template>

    <template #body>
      <div class="mt-1">
        <p class="truncate text-[13px] leading-relaxed text-zinc-700 dark:text-zinc-200/90">
          {{ displayBody }}
        </p>
        <button
          class="mt-2 inline-flex items-center gap-1 rounded-full border border-indigo-200 bg-white px-2.5 py-1 text-xs font-medium text-indigo-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-indigo-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 active:translate-y-0 dark:border-indigo-500/30 dark:bg-transparent dark:text-indigo-300 dark:hover:bg-indigo-500/10"
          @click="openTarget"
        >
          View comment
          <svg
            viewBox="0 0 24 24"
            class="h-3.5 w-3.5"
            fill="currentColor"
          ><path d="M12 4l1.41 1.41L8.83 10H20v2H8.83l4.58 4.59L12 18l-8-8 8-8z" /></svg>
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
const emit = defineEmits<{ (e:"navigate", to:any):void; (e:"mark-read", id:number):void; (e:"remove", id:number):void }>()
const theme = { accent:"bg-gradient-to-r from-indigo-500/80 to-sky-500/80 dark:from-indigo-500/60 dark:to-sky-500/60", avatarBg:"bg-indigo-100 dark:bg-indigo-500/25 text-indigo-800 dark:text-indigo-100", badge:"border-indigo-300/50 dark:border-indigo-400/40 bg-indigo-100/70 dark:bg-indigo-500/20 text-indigo-800 dark:text-indigo-100", badgeSoft:"border-indigo-300/40 dark:border-indigo-400/30 bg-indigo-100/50 dark:bg-indigo-500/10 text-indigo-800 dark:text-indigo-100", icon:"mdi:comment" }
const displayBody = computed(()=> props.item.body && props.item.body.trim() ? props.item.body.trim() : "commented on your post")

function openTarget(){
  if (!props.item.href) return
  const u = new URL(props.item.href, window.location.origin)
  const path = u.pathname                  // /p/158
  const cid = u.searchParams.get("comment") // "123" یا null
  const hash = u.hash || (cid ? `#c-${cid}` : "")
  const to = { path, ...(cid ? { query:{ comment: Number(cid) } } : {}), ...(hash ? { hash } : {}) }
  emit("navigate", to)
}
</script>
