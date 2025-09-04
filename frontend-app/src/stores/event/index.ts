import { defineStore } from "pinia";
import { api, ensureCsrfCookie } from "@/lib/http";

export type EventType = "in_person" | "online" | "hybrid";
export type VisibilityMode = "public" | "unlisted" | "private";

export type EventDocument = {
  id: number;
  url: string | null;
};

export type EventSettings = {
  type: EventType;
  visibility: VisibilityMode;
  join_url?: string | null;
  join_platform?: string | null;
  join_passcode?: string | null;
  join_instructions?: string | null;
  join_visible_minutes_before?: number | null;
  access_code?: string | null;
  og_title?: string | null;
  og_description?: string | null;
};

export type MediaRef = { id: number; order?: number };

export type EventItem = {
  id: number;
  title: string;
  description?: string | null;
  starts_at: string;
  ends_at?: string | null;
  starts_at_local?: string | null;
  ends_at_local?: string | null;
  timezone: string;
  publish_at?: string | null;
  slug?: string | null;
  location?: string | null;
  capacity?: number | null;
  is_published: boolean;
  organizer_id?: number | null;
  created_at?: string;
  updated_at?: string;
  cover_url?: string | null;
  og_image_url?: string | null;
  documents?: EventDocument[];
  settings?: EventSettings | null;
};

export type EventListFilters = {
  q?: string;
  is_published?: boolean;
  starts_from?: string;
  starts_to?: string;
  order_by?: "starts_at" | "created_at" | "updated_at";
  order_dir?: "asc" | "desc";
  per_page?: number;
  page?: number;
  type?: EventType;
  visibility?: VisibilityMode;
};

export type PaginationMeta = {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
};

export type PaginatedResponse<T> = {
  data: T[];
  meta: PaginationMeta;
};

export type CreateEventPayload = {
  title: string;
  starts_at: string;
  timezone: string;
  description?: string | null;
  ends_at?: string | null;
  location?: string | null;
  capacity?: number | null;
  is_published?: boolean;
  publish_at?: string | null;
  organizer_id?: number | null;
  slug?: string | null;
  settings?: Partial<EventSettings>;
  cover_id?: number | null;
  covers?: MediaRef[];
  documents?: MediaRef[];
};

export type UpdateEventPayload = Partial<CreateEventPayload>;

function cleanDeep<T>(obj: T): any {
  if (Array.isArray(obj)) {
    return obj.map(cleanDeep).filter((v) => !(v === undefined || v === null || v === ""));
  }
  if (obj && typeof obj === "object") {
    const out: Record<string, any> = {};
    Object.entries(obj as Record<string, any>).forEach(([k, v]) => {
      const cv = cleanDeep(v);
      if (!(cv === undefined || cv === null || cv === "" || (Array.isArray(cv) && cv.length === 0))) {
        out[k] = cv;
      }
    });
    return out;
  }
  return obj;
}

function cleanParams<T extends Record<string, any>>(obj: T): Record<string, any> {
  return cleanDeep(obj);
}

export const useEventStore = defineStore("event", {
  state: () => ({
    items: [] as EventItem[],
    meta: null as PaginationMeta | null,
    current: null as EventItem | null,
    filters: {
      per_page: 15,
      order_by: "starts_at",
      order_dir: "desc",
      page: 1,
    } as EventListFilters,
    loadingList: false,
    loadingOne: false,
    creating: false,
    updating: false,
    deleting: false,
    lastError: null as unknown,
  }),

  actions: {
    setFilters(partial: Partial<EventListFilters>) {
      this.filters = { ...this.filters, ...partial };
    },

    resetFilters() {
      this.filters = {
        per_page: 15,
        order_by: "starts_at",
        order_dir: "desc",
        page: 1,
      };
    },

    async fetchList(overrides?: Partial<EventListFilters>) {
      this.loadingList = true;
      this.lastError = null;
      try {
        const params = cleanParams({ ...this.filters, ...overrides });
        const res = await api.get<PaginatedResponse<EventItem>>("/events", { params });
        this.items = res.data.data;
        this.meta = res.data.meta;
        if (this.meta) this.filters.page = this.meta.current_page;
        return res.data;
      } catch (e) {
        this.lastError = e;
        throw e;
      } finally {
        this.loadingList = false;
      }
    },

    async fetchOne(id: number) {
      this.loadingOne = true;
      this.lastError = null;
      try {
        const res = await api.get<EventItem>(`/events/${id}`);
        this.current = res.data;
        return res.data;
      } catch (e) {
        this.lastError = e;
        throw e;
      } finally {
        this.loadingOne = false;
      }
    },

    async create(payload: CreateEventPayload) {
      this.creating = true;
      this.lastError = null;
      try {
        await ensureCsrfCookie();
        const body = cleanParams(payload);
        const res = await api.post<EventItem>("/events", body);
        this.current = res.data;
        await this.fetchList({ page: 1 });
        return res.data;
      } catch (e) {
        this.lastError = e;
        throw e;
      } finally {
        this.creating = false;
      }
    },

    async update(id: number, payload: UpdateEventPayload) {
      this.updating = true;
      this.lastError = null;
      try {
        await ensureCsrfCookie();
        const body = cleanParams(payload);
        const res = await api.patch<EventItem>(`/events/${id}`, body);
        this.current = res.data;
        const idx = this.items.findIndex((x) => x.id === id);
        if (idx >= 0) this.items[idx] = res.data;
        return res.data;
      } catch (e) {
        this.lastError = e;
        throw e;
      } finally {
        this.updating = false;
      }
    },

    async destroy(id: number) {
      this.deleting = true;
      this.lastError = null;
      try {
        await ensureCsrfCookie();
        await api.delete(`/events/${id}`);
        this.items = this.items.filter((x) => x.id !== id);
        if (this.current?.id === id) this.current = null;
        if (this.items.length === 0 && (this.meta?.current_page || 1) > 1) {
          const prev = (this.meta?.current_page || 2) - 1;
          await this.fetchList({ page: prev });
        }
      } catch (e) {
        this.lastError = e;
        throw e;
      } finally {
        this.deleting = false;
      }
    },
  },
});
