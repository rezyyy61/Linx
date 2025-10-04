import { ref } from "vue"
import { boostAwareness, donateToCampaign, signPetition, volunteerApply } from "../api/actions"
import type { BoostPayload, DonatePayload, SignPayload, VolunteerPayload, CampaignPublic } from "../api/types"

export function useCampaignActions() {
  const busy = ref(false)
  const error = ref<string | null>(null)
  async function donate(p: DonatePayload): Promise<CampaignPublic> {
    busy.value = true; error.value = null
    try { return await donateToCampaign(p) } finally { busy.value = false }
  }
  async function sign(p: SignPayload): Promise<CampaignPublic> {
    busy.value = true; error.value = null
    try { return await signPetition(p) } finally { busy.value = false }
  }
  async function volunteer(p: VolunteerPayload): Promise<CampaignPublic> {
    busy.value = true; error.value = null
    try { return await volunteerApply(p) } finally { busy.value = false }
  }
  async function boost(p: BoostPayload): Promise<CampaignPublic> {
    busy.value = true; error.value = null
    try { return await boostAwareness(p) } finally { busy.value = false }
  }
  return { busy, error, donate, sign, volunteer, boost }
}
