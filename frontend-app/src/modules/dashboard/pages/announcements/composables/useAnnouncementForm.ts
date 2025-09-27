import { ref, reactive, computed } from "vue";
import { getAnnouncement, createAnnouncement, updateAnnouncement } from "../api";
import type { Announcement } from "../types";
import type { AnnouncementPayload } from "../api";

type Mode = "create" | "edit";

export function useAnnouncementForm(opts?: {
  mode?: Mode;
  id?: number | null;
  initial?: Partial<Announcement>;
}) {
  const mode = ref<Mode>(opts?.mode ?? "create");
  const id = ref<number | null>(opts?.id ?? null);

  const loading = ref(false);
  const submitting = ref(false);
  const error = ref<string | null>(null);
  const serverErrors = ref<Record<string, string[]>>({});

  const form = reactive<AnnouncementPayload>({
    title: "",
    body: null,
    slug: null,
    is_pinned: false,
    visibility: "public",
    publish_at: null,
    cover_id: undefined as unknown as number | null,
    documents: [],
  });

  const cover = ref<{ id: number; url: string | null } | null>(null);
  const coverCleared = ref(false);
  const docs = ref<Array<{ id: number; url: string | null }>>([]);

  const previewCoverUrl = computed(() => cover.value?.url || null);
  const docsForPreview = computed(() => docs.value.slice());

  function patchInitial(a: Partial<Announcement>) {
    if (!a) return;
    form.title = a.title ?? "";
    form.body = a.body ?? null;
    form.slug = a.slug ?? null;
    form.is_pinned = !!a.is_pinned;
    form.visibility = a.visibility ?? "public";
    form.publish_at = a.publish_at ?? null;
    cover.value = a.cover_url ? { id: a.covers?.[0]?.id ?? 0, url: a.cover_url } : null;
    docs.value = Array.isArray(a.documents) ? a.documents.map(d => ({ id: d.id, url: d.url })) : [];
  }

  async function load() {
    if (mode.value !== "edit" || !id.value) return;
    loading.value = true;
    error.value = null;
    try {
      const a = await getAnnouncement(id.value);
      patchInitial(a);
    } catch (e: any) {
      error.value = e?.message || "Failed to load";
    } finally {
      loading.value = false;
    }
  }

  function setCover(v: { id: number; url: string | null } | null) {
    cover.value = v;
    coverCleared.value = mode.value === "edit" && !v ? true : false;
  }

  function setDocs(v: Array<{ id: number; url: string | null }>) {
    docs.value = Array.isArray(v) ? v : [];
  }

  function buildPayload(): AnnouncementPayload {
    const p: AnnouncementPayload = {
      title: form.title,
      body: form.body ?? null,
      slug: form.slug ?? null,
      is_pinned: !!form.is_pinned,
      visibility: form.visibility,
      publish_at: form.publish_at ?? null,
    };
    if (mode.value === "create") {
      if (cover.value?.id) p.cover_id = cover.value.id;
      p.documents = docs.value.map((d, i) => ({ id: d.id, order: i }));
    } else {
      if (coverCleared.value) p.cover_id = null;
      else if (cover.value?.id) p.cover_id = cover.value.id;
      p.documents = docs.value.map((d, i) => ({ id: d.id, order: i }));
    }
    return p;
  }

  async function submit() {
    submitting.value = true;
    error.value = null;
    serverErrors.value = {};
    try {
      const payload = buildPayload();
      if (mode.value === "edit" && id.value) {
        const res = await updateAnnouncement(id.value, payload);
        patchInitial(res);
      } else {
        const res = await createAnnouncement({ title: payload.title || "", ...payload });
        id.value = res.id;
        mode.value = "edit";
        patchInitial(res);
      }
      return true;
    } catch (e: any) {
      const data = e?.response?.data;
      error.value = data?.message || e?.message || "Submit failed";
      if (data?.errors && typeof data.errors === "object") serverErrors.value = data.errors;
      return false;
    } finally {
      submitting.value = false;
    }
  }

  return {
    mode,
    id,
    loading,
    submitting,
    error,
    serverErrors,
    form,
    cover,
    docs,
    previewCoverUrl,
    docsForPreview,
    load,
    setCover,
    setDocs,
    submit,
  };
}
