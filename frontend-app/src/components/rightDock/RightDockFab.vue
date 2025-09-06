<script setup lang="ts">
import { Icon } from '@iconify/vue'

type TabKey = 'notifications' | 'network' | 'messages' | 'quick'

const props = defineProps<{
  open: boolean
  tab: TabKey
  unreadCount?: number
  pendingCount?: number
}>()

const emit = defineEmits<{
  (e: 'toggle', tab: TabKey): void
}>()

function btnClass(active: boolean) {
  return [
    'rounded-full p-3 shadow transition focus:outline-none focus:ring-2 focus:ring-indigo-500',
    active && props.open
      ? 'bg-indigo-600 text-white'
      : 'bg-zinc-100 hover:bg-zinc-200 text-zinc-800 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:text-zinc-100'
  ].join(' ')
}
</script>

<template>
  <aside class="hidden md:flex fixed right-4 top-1/2 -translate-y-1/2 z-50 flex-col gap-3">
    <button
      :class="btnClass(tab==='notifications')"
      aria-label="Notifications"
      @click="emit('toggle','notifications')"
    >
      <span class="relative inline-block">
        <Icon
          icon="solar:bell-bing-broken"
          width="20"
          height="20"
        />
        <span
          v-if="(unreadCount ?? 0) > 0"
          class="absolute -top-2 -right-2 min-w-5 h-5 px-1 grid place-items-center text-xs rounded-full bg-red-600 text-white"
        >
          {{ unreadCount }}
        </span>
      </span>
    </button>

    <button
      :class="btnClass(tab==='network')"
      aria-label="Network"
      @click="emit('toggle','network')"
    >
      <span class="relative inline-block">
        <Icon
          icon="mdi:account-multiple-outline"
          width="20"
          height="20"
        />
        <span
          v-if="(pendingCount ?? 0) > 0"
          class="absolute -top-2 -right-2 min-w-5 h-5 px-1 grid place-items-center text-xs rounded-full bg-indigo-600 text-white"
        >
          {{ pendingCount }}
        </span>
      </span>
    </button>

    <button
      :class="btnClass(tab==='messages')"
      aria-label="Messages"
      @click="emit('toggle','messages')"
    >
      <Icon
        icon="mdi:message-text-outline"
        width="20"
        height="20"
      />
    </button>

    <button
      :class="btnClass(tab==='quick')"
      aria-label="Quick actions"
      @click="emit('toggle','quick')"
    >
      <Icon
        icon="solar:bolt-linear"
        width="20"
        height="20"
      />
    </button>
  </aside>
</template>
