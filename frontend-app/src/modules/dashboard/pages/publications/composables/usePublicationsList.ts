import { ref, reactive, computed, watch, onMounted } from "vue";
import { listPublications, deletePublication } from "../api";
import type { Publication } from "../types";

type OrderBy = "publish_at" | "created_at" | "updated_at";
type OrderDir = "asc" | "desc";

export function usePublicationsList(init?: {
  q?: string;
  published?: boolean;
  published_from?: string;
  published_to?: string;
  order_by?: OrderBy;
  order_dir?: OrderDir;
  page?: number;
  per_page?: number;
}) {
  const items = ref<Publication[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);

  const meta = reactive({
    current_page: 1,
    per_page: init?.per_page ?? 15,
    total: 0,
    last_page: 1,
  });

  const filters = reactive({
    q: init?.q ?? "",
    published: init?.published ?? undefined as boolean | undefined,
    published_from: init?.published_from ?? undefined as string | undefined,
    published_to: init?.published_to ?? undefined as string | undefined,
    order_by: init?.order_by ?? "publish_at" as OrderBy,
    order_dir: init?.order_dir ?? "desc" as OrderDir,
    page: init?.page ?? 1,
    per_page: init?.per_page ?? 15,
  });

  const hasNext = computed(() => meta.current_page < meta.last_page);
  const hasPrev = computed(() => meta.current_page > 1);

  async function fetchList() {
    loading.value = true;
    error.value = null;
    try {
      const res = await listPublications({
        page: filters.page,
        per_page: filters.per_page,
        q: filters.q || undefined,
        published: filters.published,
        published_from: filters.published_from,
        published_to: filters.published_to,
        order_by: filters.order_by,
        order_dir: filters.order_dir,
      });
      items.value = res.data;
      meta.current_page = res.meta.current_page;
      meta.per_page = res.meta.per_page;
      meta.total = res.meta.total;
      meta.last_page = res.meta.last_page;
    } catch (e: any) {
      error.value = e?.message || "Failed to load";
    } finally {
      loading.value = false;
    }
  }

  async function refresh() {
    await fetchList();
  }

  function setPage(p: number) {
    filters.page = Math.max(1, p);
  }

  function setPerPage(n: number) {
    filters.per_page = Math.max(1, Math.min(100, n));
    filters.page = 1;
  }

  function setSort(by: OrderBy, dir: OrderDir) {
    filters.order_by = by;
    filters.order_dir = dir;
    filters.page = 1;
  }

  function setPublished(v?: boolean) {
    filters.published = v;
    filters.page = 1;
  }

  function setQuery(q: string) {
    filters.q = q;
    filters.page = 1;
  }

  async function remove(id: number) {
    await deletePublication(id);
    if (items.value.length === 1 && meta.current_page > 1) {
      setPage(meta.current_page - 1);
    }
    await fetchList();
  }

  watch(
    () => [filters.q, filters.published, filters.published_from, filters.published_to, filters.order_by, filters.order_dir, filters.page, filters.per_page],
    () => { fetchList(); },
    { deep: true }
  );

  onMounted(fetchList);

  return {
    items,
    loading,
    error,
    meta,
    filters,
    hasNext,
    hasPrev,
    fetchList,
    refresh,
    setPage,
    setPerPage,
    setSort,
    setPublished,
    setQuery,
    remove,
  };
}
