<template>
  <RightDockFab
    :open="open"
    :tab="tab"
    :unread-count="unreadCount"
    :pending-count="pendingCount"
    @toggle="toggle"
  />

  <transition name="dock-pop">
    <RightDockDesktopPanel
      v-show="open"
      :tab="tab"
      :title="panelTitle"
      :notifications="notifications"
      :messages="messages"
      :show-content="showContent"
      @close="close"
    />
  </transition>

  <RightDockMobileNav
    :open="open"
    :tab="tab"
    :unread-count="unreadCount"
    :pending-count="pendingCount"
    @toggle="toggle"
  />

  <transition name="dock-fade">
    <RightDockMobilePanel
      v-show="open"
      :tab="tab"
      :title="panelTitle"
      :notifications="notifications"
      :messages="messages"
      :show-content="showContent"
      @close="close"
    />
  </transition>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, nextTick } from "vue"
import { useI18n } from "vue-i18n"
import { storeToRefs } from "pinia"
import { useFollowStore } from "@/stores/follow"
import { useNotificationsStore } from "@/modules/notifications/store"
import { relativeTimeI18n } from "@/lib/datetime"
import RightDockFab from "./RightDockFab.vue"
import RightDockDesktopPanel from "./RightDockDesktopPanel.vue"
import RightDockMobileNav from "./RightDockMobileNav.vue"
import RightDockMobilePanel from "./RightDockMobilePanel.vue"

type TabKey = "notifications" | "network" | "messages" | "quick"

const { t } = useI18n()
const open = ref(false)
const tab = ref<TabKey>("notifications")
const showContent = ref(false)

const messages = ref([
  { id: 1, from: "@sara", preview: "Can we coordinate on the campaign...", time: "5m" },
  { id: 2, from: "@amin", preview: "I shared your post with the team", time: "42m" }
])

const followStore = useFollowStore()
const { incomingRequests } = storeToRefs(followStore)
const pendingCount = computed(() => incomingRequests.value.length)

const notifStore = useNotificationsStore()
const { items: notifItemsRaw, unreadCount } = storeToRefs(notifStore)

function i18nTitle(kind: string) {
  const key = `notification.${kind}.title`
  return t(key)
}
function i18nBody(kind: string, username?: string | null) {
  const name = username ? `@${username}` : t("notification.user")
  const key = `notification.${kind}.body`
  return t(key, { name })
}

const notifications = computed(() =>
  notifItemsRaw.value.map(i => {
    const handle =
      (i as any).actor?.slug ??
      (i as any).actor?.username ??
      (i as any).handle ??
      null

    return {
      id: i.id,
      title: i18nTitle(i.kind),
      body: i18nBody(i.kind, handle),
      time: relativeTimeI18n(i.createdAt, t),
      read: i.read,
      avatar: i.avatar,
      href: i.href ?? (handle ? `/u/${handle}` : null),
    }
  })
)


onMounted(async () => {
  try {
    await followStore.loadIncomingRequests()
    await followStore.bindRealtime()
  } catch { /* empty */ }
})

const panelTitle = computed(() => {
  if (tab.value === "notifications") return "Notifications"
  if (tab.value === "network") return "Network"
  if (tab.value === "messages") return "Messages"
  return "Quick actions"
})

async function toggle(next: TabKey) {
  const openingNotifications = (!open.value && next === "notifications") || (open.value && tab.value !== "notifications" && next === "notifications")
  tab.value = next
  if (!open.value) {
    open.value = true
    await nextTick()
    requestAnimationFrame(async () => {
      showContent.value = false
      setTimeout(() => { showContent.value = true }, 120)
      if (openingNotifications) await notifStore.markAll()
    })
  } else {
    open.value = tab.value !== next ? true : false
    if (openingNotifications) await notifStore.markAll()
  }
}

function close() {
  open.value = false
  showContent.value = false
}

function onKey(e: KeyboardEvent) { if (e.key === "Escape") close() }
watch(open, v => {
  if (typeof window === "undefined") return
  if (v) window.addEventListener("keydown", onKey)
  else window.removeEventListener("keydown", onKey)
})
</script>

<style scoped>
.dock-pop-enter-from,
.dock-pop-leave-to { opacity: 0; transform: translateY(8px) scale(0.98); }
.dock-pop-enter-active,
.dock-pop-leave-active { transition: opacity 180ms ease-out, transform 180ms ease-out; will-change: opacity, transform; transform-origin: 100% 50%; }
.dock-fade-enter-from,
.dock-fade-leave-to { opacity: 0; }
.dock-fade-enter-active,
.dock-fade-leave-active { transition: opacity 160ms ease-out; will-change: opacity; }
</style>
