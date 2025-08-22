import { defineStore } from "pinia";
import type { PartiesItem } from "./types";
import { fetchPartiesList } from "./api";

export const usePartiesStore = defineStore("parties", {
  state: () => ({ items: [] as PartiesItem[], loading: false }),
  actions: {
    async load() {
      this.loading = true;
      try {
        this.items = await fetchPartiesList();
      } finally {
        this.loading = false;
      }
    },
  },
});
