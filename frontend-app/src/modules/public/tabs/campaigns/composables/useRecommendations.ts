import { ref, watchEffect } from "vue"
import { fetchCampaigns } from "../api/campaigns"
import type { CampaignPublic } from "../api/types"

export function useRecommendations(source?: () => CampaignPublic | null, limit = 6) {
  const items = ref<CampaignPublic[]>([])
  const loading = ref(false)

  async function fetch() {
    const base = source?.() || null
    loading.value = true
    try {
      const { data } = await fetchCampaigns({
        per_page: 50,
        status: "published",
        order_by: "publish_at",
        order_dir: "desc"
      } as any)
      const pool = data.filter(c =>
        (!base || c.id !== base.id) &&
        (!base || c.kind === base.kind || c.visibility === base.visibility)
      )
      items.value = pool.slice(0, limit)
    } finally {
      loading.value = false
    }
  }

  watchEffect(fetch)
  return { items, loading, refresh: fetch }
}
