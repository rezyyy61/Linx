<script setup lang="ts">
import { ref, computed } from "vue"
import { useCampaignActions } from "../../composables/useCampaignActions"
import type { VolunteerCampaign } from "../../api/types"
import VolunteerSignupModal from "@/modules/public/tabs/campaigns/modals/VolunteerSignupModal.vue";

const props = defineProps<{ campaign: VolunteerCampaign }>()
const emit = defineEmits<{(e:"updated", v:VolunteerCampaign):void}>()

const volOpen = ref(false)
const { volunteer } = useCampaignActions()

const need = computed(() => Number(props.campaign.needed_slots || 0))
const filled = computed(() => Number(props.campaign.filled_slots || 0))
const pct = computed(() => need.value ? Math.min(100, Math.round((filled.value / need.value) * 100)) : 0)

async function onVolunteer(v:{name:string;email:string;role?:string|null}) {
  const updated = await volunteer({ campaignId: props.campaign.id, ...v })
  volOpen.value = false
  emit("updated", updated as VolunteerCampaign)
}
</script>

<template>
  <div class="space-y-3">
    <div
      v-if="need"
      class="space-y-1"
    >
      <div class="h-2 w-full rounded-full bg-neutral-200 dark:bg-neutral-800 overflow-hidden">
        <div
          class="h-2 rounded-full bg-amber-600 transition-all"
          :style="{ width: pct + '%' }"
        />
      </div>
      <div class="flex items-center justify-between text-sm text-neutral-700 dark:text-neutral-300">
        <span>{{ filled.toLocaleString() }} / {{ need.toLocaleString() }} volunteers</span>
        <span>{{ pct }}%</span>
      </div>
    </div>

    <div class="grid grid-cols-2 gap-2">
      <button
        class="rounded-xl bg-amber-600 text-white py-2.5 text-sm hover:bg-amber-700"
        @click="volOpen=true"
      >
        Volunteer
      </button>
    </div>

    <VolunteerSignupModal
      :open="volOpen"
      @close="volOpen=false"
      @submit="onVolunteer"
    />
  </div>
</template>
