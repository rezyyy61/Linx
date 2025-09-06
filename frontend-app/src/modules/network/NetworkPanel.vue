<script setup lang="ts">
import { ref, onMounted, watch, computed } from "vue"
import { useI18n } from "vue-i18n"
import { storeToRefs } from "pinia"
import { useFollowStore } from "@/stores/follow"
import { useUserSearchStore } from "@/stores/userSearch"
import { useDebounce } from "../network/composables/useDebounce"
import AvatarUser from "@/components/shared/AvatarUser.vue"
import FollowButton from "@/modules/network/net/FollowButton.vue"
import UserRow from "@/modules/network/net/UserRow.vue"
import SearchBar from "@/modules/network/net/SearchBar.vue"
import UserSearchList from "@/modules/network/net/UserSearchList.vue"
import ConfirmDialog from "@/components/ui/ConfirmDialog.vue"

type TabKey = "friends" | "suggestions" | "requests"
const { t } = useI18n()

const tabs = [
  { key: "friends", label: t('follow.tabs.friends') },
  { key: "suggestions", label: t('follow.tabs.suggestions') },
  { key: "requests", label: t('follow.tabs.requests') },
] as const

const active = ref<TabKey>("friends")

const followStore = useFollowStore()
const { friends, suggestions, loading, incomingRequests } = storeToRefs(followStore)
const friendsCount = computed(() => friends.value.length)

const searchStore = useUserSearchStore()
const { results, loading: searchLoading, hasMore } = storeToRefs(searchStore)
const q = ref("")

const runSearch = useDebounce(async (value: string) => {
  await searchStore.search(value)
}, 350)

watch(q, (val) => runSearch(val))

const confirmUnfollowId = ref<number|string|null>(null)
const confirmRejectId = ref<number|string|null>(null)
const acceptingId = ref<number|string|null>(null)
const rejectingId = ref<number|string|null>(null)

async function bootstrap() {
  await followStore.ensureMe()
  await Promise.all([
    followStore.loadFollowers(),
    followStore.loadFollowings(),
    followStore.loadSuggestions(),
    followStore.loadIncomingRequests(),
    followStore.loadOutgoingRequests?.(),
  ])
  if (friends.value.length === 0 && suggestions.value.length > 0) {
    active.value = "suggestions"
  }
}
onMounted(bootstrap)

function askUnfollow(userId: number | string) {
  confirmUnfollowId.value = userId
}
async function onConfirmUnfollow() {
  if (confirmUnfollowId.value == null) return
  await followStore.unfollow(confirmUnfollowId.value)
  confirmUnfollowId.value = null
}

async function onAccept(requestId: number | string) {
  if (acceptingId.value) return
  acceptingId.value = requestId
  try { await followStore.acceptRequest(requestId) }
  finally { acceptingId.value = null }
}

function askReject(requestId: number | string) {
  confirmRejectId.value = requestId
}
async function onConfirmReject() {
  if (confirmRejectId.value == null) return
  rejectingId.value = confirmRejectId.value
  try { await followStore.rejectRequest(confirmRejectId.value) }
  finally {
    confirmRejectId.value = null
    rejectingId.value = null
  }
}
</script>

<template>
  <div class="w-full">
    <div class="p-4 pb-2">
      <SearchBar
        v-model="q"
        :placeholder="t('follow.search.placeholder')"
      />
    </div>

    <div
      v-if="q.trim().length >= 2"
      class="px-4 pb-4"
    >
      <UserSearchList
        :results="results"
        :loading="searchLoading"
        :has-more="hasMore"
        @load-more="searchStore.loadMore()"
      />
    </div>

    <template v-else>
      <div class="flex border-b border-zinc-200 dark:border-zinc-800">
        <button
          v-for="tItem in tabs"
          :key="tItem.key"
          :class="[
            'flex-1 py-2 text-sm font-medium text-center transition',
            active === tItem.key
              ? 'text-indigo-600 border-b-2 border-indigo-600 dark:text-indigo-400'
              : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200'
          ]"
          @click="active = tItem.key"
        >
          <span class="inline-flex items-center justify-center gap-2">
            <span>{{ tItem.label }}</span>
            <span
              v-if="tItem.key==='friends' && friendsCount"
              class="min-w-5 h-5 px-1 grid place-items-center text-[10px] rounded-full bg-indigo-600 text-white"
            >
              {{ friendsCount }}
            </span>
          </span>
        </button>
      </div>

      <div class="p-4 space-y-3">
        <div
          v-if="loading"
          class="text-sm text-zinc-500 dark:text-zinc-400"
        >
          {{ t('follow.common.loading') }}
        </div>

        <div v-else-if="active==='friends'">
          <div
            v-if="friends.length===0"
            class="text-sm text-zinc-500 dark:text-zinc-400"
          >
            {{ t('follow.empty.friends') }}
          </div>
          <div
            v-else
            class="space-y-2"
          >
            <UserRow
              v-for="u in friends"
              :key="u.id"
              :user="u"
            >
              <button
                class="px-3 py-1 rounded-lg bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-xs text-zinc-800 dark:text-zinc-100 transition"
                @click="askUnfollow(u.id)"
              >
                {{ t('follow.actions.unfollow') }}
              </button>
            </UserRow>
          </div>
        </div>

        <div v-else-if="active==='suggestions'">
          <div
            v-if="suggestions.length===0"
            class="text-sm text-zinc-500 dark:text-zinc-400"
          >
            {{ t('follow.empty.suggestions') }}
          </div>
          <div
            v-else
            class="space-y-2"
          >
            <UserRow
              v-for="s in suggestions"
              :key="s.id"
              :user="s"
            >
              <FollowButton
                :user-id="s.id"
                :label="t('follow.actions.follow')"
                variant="primary"
              />
            </UserRow>
          </div>
        </div>

        <div v-else-if="active==='requests'">
          <div
            v-if="incomingRequests.length===0"
            class="text-sm text-zinc-500 dark:text-zinc-400"
          >
            {{ t('follow.empty.requests') }}
          </div>
          <div
            v-else
            class="space-y-2"
          >
            <div
              v-for="r in incomingRequests"
              :key="r.id"
              class="flex items-center gap-3 p-3 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/60"
            >
              <AvatarUser
                :src="r.actor?.avatar"
                :name="r.actor?.name"
                :color="r.actor?.avatar_color"
                size="md"
              />
              <div class="flex-1 min-w-0">
                <p class="font-medium text-zinc-900 dark:text-zinc-100 truncate">
                  {{ r.actor?.name }}
                </p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate">
                  @{{ r.actor?.slug }} {{ t('follow.text.wantsToFollowYou') }}
                </p>
              </div>

              <div class="flex items-center gap-2">
                <button
                  class="px-3 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs transition disabled:opacity-70"
                  :disabled="acceptingId === r.id || rejectingId === r.id"
                  @click="onAccept(r.id)"
                >
                  <span v-if="acceptingId === r.id">{{ t('follow.actions.accepting') }}</span>
                  <span v-else>{{ t('follow.actions.accept') }}</span>
                </button>

                <button
                  class="px-3 py-1 rounded-lg bg-rose-600 hover:bg-rose-500 text-white text-xs transition disabled:opacity-70"
                  :disabled="acceptingId === r.id || rejectingId === r.id"
                  @click="askReject(r.id)"
                >
                  <span v-if="rejectingId === r.id">{{ t('follow.actions.rejecting') }}</span>
                  <span v-else>{{ t('follow.actions.reject') }}</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <div
          v-else
          class="text-sm text-zinc-500 dark:text-zinc-400"
        >
          {{ t('follow.common.nothing') }}
        </div>
      </div>
    </template>

    <ConfirmDialog
      :open="confirmUnfollowId !== null"
      :title="t('follow.confirm.unfollowTitle') || 'Unfollow user?'"
      :message="t('follow.confirm.unfollowText') || 'They will be removed from your friends list.'"
      :cancel-text="t('common.cancel') || 'Cancel'"
      :confirm-text="t('follow.actions.unfollow') || 'Unfollow'"
      variant="danger"
      @cancel="confirmUnfollowId = null"
      @confirm="onConfirmUnfollow"
    />

    <ConfirmDialog
      :open="confirmRejectId !== null"
      :title="t('follow.confirm.rejectTitle') || 'Reject request?'"
      :message="t('follow.confirm.rejectText') || 'You can’t undo this action.'"
      :cancel-text="t('common.cancel') || 'Cancel'"
      :confirm-text="t('follow.actions.reject') || 'Reject'"
      variant="danger"
      @cancel="confirmRejectId = null"
      @confirm="onConfirmReject"
    />
  </div>
</template>
