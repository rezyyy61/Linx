import { defineStore } from "pinia";
import type { ProfilesItem } from "./types";
import { fetchProfilesList } from "./api";

export const useProfilesStore = defineStore("profiles", {
  state: () => ({ items: [] as ProfilesItem[], loading: false }),
  actions: {
    async load() {
      this.loading = true;
      try {
        this.items = await fetchProfilesList();
      } finally {
        this.loading = false;
      }
    },
  },
});
