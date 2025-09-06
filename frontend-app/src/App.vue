<template>
  <a
    href="#app-content"
    class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded-md focus:bg-white focus:px-3 focus:py-2 focus:shadow"
  >Skip to content</a>
  <div id="app-content">
    <RouterView v-slot="{ Component }">
      <transition
        name="fade"
        mode="out-in"
      >
        <component :is="Component" />
      </transition>
      <UiNotifications />
    </RouterView>
  </div>
</template>

<script setup lang="ts">
import { onMounted, onBeforeUnmount, watch } from "vue"
import { useAuthStore } from "@/stores/auth/auth"
import { useFollowStore } from "@/stores/follow"
import { useNotificationsStore } from "@/modules/notifications/store"
import UiNotifications from "@/modules/auth/components/ui/UiNotifications.vue"

const auth = useAuthStore()
const follow = useFollowStore()
const notifications = useNotificationsStore()

onMounted(() => {
  notifications.hydrateFromCache()
  if (auth.user?.id) {
    notifications.onAuth(auth.user.id)
    follow.bindRealtime()
  }
})

watch(() => auth.user?.id, async (id) => {
  if (id) {
    await notifications.onAuth(id)
    await follow.bindRealtime()
  } else {
    notifications.onLogout()
    await follow.unbindRealtime()
  }
}, { immediate: true })

onBeforeUnmount(() => {
  notifications.onLogout()
  follow.unbindRealtime()
})
</script>

<style>
.fade-enter-active,
.fade-leave-active { transition: opacity 150ms ease; }
.fade-enter-from,
.fade-leave-to { opacity: 0; }
</style>
