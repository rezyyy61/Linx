<script setup lang="ts">
import { Icon } from "@iconify/vue"
import CampaignCard from "../CampaignCard.vue"
import type { CampaignPublic } from "../../api/types"

defineProps<{ items: CampaignPublic[]; loading?: boolean }>()
</script>

<template>
  <div class="space-y-3">
    <div class="flex items-center justify-between">
      <h3 class="text-sm font-semibold text-neutral-800 dark:text-neutral-200">
        You may also like
      </h3>
      <div class="text-xs text-neutral-500 dark:text-neutral-400">
        Similar campaigns
      </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <template v-if="loading">
        <div
          v-for="i in 3"
          :key="i"
          class="h-64 rounded-2xl bg-neutral-100 dark:bg-neutral-800 animate-pulse"
        />
      </template>
      <template v-else-if="!items.length">
        <div class="col-span-full text-sm text-neutral-500 dark:text-neutral-400 flex items-center gap-2">
          <Icon
            icon="mdi:information-outline"
            class="w-4 h-4"
          />
          <span>No recommendations</span>
        </div>
      </template>
      <template v-else>
        <CampaignCard
          v-for="c in items"
          :key="c.id"
          :campaign="c"
        />
      </template>
    </div>
  </div>
</template>
