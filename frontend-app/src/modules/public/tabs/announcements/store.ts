import { defineStore } from "pinia";
import type { AnnouncementsItem } from "./types";
import { fetchAnnouncementsList } from "./api";

export const useAnnouncementsStore = defineStore("announcements", {
  state: () => ({ items: [] as AnnouncementsItem[], loading: false }),
  actions: {
    async load() {
      this.loading = true;
      try {
        this.items = await fetchAnnouncementsList();
      } finally {
        this.loading = false;
      }
    },
  },
});
