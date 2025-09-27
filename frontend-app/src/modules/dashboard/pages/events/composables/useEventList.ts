import { ref, computed } from "vue";
import { useEventStore } from "@/stores/event";
import { useDateFmt } from "./useDateFmt";

export function useEventList() {
  const store = useEventStore();
  const q = ref<string>(store.filters.q || "");
  const onlyPublished = ref<boolean>(!!store.filters.is_published);
  const perPage = ref<number>(store.filters.per_page || 15);
  const orderBy = ref<"starts_at" | "created_at" | "updated_at">(store.filters.order_by || "starts_at");
  const orderDir = ref<"asc" | "desc">(store.filters.order_dir || "desc");
  const loading = computed(() => store.loadingList);
  const items = computed(() => store.items);
  const meta = computed(() => store.meta);
  const { fmt } = useDateFmt();

  function apply(page = 1) {
    store.setFilters({
      q: q.value?.trim() || undefined,
      is_published: onlyPublished.value ? true : undefined,
      per_page: perPage.value,
      order_by: orderBy.value,
      order_dir: orderDir.value,
      page,
    });
    return store.fetchList();
  }

  function reset() {
    q.value = "";
    onlyPublished.value = false;
    perPage.value = 15;
    orderBy.value = "starts_at";
    orderDir.value = "desc";
    return apply(1);
  }

  function toggleOrder() {
    orderDir.value = orderDir.value === "asc" ? "desc" : "asc";
    return apply(meta.value?.current_page || 1);
  }

  function goPage(p: number) {
    if (!meta.value) return;
    if (p < 1 || p > meta.value.last_page) return;
    return apply(p);
  }

  return {
    q,
    onlyPublished,
    perPage,
    orderBy,
    orderDir,
    loading,
    items,
    meta,
    fmt,
    apply,
    reset,
    toggleOrder,
    goPage,
  };
}
