// /src/modules/dashboard/pages/events/api/events.api.ts
import { api, ensureCsrfCookie } from "@/lib/http";
import type { AxiosResponse } from "axios";
import type { EventItem, EventListFilters, CreateEventPayload, UpdateEventPayload, EventId } from "../types";

type Paginated<T> = {
  data: T[];
  meta: { current_page: number; per_page: number; total: number; last_page: number };
};

export async function listEvents(filters: EventListFilters): Promise<Paginated<EventItem>> {
  const res: AxiosResponse<Paginated<EventItem>> = await api.get("/events", { params: filters });
  return res.data;
}

export async function showEvent(id: EventId): Promise<EventItem> {
  const res: AxiosResponse<EventItem> = await api.get(`/events/${id}`);
  return res.data;
}

export async function createEvent(payload: CreateEventPayload): Promise<EventItem> {
  await ensureCsrfCookie();
  const res: AxiosResponse<EventItem> = await api.post("/events", payload);
  return res.data;
}

export async function updateEvent(id: EventId, payload: UpdateEventPayload): Promise<EventItem> {
  await ensureCsrfCookie();
  const res: AxiosResponse<EventItem> = await api.put(`/events/${id}`, payload);
  return res.data;
}

export async function deleteEvent(id: EventId): Promise<void> {
  await ensureCsrfCookie();
  await api.delete(`/events/${id}`);
}
