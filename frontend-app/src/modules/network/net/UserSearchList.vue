<template>
  <div class="space-y-2">
    <div
      v-if="loading"
      class="text-sm text-zinc-500 dark:text-zinc-400"
    >
      {{ t('follow.search.loading') }}
    </div>

    <div v-else-if="results.length===0">
      <p class="text-sm text-zinc-500 dark:text-zinc-400">
        {{ t('follow.search.empty') }}
      </p>
    </div>

    <div
      v-else
      class="space-y-2"
    >
      <UserRow
        v-for="u in results"
        :key="u.id"
        :user="u"
      >
        <!-- اگر دوست بودی، دکمه Unfollow؛ وگرنه Follow -->
        <button
          v-if="isFriend(u.id)"
          class="px-3 py-1 rounded-lg bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-xs text-zinc-800 dark:text-zinc-100 transition"
          @click="unfollow(u.id)"
        >
          {{ t('follow.actions.unfollow') }}
        </button>
        <FollowButton
          v-else
          :user-id="u.id"
          :label="t('follow.actions.follow')"
          variant="primary"
        />
      </UserRow>

      <div
        v-if="hasMore"
        class="pt-1"
      >
        <button
          class="w-full px-3 py-2 rounded-lg bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-xs text-zinc-700 dark:text-zinc-200 transition"
          :disabled="loading"
          @click="$emit('loadMore')"
        >
          {{ loading ? t('follow.search.loading') : t('follow.search.loadMore') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { storeToRefs } from "pinia"
import { useI18n } from "vue-i18n"
import { useFollowStore } from "@/stores/follow"
import FollowButton from "./FollowButton.vue"
import UserRow from "./UserRow.vue"

const { t } = useI18n()
const followStore = useFollowStore()
const { friends } = storeToRefs(followStore)

defineProps<{
  results: any[]
  loading: boolean
  hasMore: boolean
}>()
defineEmits<{ (e: 'loadMore'): void }>()

function isFriend(id: number | string) {
  return friends.value.some(f => f.id === id)
}
async function unfollow(id: number | string) {
  await followStore.unfollow(id)
}
</script>
