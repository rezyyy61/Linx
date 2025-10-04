<script setup lang="ts">
import { ref, computed } from "vue"
import { Icon } from "@iconify/vue"
import type { CampaignPublic } from "../api/types"
import { isFundraising, isPetition, isVolunteer } from "../api/types"
import DonateModal from "../modals/DonateModal.vue"
import PetitionSignModal from "../modals/PetitionSignModal.vue"
import VolunteerSignupModal from "../modals/VolunteerSignupModal.vue"
import { useCampaignActions } from "../composables/useCampaignActions"

const props = defineProps<{ campaign: CampaignPublic }>()
const emit = defineEmits<{(e:"updated", v:CampaignPublic):void}>()

const donateOpen = ref(false)
const signOpen = ref(false)
const volOpen = ref(false)

const { donate, sign, volunteer, boost } = useCampaignActions()

const canDonate = computed(() => isFundraising(props.campaign))
const canSign = computed(() => isPetition(props.campaign))
const canVolunteer = computed(() => isVolunteer(props.campaign))

async function doBoost() {
  const updated = await boost({ campaignId: props.campaign.id, channel: "share" })
  emit("updated", updated)
}

async function onDonate(v:{amount:number;currency?:string|null;name?:string|null;email?:string|null}) {
  const updated = await donate({ campaignId: props.campaign.id, ...v })
  donateOpen.value = false
  emit("updated", updated)
}
async function onSign(v:{name:string;email:string}) {
  const updated = await sign({ campaignId: props.campaign.id, ...v })
  signOpen.value = false
  emit("updated", updated)
}
async function onVolunteer(v:{name:string;email:string;role?:string|null}) {
  const updated = await volunteer({ campaignId: props.campaign.id, ...v })
  volOpen.value = false
  emit("updated", updated)
}
</script>

<template>
  <div class="flex items-center justify-between gap-2">
    <div class="flex items-center gap-2">
      <button
        v-if="canDonate"
        class="rounded-xl bg-emerald-600 text-white px-3 py-1.5 text-sm"
        @click="donateOpen=true"
      >
        Donate
      </button>
      <button
        v-if="canSign"
        class="rounded-xl bg-emerald-600 text-white px-3 py-1.5 text-sm"
        @click="signOpen=true"
      >
        Sign
      </button>
      <button
        v-if="canVolunteer"
        class="rounded-xl bg-emerald-600 text-white px-3 py-1.5 text-sm"
        @click="volOpen=true"
      >
        Volunteer
      </button>
    </div>
    <button
      class="rounded-xl border px-3 py-1.5 text-sm dark:border-neutral-700 inline-flex items-center gap-2"
      @click="doBoost"
    >
      <Icon
        icon="mdi:share-variant"
        class="w-4 h-4"
      />
      <span>Share</span>
    </button>

    <DonateModal
      :open="donateOpen"
      :campaign="campaign"
      @close="donateOpen=false"
      @submit="onDonate"
    />
    <PetitionSignModal
      :open="signOpen"
      @close="signOpen=false"
      @submit="onSign"
    />
    <VolunteerSignupModal
      :open="volOpen"
      @close="volOpen=false"
      @submit="onVolunteer"
    />
  </div>
</template>
