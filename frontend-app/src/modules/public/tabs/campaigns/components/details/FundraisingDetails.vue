<script setup lang="ts">
import { ref, computed } from "vue"
import { useCampaignActions } from "../../composables/useCampaignActions"
import type { FundraisingCampaign } from "../../api/types"
import DonateModal from "@/modules/public/tabs/campaigns/modals/DonateModal.vue";

const props = defineProps<{ campaign: FundraisingCampaign }>()
const emit = defineEmits<{(e:"updated", v:FundraisingCampaign):void}>()

const donateOpen = ref(false)
const { donate } = useCampaignActions()

const hasGoal = computed(() => typeof props.campaign.goal_amount === "number" && (props.campaign.goal_amount || 0) > 0)
const pct = computed(() => {
  const r = props.campaign.raised_amount || 0
  const g = props.campaign.goal_amount || 0
  return g ? Math.min(100, Math.round((r / g) * 100)) : 0
})
const meta = computed(() => {
  const r = props.campaign.raised_amount || 0
  const g = props.campaign.goal_amount || 0
  const cur = props.campaign.goal_currency || ""
  return `${r.toLocaleString()} / ${g.toLocaleString()} ${cur}`.trim()
})

async function onDonate(v:{amount:number;currency?:string|null;name?:string|null;email?:string|null}) {
  const updated = await donate({ campaignId: props.campaign.id, ...v })
  donateOpen.value = false
  emit("updated", updated as FundraisingCampaign)
}
</script>

<template>
  <div class="space-y-3">
    <div
      v-if="hasGoal"
      class="space-y-1"
    >
      <div class="h-2 w-full rounded-full bg-neutral-200 dark:bg-neutral-800 overflow-hidden">
        <div
          class="h-2 rounded-full bg-emerald-600 transition-all"
          :style="{ width: pct + '%' }"
        />
      </div>
      <div class="flex items-center justify-between text-sm text-neutral-700 dark:text-neutral-300">
        <span>{{ meta }}</span>
        <span>{{ pct }}%</span>
      </div>
    </div>

    <div class="grid grid-cols-2 gap-2">
      <button
        class="rounded-xl bg-emerald-600 text-white py-2.5 text-sm hover:bg-emerald-700"
        @click="donateOpen=true"
      >
        Donate
      </button>
    </div>

    <DonateModal
      :open="donateOpen"
      :campaign="campaign"
      @close="donateOpen=false"
      @submit="onDonate"
    />
  </div>
</template>
