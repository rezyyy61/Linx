import { computed } from "vue";
import { useRoute } from "vue-router";

export function useBreadcrumbs() {
  const route = useRoute();
  const crumbs = computed(() => {
    const metaCrumbs = (route.meta as any)?.breadcrumbs || [];
    return metaCrumbs as Array<{ label: string; to?: any }>;
  });
  return { crumbs };
}
