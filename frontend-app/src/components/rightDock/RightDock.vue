<!-- src/components/rightDock/RightDock.vue -->
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
import { ref, computed, watch, onMounted, nextTick } from 'vue'
import { useFollowStore } from '@/stores/follow'
import { storeToRefs } from 'pinia'
import RightDockFab from './RightDockFab.vue'
import RightDockDesktopPanel from './RightDockDesktopPanel.vue'
import RightDockMobileNav from './RightDockMobileNav.vue'
import RightDockMobilePanel from './RightDockMobilePanel.vue'

type TabKey = 'notifications' | 'network' | 'messages' | 'quick'

const open = ref(false)
const tab = ref<TabKey>('notifications')
const showContent = ref(false)

const notifications = ref([
  { id: 1, title: 'New follower', body: '@reza started following you', time: '2m', read: false },
  { id: 2, title: 'Announcement', body: 'Party X posted a new update', time: '1h', read: true },
  { id: 3, title: 'Event near you', body: 'Town hall tomorrow 6PM', time: '3h', read: false }
])
const messages = ref([
  { id: 1, from: '@sara', preview: 'Can we coordinate on the campaign...', time: '5m' },
  { id: 2, from: '@amin', preview: 'I shared your post with the team', time: '42m' }
])
const unreadCount = computed(() => notifications.value.filter(n => !n.read).length)

const followStore = useFollowStore()
const { incomingRequests } = storeToRefs(followStore)
const pendingCount = computed(() => incomingRequests.value.length)
onMounted(async () => {
  try {
    await followStore.loadIncomingRequests()
    await followStore.bindRealtime()
  } catch { /* empty */ }
})

const panelTitle = computed(() => {
  if (tab.value === 'notifications') return 'Notifications'
  if (tab.value === 'network') return 'Network'
  if (tab.value === 'messages') return 'Messages'
  return 'Quick actions'
})

async function toggle(next: TabKey) {
  tab.value = next
  if (!open.value) {
    open.value = true
    await nextTick()
    requestAnimationFrame(() => {
      showContent.value = false
      setTimeout(() => { showContent.value = true }, 120)
    })
  } else {
    open.value = tab.value !== next ? true : false
  }
}

function close() {
  open.value = false
  showContent.value = false
}

function onKey(e: KeyboardEvent) { if (e.key === 'Escape') close() }
watch(open, v => {
  if (typeof window === 'undefined') return
  if (v) window.addEventListener('keydown', onKey)
  else window.removeEventListener('keydown', onKey)
})
</script>

<style scoped>
/* انیمیشن popup برای دسکتاپ */
.dock-pop-enter-from,
.dock-pop-leave-to {
  opacity: 0;
  transform: translateY(8px) scale(0.98);
}
.dock-pop-enter-active,
.dock-pop-leave-active {
  transition: opacity 180ms ease-out, transform 180ms ease-out;
  will-change: opacity, transform;
  transform-origin: 100% 50%;
}

/* انیمیشن fade برای موبایل (overlay/panel) */
.dock-fade-enter-from,
.dock-fade-leave-to { opacity: 0; }
.dock-fade-enter-active,
.dock-fade-leave-active {
  transition: opacity 160ms ease-out;
  will-change: opacity;
}
</style>
