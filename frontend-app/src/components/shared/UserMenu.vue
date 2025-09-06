<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, watch } from "vue"
import { RouterLink, useRouter } from "vue-router"
import { Icon } from "@iconify/vue"
import { useAuthStore } from "@/stores/auth/auth"
import { useProfileStore } from "@/stores/profile/profile"
import AvatarUser from "@/components/shared/AvatarUser.vue";

const router = useRouter()
const auth = useAuthStore()
const profile = useProfileStore()

const userOpen = ref(false)
const root = ref<HTMLElement | null>(null)

const user = computed(() => auth.user)

const displayName = computed(() => profile.meLite?.name || user.value?.name || "User")
const displayEmail = computed(() => user.value?.email || "")

const avatarUrl = computed<string>(() => profile.meLite?.avatar || "")
const avatarColor = computed<string | null>(() => profile.meLite?.avatar_color ?? null)

function toggleUser() { userOpen.value = !userOpen.value }
async function signOut() {
  await auth.logout()
  userOpen.value = false
  router.push({ name: "home" })
}
function onClickOutside(e: MouseEvent) {
  if (root.value && !root.value.contains(e.target as Node)) userOpen.value = false
}
function onEsc(e: KeyboardEvent) { if (e.key === "Escape") userOpen.value = false }

onMounted(async () => {
  document.addEventListener("click", onClickOutside)
  document.addEventListener("keydown", onEsc)
  if (!profile.meLite && !profile.meLiteLoading) {
    await profile.fetchMeLite()
  }
})
onBeforeUnmount(() => {
  document.removeEventListener("click", onClickOutside)
  document.removeEventListener("keydown", onEsc)
})
watch(() => router.currentRoute.value.fullPath, () => { userOpen.value = false })
</script>

<template>
  <div
    ref="root"
    class="relative"
  >
    <button
      type="button"
      class="inline-flex items-center justify-center rounded-full font-semibold shadow-md ring-2 ring-white/80 dark:ring-zinc-800"
      aria-haspopup="menu"
      :aria-expanded="userOpen ? 'true' : 'false'"
      @click="toggleUser"
    >
      <AvatarUser
        :src="avatarUrl"
        :name="displayName"
        :color="avatarColor"
        size="md"
        rounded="full"
        :ring="false"
        alt="User avatar"
      />
    </button>

    <transition name="menu">
      <div
        v-show="userOpen"
        class="absolute right-0 mt-2 z-50 w-64 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl dark:border-zinc-700 dark:bg-zinc-900"
        role="menu"
      >
        <div class="px-4 py-3 border-b border-gray-100 dark:border-zinc-800">
          <p class="text-sm font-semibold text-gray-900 dark:text-zinc-100 truncate">
            {{ displayName }}
          </p>
          <p
            v-if="displayEmail"
            class="text-xs text-gray-600 dark:text-zinc-400 truncate"
          >
            {{ displayEmail }}
          </p>
        </div>

        <ul class="py-1 text-sm text-gray-700 dark:text-zinc-200">
          <li>
            <RouterLink
              to="/dashboard"
              class="flex items-center gap-2 px-4 py-2 hover:bg-gray-50 dark:hover:bg-zinc-800"
              @click="userOpen = false"
            >
              <Icon
                icon="mdi:view-dashboard-outline"
                class="h-5 w-5"
              />
              <span>Dashboard</span>
            </RouterLink>
          </li>
          <li>
            <RouterLink
              to="/settings"
              class="flex items-center gap-2 px-4 py-2 hover:bg-gray-50 dark:hover:bg-zinc-800"
              @click="userOpen = false"
            >
              <Icon
                icon="mdi:cog-outline"
                class="h-5 w-5"
              />
              <span>Settings</span>
            </RouterLink>
          </li>
          <li>
            <button
              class="w-full text-left flex items-center gap-2 px-4 py-2 hover:bg-gray-50 dark:hover:bg-zinc-800"
              @click="signOut"
            >
              <Icon
                icon="mdi:logout"
                class="h-5 w-5"
              />
              <span>Sign out</span>
            </button>
          </li>
        </ul>
      </div>
    </transition>
  </div>
</template>

<style scoped>
.menu-enter-active,
.menu-leave-active {
  transition: opacity 120ms ease, transform 120ms ease;
  transform-origin: top right;
}
.menu-enter-from,
.menu-leave-to {
  opacity: 0;
  transform: translateY(-4px) scale(0.98);
}
</style>
