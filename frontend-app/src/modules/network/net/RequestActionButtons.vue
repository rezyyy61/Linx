<template>
  <div class="flex items-center gap-2">
    <button
      class="px-3 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs transition disabled:opacity-70"
      :disabled="accepting || rejecting"
      @click="onAccept"
    >
      <span v-if="accepting">{{ t('follow.actions.accepting') }}</span>
      <span v-else>{{ t('follow.actions.accept') }}</span>
    </button>

    <button
      class="px-3 py-1 rounded-lg bg-rose-600 hover:bg-rose-500 text-white text-xs transition disabled:opacity-70"
      :disabled="accepting || rejecting"
      @click="onReject"
    >
      <span v-if="rejecting">{{ t('follow.actions.rejecting') }}</span>
      <span v-else>{{ t('follow.actions.reject') }}</span>
    </button>
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue"
import { useI18n } from "vue-i18n"
import { useFollowStore } from "@/stores/follow"

const props = defineProps<{ requestId: number | string }>()
const { t } = useI18n()
const followStore = useFollowStore()
const accepting = ref(false)
const rejecting = ref(false)

async function onAccept() {
  if (accepting.value || rejecting.value) return
  accepting.value = true
  try { await followStore.acceptRequest(props.requestId) }
  finally { accepting.value = false }
}
async function onReject() {
  if (accepting.value || rejecting.value) return
  rejecting.value = true
  try { await followStore.rejectRequest(props.requestId) }
  finally { rejecting.value = false }
}
</script>
