<template>
  <header class="h-16 bg-white border-b flex items-center justify-between px-4">
    <div class="flex items-center gap-2">
      <button
        class="md:hidden"
        @click="toggle"
      >
        <Icon
          icon="solar:menu-dots-linear"
          width="22"
        />
      </button>
    </div>
    <div class="flex items-center gap-3">
      <Icon
        icon="solar:search-linear"
        width="20"
      />
      <RouterLink
        :to="{ name: 'profile.edit' }"
        class="flex items-center gap-2"
      >
        <Icon
          icon="solar:user-linear"
          width="20"
        />
        <span class="hidden md:inline">{{ userName }}</span>
      </RouterLink>
    </div>
  </header>
</template>

<script setup lang="ts">
import { Icon } from "@iconify/vue";
import { computed } from "vue";
import { useUiStore } from "../stores/ui";
const toggle = () => useUiStore().toggleSidebar();
const auth = (() => {
  // eslint-disable-next-line no-undef
  try { const { useAuthStore } = require("@/stores/auth/auth"); return useAuthStore(); } catch { return null; }
})();
const userName = computed(() => (auth as any)?.user?.name || "User");
</script>
