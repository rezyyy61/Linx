<script setup lang="ts">
import { ref, computed } from "vue"
import { useCampaignActions } from "../../composables/useCampaignActions"
import type { PetitionCampaign } from "../../api/types"
import PetitionSignModal from "@/modules/public/tabs/campaigns/modals/PetitionSignModal.vue";

const props = defineProps<{ campaign: PetitionCampaign }>()
const emit = defineEmits<{(e:"updated", v:PetitionCampaign):void}>()

const signOpen = ref(false)
const { sign } = useCampaignActions()

const goal = computed(() => Number(props.campaign.signature_goal || 0))
const count = computed(() => Number(props.campaign.signatures_count || 0))
const pct = computed(() => goal.value ? Math.min(100, Math.round((count.value / goal.value) * 100)) : 0)

async function onSign(v:{name:string;email:string}) {
  const updated = await sign({ campaignId: props.campaign.id, ...v })
  signOpen.value = false
  emit("updated", updated as PetitionCampaign)
}
</script>

<template>
  <div class="space-y-3">
    <div
      v-if="goal"
      class="space-y-1"
    >
      <div class="h-2 w-full rounded-full bg-neutral-200 dark:bg-neutral-800 overflow-hidden">
        <div
          class="h-2 rounded-full bg-indigo-600 transition-all"
          :style="{ width: pct + '%' }"
        />
      </div>
      <div class="flex items-center justify-between text-sm text-neutral-700 dark:text-neutral-300">
        <span>{{ count.toLocaleString() }} / {{ goal.toLocaleString() }} signatures</span>
        <span>{{ pct }}%</span>
      </div>
    </div>

    <div class="grid grid-cols-2 gap-2">
      <button
        class="rounded-xl bg-indigo-600 text-white py-2.5 text-sm hover:bg-indigo-700"
        @click="signOpen=true"
      >
        Sign Petition
      </button>
    </div>

    <PetitionSignModal
      :open="signOpen"
      @close="signOpen=false"
      @submit="onSign"
    />
  </div>
</template>
