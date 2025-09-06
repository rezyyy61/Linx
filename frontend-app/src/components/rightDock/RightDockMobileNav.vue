<script setup lang="ts">
import { Icon } from '@iconify/vue'

type TabKey = 'notifications' | 'messages' | 'quick'

const emit = defineEmits<{
  (e: 'toggle', tab: TabKey): void
}>()
</script>

<template>
  <nav class="md:hidden fixed bottom-0 left-0 right-0 z-50 bg-white/90 dark:bg-zinc-900/90 border-t border-zinc-200 dark:border-zinc-800 grid grid-cols-3 backdrop-blur">
    <button
      class="py-3 text-center transition"
      @click="emit('toggle','notifications')"
    >
      <Icon
        icon="solar:bell-bing-broken"
        width="22"
        height="22"
      />
    </button>
    <button
      class="py-3 text-center transition"
      @click="emit('toggle','messages')"
    >
      <Icon
        icon="mdi:message-text-outline"
        width="22"
        height="22"
      />
    </button>
    <button
      class="py-3 text-center transition"
      @click="emit('toggle','quick')"
    >
      <Icon
        icon="solar:bolt-linear"
        width="22"
        height="22"
      />
    </button>
  </nav>
</template>
