<script setup lang="ts">
import { computed } from "vue"
import type { CampaignPublic } from "../../api/types"
const props = defineProps<{ campaign: CampaignPublic }>()
const target = computed(() => Number((props.campaign as any).target_reach || 0))
const current = computed(() => Number((props.campaign as any).current_reach || 0))
const pct = computed(() => target.value ? Math.min(100, Math.round((current.value / target.value) * 100)) : 0)
</script>

<template>
  <div class="space-y-3">
    <div
      v-if="target"
      class="space-y-1"
    >
      <div class="h-2 w-full rounded-full bg-neutral-200 dark:bg-neutral-800 overflow-hidden">
        <div
          class="h-2 rounded-full bg-purple-600"
          :style="{ width: pct + '%' }"
        />
      </div>
      <div class="flex items-center justify-between text-sm text-neutral-600 dark:text-neutral-300">
        <span>{{ current.toLocaleString() }} / {{ target.toLocaleString() }} reach</span>
        <span>{{ pct }}%</span>
      </div>
    </div>
  </div>
</template>
