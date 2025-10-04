// /src/modules/dashboard/pages/campaigns/composables/useCampaigns.ts
import { ref, watch } from "vue"
import type { CampaignRow, CampaignQuery, Paged } from "../api/types"
import { listCampaigns } from "../api/campaigns"

function sanitize(q: CampaignQuery): CampaignQuery {
  const out: CampaignQuery = {}
  Object.entries(q).forEach(([k, v]) => {
    if (v !== "" && v !== null && v !== undefined) (out as any)[k] = v
  })
  return out
}

export function useCampaigns(initial: CampaignQuery = {}) {
  const items = ref<CampaignRow[]>([])
  const meta = ref<Paged<CampaignRow>["meta"]>({ page: 1, per_page: 15, total: 0, last_page: 1 })
  const loading = ref(false)
  const q = ref<CampaignQuery>({
    q: initial.q || "",
    kind: initial.kind || "",
    status: initial.status || "",
    visibility: initial.visibility || "",
    order_by: initial.order_by || "publish_at",
    order_dir: initial.order_dir || "desc",
    page: initial.page || 1,
    per_page: initial.per_page || 15,
  })

  async function fetchList() {
    loading.value = true
    try {
      const res = await listCampaigns(sanitize(q.value))
      items.value = res.data
      meta.value = res.meta
    } finally {
      loading.value = false
    }
  }

  function setPage(p: number) {
    q.value.page = p
    fetchList()
  }

  function reset() {
    q.value = {
      q: "",
      kind: "",
      status: "",
      visibility: "",
      order_by: "publish_at",
      order_dir: "desc",
      page: 1,
      per_page: 15,
    }
    fetchList()
  }

  let t: ReturnType<typeof setTimeout> | null = null
  watch(
    q,
    (nv, ov) => {
      const keys = ["q", "kind", "status", "visibility", "order_by", "order_dir"] as const
      const changedFilter = ov
        ? keys.some(k => (nv as any)[k] !== (ov as any)[k])
        : true
      if (changedFilter) q.value.page = 1
      if (t) clearTimeout(t)
      t = setTimeout(fetchList, 250)
    },
    { deep: true, immediate: false }
  )

  return { items, meta, q, loading, fetchList, setPage, reset }
}
