import { watchEffect } from "vue";

export function usePageMeta(title: string) {
  watchEffect(() => {
    document.title = `${title} • Dashboard`;
  });
}
