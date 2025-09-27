import { reactive, computed } from "vue";
import type { CreateEventPayload, EventItem } from "@/stores/event";

export function useEventForm(initial?: Partial<EventItem | CreateEventPayload>) {
  const form = reactive<CreateEventPayload>({
    title: (initial as any)?.title ?? "",
    description: (initial as any)?.description ?? null,
    starts_at: (initial as any)?.starts_at ?? "",
    ends_at: (initial as any)?.ends_at ?? null,
    timezone: (initial as any)?.timezone ?? "Europe/Amsterdam",
    location: (initial as any)?.location ?? null,
    capacity: (initial as any)?.capacity ?? null,
    is_published: (initial as any)?.is_published ?? false,
    organizer_id: (initial as any)?.organizer_id ?? null,
    settings: (initial as any)?.settings ?? undefined,
  });

  const errors = computed(() => {
    const e: Record<string, string> = {};
    if (!form.title || !form.title.trim()) e.title = "event.form.errors.titleRequired";
    if (!form.starts_at) e.starts_at = "event.form.errors.startsAtRequired";
    if (form.starts_at && form.ends_at && new Date(form.ends_at) < new Date(form.starts_at)) e.ends_at = "event.form.errors.endsAfterStart";
    if (form.capacity !== null && form.capacity !== undefined && form.capacity < 1) e.capacity = "event.form.errors.capacityMin";
    return e;
  });

  const isValid = computed(() => Object.keys(errors.value).length === 0);

  function buildPayload(opts?: { coverId?: number | null; docs?: Array<{ id: number; order: number }> }) {
    const payload: any = { ...form };
    if (opts?.coverId) payload.cover_id = opts.coverId;
    if (opts?.docs?.length) payload.documents = opts.docs;
    return payload as CreateEventPayload;
  }

  return { form, errors, isValid, buildPayload };
}
