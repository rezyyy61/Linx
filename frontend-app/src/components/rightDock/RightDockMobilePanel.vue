<template>
  <div class="md:hidden fixed inset-0 z-40">
    <div
      class="absolute inset-0 bg-black/50"
      aria-hidden="true"
      @click="$emit('close')"
    />

    <!-- Quick: centered modal with glass -->
    <div
      v-if="tab==='quick'"
      class="absolute inset-x-4 top-1/2 -translate-y-1/2 max-h-[78vh] rounded-2xl border border-zinc-200/60 dark:border-zinc-700/60 bg-white/50 dark:bg-zinc-900/40 backdrop-blur-xl p-4 overflow-y-auto"
    >
      <header class="flex items-center justify-between pb-2 border-b border-zinc-200/70 dark:border-zinc-800/70">
        <h3 class="font-semibold capitalize text-zinc-900 dark:text-zinc-100">
          {{ title }}
        </h3>
        <button
          class="p-2 rounded-md hover:bg-zinc-100/70 dark:hover:bg-zinc-800/70 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          aria-label="Close"
          @click="$emit('close')"
        >
          <Icon
            icon="mdi:close"
            width="18"
            height="18"
          />
        </button>
      </header>
      <section class="pt-3 space-y-2">
        <PanelQuick />
      </section>
    </div>

    <!-- Others: bottom sheet -->
    <div
      v-else
      class="absolute left-0 right-0 bottom-0 max-h-[70vh] rounded-t-2xl bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-800 p-4 overflow-y-auto"
    >
      <header class="flex items-center justify-between pb-2 border-b border-zinc-200 dark:border-zinc-800">
        <h3 class="font-semibold capitalize text-zinc-900 dark:text-zinc-100">
          {{ title }}
        </h3>
        <button
          class="p-2 rounded-md hover:bg-zinc-100 dark:hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          aria-label="Close"
          @click="$emit('close')"
        >
          <Icon
            icon="mdi:close"
            width="18"
            height="18"
          />
        </button>
      </header>

      <section class="pt-3 space-y-3">
        <PanelNotifications
          v-if="tab==='notifications'"
          :items="notifications"
        />
        <PanelNetwork v-else-if="tab==='network'" />
        <PanelMessages
          v-else-if="tab==='messages'"
          :items="messages"
        />
      </section>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Icon } from "@iconify/vue"
import PanelNotifications from "./panels/PanelNotifications.vue"
import PanelNetwork from "./panels/PanelNetwork.vue"
import PanelMessages from "./panels/PanelMessages.vue"
import PanelQuick from "./panels/PanelQuick.vue"

defineProps<{
  tab: "notifications" | "network" | "messages" | "quick"
  title: string
  notifications: Array<any>
  messages: Array<any>
}>()

// eslint-disable-next-line @typescript-eslint/no-unused-vars
const emit = defineEmits<{ (e: "close"): void }>()
</script>
