import { defineStore } from "pinia";
import type { CampaignsItem } from "./types";
import { fetchCampaignsList } from "./api";

export const useCampaignsStore = defineStore("campaigns", {
  state: () => ({ items: [] as CampaignsItem[], loading: false }),
  actions: {
    async load() {
      this.loading = true;
      try {
        this.items = await fetchCampaignsList();
      } finally {
        this.loading = false;
      }
    },
  },
});
