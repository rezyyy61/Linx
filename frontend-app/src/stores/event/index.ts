import { defineStore } from "pinia";
import { api } from "@/lib/http";

export type EventSettings = {
  type?: "in_person" | "online" | "hybrid" | null;
  visibility?: "public" | "unlisted" | "private" | null;
  join_url?: string | null;
  join_platform?: string | null;
  join_passcode?: string | null;
  join_instructions?: string | null;
  join_visible_minutes_before?: number | null;
  access_code?: string | null;
  og_title?: string | null;
  og_description?: string | null;
};

export type EventDoc = { id: number; url: string | null };

export type EventItem = {
  id: number;
  title: string;
  description: string | null;
  slug: string;
  timezone: string | null;
  starts_at: string | null;
  ends_at: string | null;
  starts_at_local: string | null;
  ends_at_local: string | null;
  location: string | null;
  capacity: number | null;
  is_published: boolean;
  publish_at: string | null;
  cover_url: string | null;
  cover_id?: number | null;
  documents?: EventDoc[];
  document_ids?: number[];
  settings?: EventSettings | null;
  organizer_id?: number | null;
  organizer?: { id: number; name: string | null } | null;
  created_at: string | null;
  updated_at: string | null;
};

export type EventFilters = {
  q?: string;
  is_published?: boolean;
  starts_from?: string;
  starts_to?: string;
  order_by?: "starts_at" | "created_at" | "updated_at";
  order_dir?: "asc" | "desc";
  per_page?: number;
  page?: number;
};

export type CreateEventPayload = {
  title: string;
  description: string | null;
  starts_at: string | null;
  ends_at: string | null;
  timezone: string;
  location: string | null;
  capacity: number | null;
  is_published: boolean;
  organizer_id: number | null;
  publish_at?: string | null;
  settings?: EventSettings | null;
  cover_id?: number;
  documents?: Array<{ id: number; order?: number }>;
};

export type UpdateEventPayload = Partial<CreateEventPayload>;

type PaginatedMeta = {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
};

type ListResponse = { data: EventItem[]; meta: PaginatedMeta };
type ShowResponse = EventItem;

async function apiList(filters: EventFilters): Promise<ListResponse> {
  const res = await api.get("/events", { params: filters });
  return res.data;
}
async function apiShow(id: number): Promise<ShowResponse> {
  const res = await api.get(`/events/${id}`);
  return res.data;
}
async function apiCreate(payload: CreateEventPayload): Promise<ShowResponse> {
  const res = await api.post("/events", payload);
  return res.data;
}
async function apiUpdate(id: number, payload: UpdateEventPayload): Promise<ShowResponse> {
  const res = await api.put(`/events/${id}`, payload);
  return res.data;
}
async function apiDelete(id: number): Promise<void> {
  await api.delete(`/events/${id}`);
}

export const useEventStore = defineStore("event", {
  state: () => ({
    items: [] as EventItem[],
    meta: null as PaginatedMeta | null,
    current: null as EventItem | null,
    loadingList: false,
    loadingItem: false,
    saving: false,
    filters: {
      per_page: 15,
      order_by: "starts_at",
      order_dir: "desc",
      page: 1,
    } as EventFilters,
  }),
  actions: {
    setFilters(next: EventFilters) {
      this.filters = { ...this.filters, ...next };
    },
    async fetchList() {
      this.loadingList = true;
      try {
        const res = await apiList(this.filters);
        this.items = res.data;
        this.meta = res.meta;
      } finally {
        this.loadingList = false;
      }
    },
    async fetchOne(id: number) {
      this.loadingItem = true;
      try {
        const res = await apiShow(id);
        this.current = res;
        return res;
      } finally {
        this.loadingItem = false;
      }
    },
    async create(payload: CreateEventPayload) {
      this.saving = true;
      try {
        const res = await apiCreate(payload);
        this.current = res;
        return res;
      } finally {
        this.saving = false;
      }
    },
    async update(id: number, payload: UpdateEventPayload) {
      this.saving = true;
      try {
        const res = await apiUpdate(id, payload);
        this.current = res;
        return res;
      } finally {
        this.saving = false;
      }
    },
    async destroy(id: number) {
      await apiDelete(id);
      if (this.current?.id === id) this.current = null;
      this.items = this.items.filter(i => i.id !== id);
    },
    clearCurrent() {
      this.current = null;
    },
  },
});
