<template>
  <button
    :disabled="disabled"
    :class="[
      'px-3 py-1 rounded-lg text-xs transition',
      disabled
        ? 'bg-zinc-300 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 cursor-default'
        : variantClass
    ]"
    @click="onClick"
  >
    <span v-if="busy">{{ t('follow.actions.sending') }}</span>
    <span v-else-if="connected">{{ t('follow.actions.connected') }}</span>
    <span v-else-if="pending">{{ t('follow.actions.requested') }}</span>
    <span v-else>{{ label }}</span>
  </button>
</template>

<script setup lang="ts">
import { computed, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useFollowStore } from "@/stores/follow"
import { useFollowUiState } from "../composables/useFollowUiState"

const props = withDefaults(defineProps<{
  userId: number | string
  label?: string
  variant?: "primary" | "secondary"
}>(), {
  label: "Follow",
  variant: "primary"
})

const { t } = useI18n()
const followStore = useFollowStore()
const ui = useFollowUiState()

const busy = computed(() => ui.isLoading(props.userId))
const pendingFromStore = computed(() => followStore.isPendingWith(props.userId))
const connected = computed(() => followStore.isConnected(props.userId))
const pending = computed(() => ui.isRequested(props.userId) || pendingFromStore.value)
const disabled = computed(() => connected.value || pending.value || busy.value)

const variantClass = computed(() =>
  props.variant === "primary"
    ? "bg-indigo-600 hover:bg-indigo-500 text-white"
    : "bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-100"
)

watch([pendingFromStore, connected], ([p, c]) => {
  if (c || !p) ui.clearRequested(props.userId)
})

async function onClick() {
  if (disabled.value) return
  ui.start(props.userId)
  try {
    await followStore.sendFollow(props.userId)
    ui.succeed(props.userId)
  } catch {
    ui.fail(props.userId)
  }
}
</script>
