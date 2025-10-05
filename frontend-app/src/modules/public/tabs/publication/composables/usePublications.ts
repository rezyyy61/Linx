import { ref, computed } from "vue"
import type { Publication, Paginated, PublicationOrderBy, DateRange } from "../types"
import { fetchPublications } from "../api/index"

type Q = {
  page: number
  per_page: number
  q: string
  language: string
  date_range: DateRange
  order_by: PublicationOrderBy
  order_dir: "asc" | "desc"
}

export function usePublications(init?: Partial<Q>) {
  const items = ref<Publication[]>([])
  const loading = ref(false)
  const loadingMore = ref(false)
  const error = ref<string | null>(null)

  const meta = ref({ current_page: 1, per_page: init?.per_page ?? 12, total: 0, last_page: 1 })

  const q = ref<Q>({
    page: 1,
    per_page: init?.per_page ?? 12,
    q: "",
    language: "",
    date_range: "all",
    order_by: init?.order_by ?? "publish_at",
    order_dir: init?.order_dir ?? "desc",
    ...init,
  })

  const hasMore = computed(() => meta.value.current_page < meta.value.last_page)

  async function fetchList(replace = true) {
    if (replace) loading.value = true
    error.value = null
    try {
      const res: Paginated<Publication> = await fetchPublications({
        page: q.value.page,
        per_page: q.value.per_page,
        q: q.value.q || undefined,
        language: q.value.language || undefined,
        order_by: q.value.order_by,
        order_dir: q.value.order_dir,
        date_range: q.value.date_range,
      })
      meta.value = res.meta
      items.value = replace ? res.data : items.value.concat(res.data)
    } catch (e: any) {
      error.value = e?.message || "Failed to load"
    } finally {
      if (replace) loading.value = false
    }
  }

  async function fetchMore() {
    if (loadingMore.value || !hasMore.value) return
    loadingMore.value = true
    try {
      q.value.page += 1
      await fetchList(false)
    } finally {
      loadingMore.value = false
    }
  }

  function reset() {
    q.value = {
      page: 1,
      per_page: init?.per_page ?? 12,
      q: "",
      language: "",
      date_range: "all",
      order_by: init?.order_by ?? "publish_at",
      order_dir: init?.order_dir ?? "desc",
    }
    items.value = []
    meta.value = { current_page: 1, per_page: q.value.per_page, total: 0, last_page: 1 }
  }

  return { items, meta, q, loading, loadingMore, hasMore, fetchList, fetchMore, reset, error }
}
