import { defineStore } from "pinia";
import type { BooksItem } from "./types";
import { fetchBooksList } from "./api";

export const useBooksStore = defineStore("books", {
  state: () => ({ items: [] as BooksItem[], loading: false }),
  actions: {
    async load() {
      this.loading = true;
      try {
        this.items = await fetchBooksList();
      } finally {
        this.loading = false;
      }
    }
  }
});
