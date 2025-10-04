import { ref, computed } from "vue"
import { fetchCampaigns } from "../api/campaigns"
import type { CampaignPublic, CampaignQuery, Paged } from "../api/types"

export function useCampaigns(initial: CampaignQuery = {}) {
  const items = ref<CampaignPublic[]>([])
  const meta = ref<Paged<CampaignPublic>["meta"]>({ page: 1, per_page: 12, total: 0, last_page: 1 })
  const loading = ref(false)
  const loadingMore = ref(false)

  const q = ref<CampaignQuery>({
    q: initial.q || "",
    kind: initial.kind || "",
    status: initial.status || "published",
    visibility: initial.visibility || "",
    order_by: initial.order_by || "publish_at",
    order_dir: initial.order_dir || "desc",
    page: initial.page || 1,
    per_page: initial.per_page || 12
  })

  async function fetchList(reset = true) {
    if (reset) loading.value = true
    else loadingMore.value = true
    try {
      const res = await fetchCampaigns(q.value)
      meta.value = res.meta
      if (reset) items.value = res.data
      else items.value = items.value.concat(res.data)
    } finally {
      loading.value = false
      loadingMore.value = false
    }
  }

  function setPage(p: number, append = false) {
    q.value.page = p
    fetchList(!append)
  }

  function reset() {
    q.value = {
      q: "",
      kind: "",
      status: "published",
      visibility: "",
      order_by: "publish_at",
      order_dir: "desc",
      page: 1,
      per_page: 12
    }
    fetchList(true)
  }

  const hasMore = computed(() => (meta.value.page || 1) < (meta.value.last_page || 1))

  async function fetchMore() {
    if (loading.value || loadingMore.value) return
    if (!hasMore.value) return
    q.value.page = (q.value.page || 1) + 1
    await fetchList(false)
  }

  return {
    items, meta, q,
    loading, loadingMore,
    fetchList, fetchMore, setPage, reset,
    hasMore,
    totalPages: computed(() => meta.value.last_page)
  }
}
