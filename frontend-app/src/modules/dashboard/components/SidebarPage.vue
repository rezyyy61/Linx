<script setup lang="ts">
import { computed } from "vue";
import { useRoute } from "vue-router";
import { Icon } from "@iconify/vue";
import { dashboardMenu } from "../constants/menu";
import { useUiStore } from "../stores/ui";

const route = useRoute();
const collapsed = computed(() => useUiStore().sidebarCollapsed);
const toggle = () => useUiStore().toggleSidebar();

const visibleMenu = computed(() => dashboardMenu);

const isActive = (toName?: string) => {
  const n = route.name?.toString();
  if (!toName || !n) return false;
  return n === toName || route.matched.some(r => r.name?.toString() === toName);
};

const linkClass = (item: { toName?: string }) => {
  const base = "group flex items-center gap-3 px-3 py-2 rounded-lg transition-colors text-gray-700 dark:text-gray-200";
  const active = "bg-gray-100 dark:bg-gray-800";
  const hover = "hover:bg-gray-100 dark:hover:bg-gray-800";
  return [base, isActive(item.toName) ? active : hover];
};
</script>

<template>
  <aside :class="['h-screen sticky top-0 border-r transition-all bg-white dark:bg-gray-900/80 dark:text-gray-100 dark:border-gray-800', collapsed ? 'w-20' : 'w-72']">
    <div class="h-16 flex items-center px-4 border-b dark:border-gray-800">
      <button
        class="mr-2"
        @click="toggle"
      >
        <Icon
          icon="solar:sidebar-minimalistic-linear"
          width="22"
        />
      </button>
      <span
        v-if="!collapsed"
        class="font-semibold"
      >Dashboard</span>
    </div>

    <nav class="p-3 space-y-1">
      <RouterLink
        v-for="item in visibleMenu"
        :key="item.label"
        :to="{ name: item.toName }"
        :title="collapsed ? item.label : undefined"
        :class="linkClass(item)"
      >
        <Icon
          :icon="item.icon"
          width="20"
        />
        <span v-if="!collapsed">{{ item.label }}</span>
      </RouterLink>
    </nav>
  </aside>
</template>
