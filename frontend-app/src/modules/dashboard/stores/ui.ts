import { defineStore } from "pinia";

export const useUiStore = defineStore("ui", {
  state: () => ({
    sidebarCollapsed: false as boolean,
  }),
  actions: {
    toggleSidebar() {
      this.sidebarCollapsed = !this.sidebarCollapsed;
    },
    setSidebar(collapsed: boolean) {
      this.sidebarCollapsed = collapsed;
    },
  },
});
