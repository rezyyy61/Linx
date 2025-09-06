<script setup lang="ts">
import { computed } from 'vue'
import { Icon } from '@iconify/vue'
import PanelNotifications from './panels/PanelNotifications.vue'
import PanelNetwork from './panels/PanelNetwork.vue'
import PanelMessages from './panels/PanelMessages.vue'
import PanelQuick from './panels/PanelQuick.vue'

type TabKey = 'notifications' | 'network' | 'messages' | 'quick'

const props = defineProps<{
  tab: TabKey
  title: string
  notifications: Array<any>
  messages: Array<any>
}>()

const emit = defineEmits<{
  (e: 'close'): void
}>()

const panelClass = computed(() => {
  const base = [
    'hidden md:block fixed right-16 top-1/2 -translate-y-1/2',
    'max-h-[80vh] overflow-y-auto rounded-2xl border shadow-2xl z-40',
    props.tab === 'quick'
      ? 'w-[460px] bg-white/50 dark:bg-zinc-900/40 backdrop-blur-xl border-zinc-200/60 dark:border-zinc-700/60'
      : 'w-[360px] bg-white/90 dark:bg-zinc-900/90 backdrop-blur border-zinc-200 dark:border-zinc-800'
  ]
  return base.join(' ')
})

const headerClass = computed(() => [
  'px-4 py-3 border-b sticky top-0 z-10 flex items-center justify-between',
  props.tab === 'quick'
    ? 'border-zinc-200/60 dark:border-zinc-800/60 bg-white/50 dark:bg-zinc-900/40 backdrop-blur-xl'
    : 'border-zinc-200 dark:border-zinc-800 bg-white/90 dark:bg-zinc-900/90 backdrop-blur'
].join(' '))
</script>

<template>
  <div
    :class="panelClass"
    role="dialog"
    aria-modal="true"
  >
    <header :class="headerClass">
      <h3 class="font-semibold capitalize text-zinc-900 dark:text-zinc-100">
        {{ title }}
      </h3>
      <button
        class="p-2 rounded-md hover:bg-zinc-100 dark:hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        aria-label="Close"
        @click="emit('close')"
      >
        <Icon
          icon="mdi:close"
          width="18"
          height="18"
        />
      </button>
    </header>

    <section class="p-4 space-y-3">
      <PanelNotifications
        v-if="tab==='notifications'"
        :items="notifications"
      />
      <PanelNetwork v-else-if="tab==='network'" />
      <PanelMessages
        v-else-if="tab==='messages'"
        :items="messages"
      />
      <PanelQuick v-else />
    </section>
  </div>
</template>
