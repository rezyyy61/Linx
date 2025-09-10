<!-- src/modules/dashboard/pages/audience/AudienceLayout.vue -->
<template>
  <div class="p-6 max-w-6xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-lg font-semibold">
        Audience
      </h1>
    </div>

    <div class="flex items-center gap-2 border-b dark:border-slate-700">
      <RouterLink
        :to="{ name:'audience.followers' }"
        :class="tabClass('audience.followers')"
      >
        Friends
      </RouterLink>
      <RouterLink
        :to="{ name:'audience.members' }"
        :class="tabClass('audience.members')"
      >
        Members
      </RouterLink>
      <RouterLink
        :to="{ name:'audience.MyMemberships' }"
        :class="tabClass('audience.MyMemberships')"
      >
        My Memberships
      </RouterLink>
    </div>

    <div class="pt-4">
      <router-view />
    </div>
  </div>
</template>

<script setup lang="ts">
import { useRoute } from 'vue-router'

const route = useRoute()
function isActive(name: string) {
  const n = route.name?.toString() || ''
  return n === name || route.matched.some(m => m.name?.toString() === name)
}
function tabClass(name: string) {
  const base = 'px-3 py-2 text-sm rounded-t-lg'
  const active = 'bg-white dark:bg-slate-900 border-x border-t border-slate-200 dark:border-slate-700 text-slate-900 dark:text-slate-100'
  const idle = 'text-slate-600 dark:text-slate-300 hover:text-slate-900'
  return [base, isActive(name) ? active : idle]
}
</script>
