import { ref } from "vue"
import { fetchCampaign } from "../api/campaigns"
import type { CampaignPublic } from "../api/types"

export function useCampaignDetails(slug: string) {
  const item = ref<CampaignPublic | null>(null)
  const loading = ref(false)
  const notFound = ref(false)

  async function fetchOne() {
    loading.value = true
    notFound.value = false
    try {
      const r = await fetchCampaign(slug)
      item.value = r
      if (!r) notFound.value = true
    } finally {
      loading.value = false
    }
  }

  return { item, loading, notFound, fetchOne }
}
