import { ref } from "vue"
import type { CampaignRow, CreateCampaignPayload, UpdateCampaignPayload } from "../api/types"
import { createCampaign, getCampaign, updateCampaign, deleteCampaign } from "../api/campaigns"

export function useCampaignEditor() {
  const loading = ref(false)
  const item = ref<CampaignRow | null>(null)

  async function load(id: number) {
    loading.value = true
    try {
      item.value = await getCampaign(id)
    } finally {
      loading.value = false
    }
  }

  async function create(payload: CreateCampaignPayload) {
    loading.value = true
    try {
      item.value = await createCampaign(payload)
      return item.value
    } finally {
      loading.value = false
    }
  }

  async function update(id: number, payload: UpdateCampaignPayload) {
    loading.value = true
    try {
      item.value = await updateCampaign(id, payload)
      return item.value
    } finally {
      loading.value = false
    }
  }

  async function destroy(id: number) {
    loading.value = true
    try {
      await deleteCampaign(id)
    } finally {
      loading.value = false
    }
  }

  return { loading, item, load, create, update, destroy }
}
