<script setup lang="ts">
import { computed, ref } from "vue"
import { Icon } from "@iconify/vue"
import PanelNotifications from "./panels/PanelNotifications.vue"
import PanelNetwork from "./panels/PanelNetwork.vue"
import PanelMessages from "./panels/PanelMessages.vue"
import PanelQuick from "./panels/PanelQuick.vue"

type TabKey = "notifications" | "network" | "messages" | "quick"

const props = defineProps<{
  tab: TabKey
  title: string
  notifications: Array<any>
  messages: Array<any>
  showContent?: boolean
}>()

const emit = defineEmits<{ (e: "close"): void }>()

const expanded = ref(false)

const wrapperClass = "hidden md:block fixed inset-0 z-50 pointer-events-none"

const backdropClass =
  "fixed inset-0 z-40 bg-gradient-to-br from-black/10 via-black/25 to-black/10 backdrop-blur-sm pointer-events-auto"

const panelClass = computed(() => {
  const base = [
    "fixed z-50 pointer-events-auto overflow-y-auto",
    "rounded-2xl border shadow-2xl",
    "transition-all duration-200 ease-out will-change-transform",
  ]
  if (expanded.value) {
    base.push(
      "left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2",
      "w-[760px] max-w-[92vw] max-h-[86vh]",
      "bg-white/95 dark:bg-zinc-900/65",
      "border-zinc-200/80 dark:border-white/10",
      "ring-1 ring-black/5 dark:ring-white/10",
    )
  } else {
    base.push(
      "right-16 top-1/2 -translate-y-1/2",
      "w-[420px] max-h-[80vh]",
      "bg-white/92 dark:bg-zinc-900/55",
      "border-zinc-200/80 dark:border-white/10",
      "ring-1 ring-black/5 dark:ring-white/5",
    )
  }
  return base.join(" ")
})

const headerClass = computed(() => [
  "px-3 py-2 border-b sticky top-0 z-10 flex items-center justify-between",
  "border-zinc-200/80 dark:border-white/10",
  expanded.value
    ? "bg-white/90 dark:bg-zinc-900/65 backdrop-blur"
    : "bg-white/80 dark:bg-zinc-900/55 backdrop-blur",
].join(" "))
</script>

<template>
  <div
    :class="wrapperClass"
    role="dialog"
    aria-modal="true"
  >
    <div
      v-if="expanded"
      :class="backdropClass"
      aria-hidden="true"
      @click="expanded = false"
    />

    <div :class="panelClass">
      <header :class="headerClass">
        <h3 class="font-medium text-sm capitalize text-zinc-900 dark:text-zinc-100">
          {{ title }}
        </h3>

        <div class="flex items-center gap-1.5">
          <!-- Expand / Collapse -->
          <button
            class="p-1.5 rounded-md hover:bg-zinc-100 dark:hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            :aria-label="expanded ? 'Collapse' : 'Expand'"
            @click.stop="expanded = !expanded"
          >
            <Icon
              :icon="expanded ? 'mdi:arrow-collapse' : 'mdi:arrow-expand'"
              width="16"
              height="16"
            />
          </button>

          <!-- Close -->
          <button
            class="p-1.5 rounded-md hover:bg-zinc-100 dark:hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            aria-label="Close"
            @click.stop="emit('close')"
          >
            <Icon
              icon="mdi:close"
              width="16"
              height="16"
            />
          </button>
        </div>
      </header>

      <section class="p-3 space-y-3">
        <PanelNotifications
          v-if="props.tab==='notifications'"
          :items="props.notifications"
        />
        <PanelNetwork v-else-if="props.tab==='network'" />
        <PanelMessages
          v-else-if="props.tab==='messages'"
          :items="props.messages"
        />
        <PanelQuick v-else />
      </section>
    </div>
  </div>
</template>
