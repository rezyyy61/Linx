import { ref, reactive, computed } from "vue";
import { getPublication, createPublication, updatePublication } from "../api";
import type { Publication } from "../types";
import type { PublicationPayload } from "../api";

type Mode = "create" | "edit";

export function usePublicationForm(opts?: {
  mode?: Mode;
  id?: number | null;
  initial?: Partial<Publication>;
}) {
  const mode = ref<Mode>(opts?.mode ?? "create");
  const id = ref<number | null>(opts?.id ?? null);

  const loading = ref(false);
  const submitting = ref(false);
  const error = ref<string | null>(null);
  const serverErrors = ref<Record<string, string[]>>({});

  const form = reactive<PublicationPayload>({
    title: "",
    issue: "",
    description: null,
    slug: null,
    is_published: false,
    publish_at: null,
    language: "fa",
    cover_id: undefined as unknown as number | null,
    documents: [],
  });

  const cover = ref<{ id: number; url: string | null } | null>(null);
  const coverCleared = ref(false);
  const docs = ref<Array<{ id: number; url: string | null }>>([]);

  const previewCoverUrl = computed(() => cover.value?.url || null);
  const docsForPreview = computed(() => docs.value.slice());

  function patchInitial(p: Partial<Publication>) {
    if (!p) return;
    form.title = p.title ?? "";
    form.issue = p.issue ?? "";
    form.description = p.description ?? null;
    form.slug = p.slug ?? null;
    form.is_published = !!p.is_published;
    form.publish_at = p.publish_at ?? null;
    form.language = p.language ?? "fa";
    cover.value = p.cover_url ? { id: p.cover_id ?? 0, url: p.cover_url } : null;
    docs.value = Array.isArray(p.documents) ? p.documents.map(d => ({ id: d.id, url: d.url })) : [];
  }

  async function load() {
    if (mode.value !== "edit" || !id.value) return;
    loading.value = true;
    error.value = null;
    try {
      const p = await getPublication(id.value);
      patchInitial(p);
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

  function buildPayload(): PublicationPayload {
    const p: PublicationPayload = {
      title: form.title,
      issue: form.issue,
      description: form.description ?? null,
      slug: form.slug ?? null,
      is_published: !!form.is_published,
      publish_at: form.publish_at ?? null,
      language: form.language ?? "fa",
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
        const res = await updatePublication(id.value, payload);
        patchInitial(res);
      } else {
        const res = await createPublication({ title: payload.title || "", issue: payload.issue || "", ...payload });
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
