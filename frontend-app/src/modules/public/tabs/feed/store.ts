import { defineStore } from "pinia";
import type { FeedItem } from "./types";
import { fetchFeedList } from "./api";

export const useFeedStore = defineStore("feed", {
  state: () => ({ items: [] as FeedItem[], loading: false }),
  actions: {
    async load() {
      this.loading = true;
      try {
        this.items = await fetchFeedList();
      } finally {
        this.loading = false;
      }
    },
  },
});
