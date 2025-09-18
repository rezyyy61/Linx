<template>
  <div class="mb-2">
    <RouterLink
      :to="to"
      class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-200 hover:bg-indigo-100 dark:bg-indigo-900/20 dark:text-indigo-300 dark:ring-indigo-900/40"
      :aria-label="aria"
    >
      <Icon
        icon="mdi:repeat-variant"
        class="h-4 w-4"
      />
      <span>Reposted<span v-if="username"> from @{{ username }}</span></span>
    </RouterLink>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Icon } from '@iconify/vue'
import { RouterLink } from 'vue-router'

const props = defineProps<{
  originalId: number
  username?: string
}>()

const to = computed(() => `/p/${props.originalId}`)
const aria = computed(() => props.username ? `Reposted from @${props.username}` : 'Reposted')
</script>
