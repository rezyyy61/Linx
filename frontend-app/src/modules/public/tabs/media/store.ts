import { defineStore } from "pinia";
import type { MediaItem } from "./types";
import { fetchMediaList } from "./api";

export const useMediaStore = defineStore("media", {
  state: () => ({ items: [] as MediaItem[], loading: false }),
  actions: {
    async load() {
      this.loading = true;
      try {
        this.items = await fetchMediaList();
      } finally {
        this.loading = false;
      }
    }
  }
});
