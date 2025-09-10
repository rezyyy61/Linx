<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <FriendsToolbar
        :search="state.search"
        :order-by="state.orderBy"
        :limit="state.limit"
        :loading="state.loading"
        :selected-count="selectedIds.length"
        @select-all="() => toggleAllFlag(true)"
        @unselect-all="() => toggleAllFlag(false)"
        @update:search="v => state.search = v"
        @update:order-by="v => state.orderBy = v"
        @update:limit="v => { state.limit = v; refresh() }"
        @refresh="() => { refresh(); refreshMemberships() }"
        @bulk-remove="bulkRemove"
        @clear-selection="clearSelection"
      />
    </div>

    <div
      v-if="state.loading"
      class="text-sm text-slate-500 dark:text-slate-400"
    >
      Loading…
    </div>

    <div
      v-else-if="filteredSorted.length === 0"
      class="rounded-2xl border border-dashed p-10 text-center text-sm dark:border-slate-700"
    >
      No friends yet
    </div>

    <div
      v-else
      class="space-y-3"
    >
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
          <input
            type="checkbox"
            :checked="allChecked"
            @change="toggleAll"
          >
          <span>Select all</span>
        </div>
        <div class="text-xs text-slate-600 dark:text-slate-400">
          Showing {{ filteredSorted.length }} friends
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 gap-5">
        <FriendCard
          v-for="p in filteredSorted"
          :key="p.id"
          :profile="p"
          :pinned="pins[p.id] === true"
          :checked="selectedIds.includes(p.id)"
          :membership-status="statusByUserId[p.id] ?? null"
          @toggle-check="toggleCheck(p.id)"
          @toggle-pin="togglePin(p.id)"
          @remove="requestRemove(p.id, p.name)"
          @open-mutuals="mutualsForUserId = p.id"
        />
      </div>
    </div>

    <MutualsModal
      v-if="mutualsForUserId !== null"
      :open="mutualsForUserId !== null"
      :user-id="mutualsForUserId!"
      @close="mutualsForUserId = null"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import FriendCard from './components/FriendCard.vue'
import FriendsToolbar from './components/FriendsToolbar.vue'
import MutualsModal from '@/modules/dashboard/pages/audience/pages/friends/components/MutualsModal.vue'
import { useFriends } from './composables/useFriends'
import type { ProfileLite, Membership, MembershipStatus } from '../../types'
import { useToast } from '@/modules/toast/useToast'
import { listMemberships } from '../../api/members'
import { meLite } from '../../api/follow'

const { state, friends, refresh, removeFriend, pins, togglePin } = useFriends()
const { t } = useI18n()
const toast = useToast()

const selectedIds = ref<number[]>([])
const mutualsForUserId = ref<number|null>(null)

/** ------ عضویت‌ها برای غیرفعال‌کردن Invite ------ */
const ownerId = ref<number|null>(null)
const memberships = ref<Membership[]>([])

/** map: userId -> membership status (accepted/pending/...) */
const statusByUserId = computed<Record<number, MembershipStatus | undefined>>(() => {
  const map: Record<number, MembershipStatus | undefined> = {}
  for (const m of memberships.value) {
    if (m.member_id != null) map[m.member_id] = m.status
  }
  return map
})

async function refreshMemberships() {
  if (!ownerId.value) return
  try {
    memberships.value = await listMemberships(ownerId.value)
  } catch {
    // اختیاری: toast.warning('Failed to load memberships')
  }
}

/** ------ لیست دوستان + فیلتر/سورت ------ */
const filteredSorted = computed<ProfileLite[]>(() => {
  const q = state.search.trim().toLowerCase()
  let arr = [...friends.value]
  if (q) {
    arr = arr.filter(x =>
      (x.name || '').toLowerCase().includes(q) ||
      (x.slug || '').toLowerCase().includes(q)
    )
  }
  if (state.orderBy === 'name') {
    arr.sort((a, b) => (a.name || '').localeCompare(b.name || ''))
  } else if (state.orderBy === 'pinned') {
    arr.sort((a, b) => Number(!!pins.value[b.id]) - Number(!!pins.value[a.id]))
  }
  return arr
})

/** ------ انتخاب‌ها ------ */
const allChecked = computed(
  () => filteredSorted.value.length > 0 &&
    filteredSorted.value.every(x => selectedIds.value.includes(x.id))
)

function toggleAll(e: Event) {
  const on = (e.target as HTMLInputElement).checked
  selectedIds.value = on ? filteredSorted.value.map(x => x.id) : []
}
function toggleAllFlag(on: boolean) {
  selectedIds.value = on ? filteredSorted.value.map(x => x.id) : []
}
function toggleCheck(id: number) {
  const i = selectedIds.value.indexOf(id)
  if (i >= 0) selectedIds.value.splice(i, 1)
  else selectedIds.value.push(id)
}

/** ------ حذف‌ها ------ */
async function removeOne(id: number) {
  try {
    await removeFriend(id)
    selectedIds.value = selectedIds.value.filter(x => x !== id)
    toast.success(t('toast.friend.removedOne'))
    // اگر حذف دوست روی عضویت تاثیر داشت، می‌تونی اینجا memberships رو هم تازه کنی
    await refreshMemberships()
  } catch {
    toast.error(t('toast.friend.removeFailOne'))
  }
}

function requestRemove(id: number, name?: string | null) {
  const who = name || t('toast.friend.unknown')
  toast.confirm(
    t('toast.friend.confirmRemoveOne', { name: who }),
    {
      confirmLabel: t('toast.action.remove'),
      cancelLabel: t('toast.action.cancel'),
      destructive: true,
      onConfirm: () => removeOne(id)
    }
  )
}

async function doBulkRemove(ids: number[]) {
  let ok = 0
  for (const id of ids) {
    try { await removeFriend(id); ok++ } catch { /* ignore */ }
  }
  selectedIds.value = selectedIds.value.filter(x => !ids.includes(x))
  if (ok === ids.length) toast.success(t('toast.friend.removedMany', { n: ok }))
  else toast.warning(t('toast.friend.removePartial', { ok, fail: ids.length - ok }))
  await refreshMemberships()
}

async function bulkRemove() {
  const ids = [...selectedIds.value]
  if (!ids.length) { toast.info(t('toast.friend.noneSelected')); return }
  toast.confirm(
    t('toast.friend.confirmRemoveMany', { n: ids.length }),
    {
      confirmLabel: t('toast.action.remove'),
      cancelLabel: t('toast.action.cancel'),
      destructive: true,
      onConfirm: () => doBulkRemove(ids)
    }
  )
}

function clearSelection() {
  selectedIds.value = []
}

/** ------ شروع ------ */
onMounted(async () => {
  try {
    const me = await meLite()
    ownerId.value = me.data.id
  } finally {
    // هم دوستان و هم عضویت‌ها را باهم بگیر
    await Promise.all([refresh(), refreshMemberships()])
  }
})
</script>
