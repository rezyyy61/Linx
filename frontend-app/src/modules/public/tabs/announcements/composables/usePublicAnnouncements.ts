import { computed, ref } from "vue"
import { fetchAnnouncements, type AnnouncementPublic } from "../api/api"

export type AnnouncementPublicFilters = {
  q: string
  onlyPinned: boolean
  sort: "latest" | "oldest" | "popular"
}

const PAGE_SIZE = 9

function sortToParams(sort: AnnouncementPublicFilters["sort"]) {
  if (sort === "latest") return { order_by: "publish_at", order_dir: "desc" }
  if (sort === "oldest") return { order_by: "publish_at", order_dir: "asc" }
  return { order_by: "updated_at", order_dir: "desc" } // popular (proxy)
}

export function usePublicAnnouncements() {
  const loading = ref(false)
  const hasMore = ref(true)
  const page = ref(1) // API pages are 1-based
  const list = ref<AnnouncementPublic[]>([])
  const currentFilters = ref<AnnouncementPublicFilters>({
    q: "",
    onlyPinned: false,
    sort: "latest",
  })

  function reset(filters?: AnnouncementPublicFilters) {
    page.value = 1
    hasMore.value = true
    list.value = []
    if (filters) currentFilters.value = { ...filters }
  }

  async function fetchPage() {
    if (loading.value || !hasMore.value) return
    loading.value = true
    try {
      const { q, onlyPinned, sort } = currentFilters.value
      const sortParams = sortToParams(sort)

      const res = await fetchAnnouncements({
        q,
        only_pinned: onlyPinned ? 1 : 0,
        per_page: PAGE_SIZE,
        page: page.value,
        ...sortParams,
      })

      const rows = Array.isArray(res?.data) ? res.data : []
      list.value = list.value.concat(rows)
      hasMore.value = rows.length === PAGE_SIZE
      if (hasMore.value) page.value += 1
    } finally {
      loading.value = false
    }
  }

  return {
    list: computed(() => list.value),
    loading,
    hasMore,
    fetchPage,
    reset,
  }
}
