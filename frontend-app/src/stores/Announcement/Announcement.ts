import { defineStore } from "pinia";
import type { Announcement } from "@/modules/dashboard/pages/announcements/types";
import {
  listAnnouncements,
  getAnnouncement,
  createAnnouncement,
  updateAnnouncement,
  deleteAnnouncement,
  type AnnouncementPayload,
} from "@/modules/dashboard/pages/announcements/api";

type ListParams = Parameters<typeof listAnnouncements>[0];

export const useAnnouncementStore = defineStore("announcementStore", {
  state: () => ({
    items: [] as Announcement[],
    meta: { current_page: 1, per_page: 15, total: 0, last_page: 1 },
    current: null as Announcement | null,
    loading: false,
    saving: false,
    error: null as string | null,
  }),
  actions: {
    async fetchList(params: ListParams = {}) {
      this.loading = true;
      this.error = null;
      try {
        const res = await listAnnouncements(params);
        this.items = res.data;
        this.meta = res.meta;
        return res;
      } catch (e: any) {
        this.error = e?.response?.data?.message || "failed";
        throw e;
      } finally {
        this.loading = false;
      }
    },
    async fetchOne(id: number) {
      this.loading = true;
      this.error = null;
      try {
        const item = await getAnnouncement(id);
        this.current = item;
        return item;
      } catch (e: any) {
        this.error = e?.response?.data?.message || "failed";
        throw e;
      } finally {
        this.loading = false;
      }
    },
    async create(payload: AnnouncementPayload & Required<Pick<AnnouncementPayload, "title">>) {
      this.saving = true;
      this.error = null;
      try {
        const item = await createAnnouncement(payload);
        this.current = item;
        return item;
      } catch (e: any) {
        this.error = e?.response?.data?.message || "failed";
        throw e;
      } finally {
        this.saving = false;
      }
    },
    async update(id: number, payload: AnnouncementPayload) {
      this.saving = true;
      this.error = null;
      try {
        const item = await updateAnnouncement(id, payload);
        this.current = item;
        const idx = this.items.findIndex(x => x.id === id);
        if (idx >= 0) this.items[idx] = item;
        return item;
      } catch (e: any) {
        this.error = e?.response?.data?.message || "failed";
        throw e;
      } finally {
        this.saving = false;
      }
    },
    async remove(id: number) {
      this.saving = true;
      this.error = null;
      try {
        await deleteAnnouncement(id);
        this.items = this.items.filter(x => x.id !== id);
        if (this.current?.id === id) this.current = null;
      } catch (e: any) {
        this.error = e?.response?.data?.message || "failed";
        throw e;
      } finally {
        this.saving = false;
      }
    },
  },
});
