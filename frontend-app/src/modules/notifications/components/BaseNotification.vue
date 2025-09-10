<template>
  <div
    :class="[
      'group relative p-3 sm:p-3.5 ps-3 rounded-2xl border flex items-start gap-3 transition-all cursor-pointer outline-none',
      'text-sm shadow-[0_8px_26px_-16px_rgba(0,0,0,0.55)]',
      'bg-white/90 hover:bg-white dark:bg-gradient-to-br dark:from-zinc-900/70 dark:to-zinc-800/60 dark:hover:from-zinc-900/80 dark:hover:to-zinc-800/75',
      'border-zinc-200/80 dark:border-white/10',
      !item.read ? 'ring-1 ring-indigo-400/25 dark:ring-indigo-400/30' : 'ring-0',
      'focus-visible:ring-2 focus-visible:ring-indigo-400/50 hover:translate-y-[-1px]'
    ]"
    role="listitem"
    tabindex="0"
    :aria-label="ariaTitle"
    @click="$emit('main')"
    @keydown="onKey"
  >
    <div
      class="absolute inset-y-0 left-0 w-1 rounded-s-2xl"
      :class="theme.accent"
      aria-hidden="true"
    />

    <div class="shrink-0 mt-0.5 relative">
      <img
        v-if="item.avatar"
        :src="item.avatar"
        alt=""
        class="w-9 h-9 rounded-xl object-cover ring-2 ring-white/70 dark:ring-white/10"
      >
      <div
        v-else
        :class="['w-9 h-9 rounded-xl flex items-center justify-center ring-2 ring-white/60 dark:ring-white/10', theme.avatarBg]"
        aria-hidden="true"
      >
        <Icon
          :icon="theme.icon"
          width="18"
          height="18"
          class="opacity-90"
        />
      </div>
      <span
        v-if="!item.read"
        class="absolute -right-1 -top-1 inline-flex h-2.5 w-2.5"
      >
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-30" />
        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-indigo-400 ring-2 ring-indigo-400/25" />
      </span>
    </div>

    <div class="flex-1 min-w-0">
      <div class="flex items-start gap-2">
        <p class="font-semibold text-zinc-900 dark:text-zinc-50 truncate">
          <slot name="title">
            {{ fallbackName }}
          </slot>
          <span
            v-if="!item.read"
            class="ms-2 align-middle text-[10px] px-1.5 py-0.5 rounded-md border"
            :class="theme.badge"
          >New</span>
        </p>
      </div>

      <slot name="body">
        <p
          v-if="item.body"
          class="mt-0.5 text-zinc-700 dark:text-zinc-200/90 truncate"
        >
          {{ item.body }}
        </p>
      </slot>

      <div
        v-if="$slots.actions"
        class="mt-2 flex flex-wrap items-center gap-2"
        @click.stop
      >
        <slot name="actions" />
      </div>

      <div class="mt-1.5 text-[11px] text-zinc-500 dark:text-zinc-400">
        {{ relativeAgo }}
      </div>
    </div>

    <div
      class="flex items-center gap-1.5"
      @click.stop
    >
      <button
        v-if="!item.read"
        class="p-1.5 rounded-md hover:bg-zinc-100 dark:hover:bg-white/10"
        aria-label="Mark as read"
        @click="$emit('mark-read', item.id)"
      >
        <Icon
          icon="mdi:check"
          width="16"
          height="16"
        />
      </button>
      <button
        class="p-1.5 rounded-md hover:bg-zinc-100 dark:hover:bg-white/10"
        aria-label="Delete"
        @click="$emit('remove', item.id)"
      >
        <Icon
          icon="mdi:trash-can-outline"
          width="16"
          height="16"
        />
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Icon } from "@iconify/vue"
import { computed } from "vue"
import { useI18n } from "vue-i18n"
import type { NotificationVM } from "@/modules/notifications/types"

type Theme = { accent:string; avatarBg:string; badge:string; badgeSoft:string; icon:string }
const props = defineProps<{ item: NotificationVM; theme: Theme }>()
const { t } = useI18n()

const fallbackName = computed(() => props.item.title || props.item.handle || t("notification.user"))
const ariaTitle = computed(() => String(fallbackName.value))

const relativeAgo = computed(() => {
  const d = new Date(props.item.createdAt)
  if (isNaN(d.getTime())) return String(props.item.createdAt)
  const diff = Date.now() - d.getTime()
  const sec = Math.floor(diff / 1000)
  if (sec < 60) return t("notification.ago.now")
  const min = Math.floor(sec / 60)
  if (min < 60) return t("notification.ago.m", { n: min })
  const hr = Math.floor(min / 60)
  if (hr < 24) return t("notification.ago.h", { n: hr })
  const day = Math.floor(hr / 24)
  return t("notification.ago.d", { n: day })
})

const emit = defineEmits<{ (e:"main"):void; (e:"mark-read", id:number):void; (e:"remove", id:number):void }>()
function onKey(e: KeyboardEvent){ if(e.key==="Enter"||e.key===" "){ e.preventDefault(); emit("main") } }
</script>
