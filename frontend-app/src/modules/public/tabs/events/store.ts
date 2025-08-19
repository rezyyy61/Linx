import { defineStore } from "pinia";
import type { EventsItem } from "./types";
import { fetchEventsList } from "./api";

export const useEventsStore = defineStore("events", {
  state: () => ({ items: [] as EventsItem[], loading: false }),
  actions: {
    async load() {
      this.loading = true;
      try {
        this.items = await fetchEventsList();
      } finally {
        this.loading = false;
      }
    }
  }
});
