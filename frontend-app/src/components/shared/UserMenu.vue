<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, watch } from "vue";
import { RouterLink, useRouter } from "vue-router";
import { Icon } from "@iconify/vue";
import { useAuthStore } from "@/stores/auth/auth";

const router = useRouter();
const auth = useAuthStore();
const userOpen = ref(false);
const root = ref<HTMLElement | null>(null);

const user = computed(() => auth.user);
const initials = computed(() =>
  (user.value?.name || "U")
    .split(" ")
    .map((p) => p[0])
    .join("")
    .slice(0, 2)
    .toUpperCase(),
);
const avatar = computed<string>(() => (user.value as any)?.avatar_url || "");

function toggleUser() {
  userOpen.value = !userOpen.value;
}
async function signOut() {
  await auth.logout();
  userOpen.value = false;
  router.push({ name: "home" });
}
function onClickOutside(e: MouseEvent) {
  if (root.value && !root.value.contains(e.target as Node))
    userOpen.value = false;
}
function onEsc(e: KeyboardEvent) {
  if (e.key === "Escape") userOpen.value = false;
}

onMounted(() => {
  document.addEventListener("click", onClickOutside);
  document.addEventListener("keydown", onEsc);
});
onBeforeUnmount(() => {
  document.removeEventListener("click", onClickOutside);
  document.removeEventListener("keydown", onEsc);
});
watch(
  () => router.currentRoute.value.fullPath,
  () => {
    userOpen.value = false;
  },
);
</script>

<template>
  <div
    ref="root"
    class="relative"
  >
    <button
      type="button"
      class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-tr from-red-500 to-red-700 text-white font-semibold shadow-md ring-2 ring-white/80 dark:ring-zinc-800"
      aria-haspopup="menu"
      :aria-expanded="userOpen ? 'true' : 'false'"
      @click="toggleUser"
    >
      <img
        v-if="avatar"
        :src="avatar"
        alt=""
        class="h-9 w-9 rounded-full object-cover"
      >
      <span
        v-else
        class="text-xs select-none"
      >{{ initials }}</span>
    </button>

    <transition name="menu">
      <div
        v-show="userOpen"
        class="absolute right-0 mt-2 z-50 w-64 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl dark:border-zinc-700 dark:bg-zinc-900"
        role="menu"
      >
        <div class="px-4 py-3 border-b border-gray-100 dark:border-zinc-800">
          <p
            class="text-sm font-semibold text-gray-900 dark:text-zinc-100 truncate"
          >
            {{ user?.name }}
          </p>
          <p class="text-xs text-gray-600 dark:text-zinc-400 truncate">
            {{ user?.email }}
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
  transition:
    opacity 120ms ease,
    transform 120ms ease;
  transform-origin: top right;
}
.menu-enter-from,
.menu-leave-to {
  opacity: 0;
  transform: translateY(-4px) scale(0.98);
}
</style>
