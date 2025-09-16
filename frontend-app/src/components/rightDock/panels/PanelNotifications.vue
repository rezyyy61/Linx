<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from "vue"
import { useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import { useNotificationsStore } from "@/modules/notifications/store"
import { storeToRefs } from "pinia"
import type { NotificationVM } from "@/modules/notifications/types"

import NotificationsHeader from "@/components/rightDock/panels/NotificationsHeader.vue"
import DefaultNotification from "@/modules/notifications/components/DefaultNotification.vue"
import MemberInvited from "@/modules/notifications/components/MemberInvited.vue"
import FollowUnfollowed from "@/modules/notifications/components/FollowUnfollowed.vue"
import FollowAccepted from "@/modules/notifications/components/FollowAccepted.vue"
import FollowRejected from "@/modules/notifications/components/FollowRejected.vue"
import CommentOnPost from "@/modules/notifications/components/CommentOnPost.vue"
import CommentMentioned from "@/modules/notifications/components/CommentMentioned.vue"
import NotificationModalHost from "@/modules/notifications/components/NotificationModalHost.vue"
import { useToast } from "@/modules/toast/useToast"

const { t } = useI18n()
const toast = useToast()

const store = useNotificationsStore()
const { items, hasMore, status } = storeToRefs(store)

const tab = ref<"all"|"unread">("all")
const kind = ref<string>("all")
const router = useRouter()

const kinds = computed(() => {
  const s = new Set<string>()
  for (const it of items.value as any[]) if (it.kind) s.add(it.kind)
  return Array.from(s).sort()
})

const itemsVM = computed<NotificationVM[]>(() => {
  return items.value.filter(n => {
    if (tab.value === "unread" && n.read) return false
    if (kind.value !== "all") { if (!n.kind || n.kind !== kind.value) return false }
    return true
  })
})

const map: Record<string, any> = {
  "member.invited": MemberInvited,
  "follow.unfollowed": FollowUnfollowed,
  "follow.accepted": FollowAccepted,
  "follow.rejected": FollowRejected,
  "comment.on_post": CommentOnPost,
  "comment.mentioned": CommentMentioned,
}
function resolveCmp(kind?: string){ return (kind && map[kind]) || DefaultNotification }

const modalName = ref<string|null>(null)
const modalPayload = ref<any>({})
const pendingNotifId = ref<number|null>(null)

function onOpenModal(p:{name:string;data:any}) {
  modalName.value = p.name
  modalPayload.value = p.data || {}
  pendingNotifId.value = Number(p.data?.originNotifId || 0) || null
}
function closeModal() {
  modalName.value = null
  modalPayload.value = {}
  pendingNotifId.value = null
}

async function onAccepted() {
  if (pendingNotifId.value) {
    try { await store.remove(pendingNotifId.value) }
    catch { await store.markOneAsRead(pendingNotifId.value).catch(()=>{}) }
  }
  closeModal()
  toast.success(t("toast.member.accepted"))
}

async function onRejected() {
  if (pendingNotifId.value) {
    try { await store.remove(pendingNotifId.value) }
    catch { await store.markOneAsRead(pendingNotifId.value).catch(()=>{}) }
  }
  closeModal()
  toast.info(t("toast.member.rejected"))
}

async function onNavigate(to: any){ router.push(to as any) }
async function onMarkRead(id:number){ await store.markOneAsRead(id) }
async function onRemove(id:number){ await store.remove(id) }
async function markAll(){ await store.markAll() }

const sentinel = ref<HTMLElement|null>(null)
let io: IntersectionObserver | null = null
async function onIntersect(entries: IntersectionObserverEntry[]) {
  const entry = entries[0]
  if (!entry.isIntersecting || status.value === "loading" || !hasMore.value) return
  await store.fetchNextPage()
}
function setupIO() {
  if (io) io.disconnect()
  io = new IntersectionObserver(onIntersect, { root: null, rootMargin: "400px 0px 0px 0px", threshold: 0 })
  if (sentinel.value) io.observe(sentinel.value)
}
onMounted(async () => {
  store.hydrateFromCache()
  if (!items.value.length) await store.fetchFirstPage()
  setupIO()
})
onBeforeUnmount(() => { if (io) io.disconnect() })
</script>

<template>
  <div class="space-y-4">
    <NotificationsHeader
      :tab="tab"
      :kind="kind"
      :kinds="kinds"
      @update:tab="tab=$event"
      @update:kind="kind=$event"
      @mark-all="markAll"
    />

    <div
      class="space-y-2"
      role="list"
    >
      <component
        :is="resolveCmp(n.kind)"
        v-for="n in itemsVM"
        :key="n.id"
        :item="n"
        @navigate="onNavigate"
        @open-modal="onOpenModal"
        @mark-read="onMarkRead"
        @remove="onRemove"
      />
    </div>

    <div
      ref="sentinel"
      class="h-8"
    />

    <div
      v-if="status==='loading'"
      class="space-y-2"
    >
      <div
        v-for="i in 3"
        :key="'sk-'+i"
        class="animate-pulse rounded-2xl border border-zinc-200/80 bg-white/70 p-3 dark:border-white/10 dark:bg-zinc-900/50"
      >
        <div class="mb-2 h-4 w-1/3 rounded bg-zinc-200 dark:bg-zinc-700" />
        <div class="h-3 w-2/3 rounded bg-zinc-200 dark:bg-zinc-700" />
      </div>
    </div>

    <div
      v-else-if="!hasMore && itemsVM.length===0"
      class="text-center text-sm text-zinc-600 dark:text-zinc-300"
    >
      {{ $t("notification.empty") }}
    </div>

    <NotificationModalHost
      :name="modalName"
      :payload="modalPayload"
      @close="closeModal"
      @accepted="onAccepted"
      @rejected="onRejected"
    />
  </div>
</template>
