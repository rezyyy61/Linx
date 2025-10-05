<!-- /home/rezyyy/PhpstormProjects/Linx/frontend-app/src/modules/dashboard/pages/publications/components/PublicationForm.vue -->
<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from "vue";
import { useI18n } from "vue-i18n";
import { Icon } from "@iconify/vue";
import { createPublication, updatePublication } from "../api";
import type { Publication } from "../types";
import RichTextEditor from "@/modules/dashboard/pages/posts/components/text/RichTextEditor.vue";
import PublicationCoverUploader from "@/modules/dashboard/pages/publications/components/PublicationCoverUploader.vue";
import PublicationDocsUploader from "@/modules/dashboard/pages/publications/components/PublicationDocsUploader.vue";

const props = withDefaults(defineProps<{
  mode?: "create" | "edit";
  initial?: Partial<Publication> | null;
}>(), {
  mode: "create",
  initial: null,
});

const emit = defineEmits<{
  (e: "submitted", v: Publication): void;
  (e: "cancel"): void;
}>();

const { t } = useI18n();
const isEdit = computed(() => props.mode === "edit");
const saving = ref(false);
const submitting = ref(false);

const descriptionModel = computed<string | undefined>({
  get: () => form.description ?? undefined,
  set: (v) => { form.description = v ?? null; }
});

const form = reactive({
  title: "",
  issue: "",
  description: "" as string | null,
  is_published: false,
  publish_at_local: undefined as string | undefined,
  language: "fa",
});

// ---- Pretty Published control (NOW / SCHEDULE / DRAFT)
type PublishMode = "now" | "schedule" | "draft";
const publishMode = ref<PublishMode>("now");

function toUtcIso(local?: string | null) {
  if (!local) return null;
  const d = new Date(local);
  if (Number.isNaN(d.getTime())) return null;
  return new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString();
}
function toLocalInput(iso: string) {
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return "";
  const pad = (n: number) => String(n).padStart(2, "0");
  const yyyy = d.getFullYear();
  const mm = pad(d.getMonth() + 1);
  const dd = pad(d.getDate());
  const hh = pad(d.getHours());
  const mi = pad(d.getMinutes());
  return `${yyyy}-${mm}-${dd}T${hh}:${mi}`;
}
function nowPlus(minutes = 30) {
  const d = new Date(Date.now() + minutes * 60000);
  const pad = (n: number) => String(n).padStart(2, "0");
  const yyyy = d.getFullYear();
  const mm = pad(d.getMonth() + 1);
  const dd = pad(d.getDate());
  const hh = pad(d.getHours());
  const mi = pad(d.getMinutes());
  return `${yyyy}-${mm}-${dd}T${hh}:${mi}`;
}

function computeInitialPublishMode() {
  if (!form.is_published) return "draft";
  if (form.publish_at_local) {
    const dt = new Date(form.publish_at_local);
    if (!Number.isNaN(dt.getTime()) && dt.getTime() > Date.now()) return "schedule";
  }
  return "now";
}

watch(publishMode, (m) => {
  if (m === "now") {
    form.is_published = true;
    form.publish_at_local = undefined;
  } else if (m === "schedule") {
    form.is_published = true;
    if (!form.publish_at_local) form.publish_at_local = nowPlus(30);
  } else {
    form.is_published = false;
  }
});

watch(
  () => [form.is_published, form.publish_at_local] as const,
  ([pub, when]) => {
    if (!pub) { publishMode.value = "draft"; return; }
    if (when) {
      const dt = new Date(when);
      publishMode.value = (!Number.isNaN(dt.getTime()) && dt.getTime() > Date.now()) ? "schedule" : "now";
    } else {
      publishMode.value = "now";
    }
  }
);

const publishStatus = computed<"published" | "scheduled" | "draft">(() => {
  if (publishMode.value === "draft") return "draft";
  if (publishMode.value === "schedule") return "scheduled";
  return "published";
});
const publishBadgeText = computed(() => {
  if (publishStatus.value === "draft") return t("publication.status.draft") || "Draft";
  if (publishStatus.value === "scheduled") return t("publication.status.scheduled") || "Scheduled";
  return t("publication.status.published") || "Published";
});
const publishBadgeClass = computed(() => {
  if (publishStatus.value === "draft") return "bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700";
  if (publishStatus.value === "scheduled") return "bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/30 dark:text-amber-300 dark:border-amber-900/40";
  return "bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-300 dark:border-emerald-900/40";
});

// ---- media state
const coverMediaId = ref<number | null>(null);
const coverMediaUrl = ref<string | null>(null);
const coverCleared = ref(false);
const docsLocal = ref<Array<{ id: number; url: string | null }>>([]);

function hydrateFromInitial(p?: Partial<Publication> | null) {
  if (!p) return;
  form.title = p.title ?? "";
  form.issue = p.issue ?? "";
  form.description = p.description ?? null;
  form.is_published = !!p.is_published;
  form.publish_at_local = p.publish_at ? toLocalInput(p.publish_at) : undefined;
  form.language = p.language ?? "fa";
  coverMediaId.value = (p as any).cover_id ?? null;
  coverMediaUrl.value = (p as any).cover_url ?? null;
  docsLocal.value = Array.isArray(p?.documents) ? p!.documents!.map(d => ({ id: d.id, url: d.url ?? null })) : [];
  publishMode.value = computeInitialPublishMode();
}

onMounted(() => hydrateFromInitial(props.initial || null));

// ---- validation
const touched = reactive<{ [k: string]: boolean }>({});
const errors = reactive<Record<string, string | undefined>>({
  title: undefined,
  issue: undefined,
  description: undefined,
});

function touch(k: keyof typeof errors | "title" | "issue" | "description") {
  touched[k] = true;
  validate();
}

function validate() {
  errors.title = !form.title.trim()
    ? (t("publication.form.title.required") || "Required")
    : form.title.trim().length > 120
      ? (t("publication.form.title.tooLong") || "Too long")
      : undefined;

  errors.issue = !form.issue.trim()
    ? (t("publication.form.issue.required") || "Required")
    : form.issue.trim().length > 60
      ? (t("publication.form.issue.tooLong") || "Too long")
      : undefined;

  const descPlain = descriptionPlainText.value;
  errors.description = descPlain.length > 9000 ? (t("publication.form.description.tooLong") || "Too long") : undefined;
}

const isDisabled = computed(() => {
  validate();
  return !!(errors.title || errors.issue || errors.description);
});

const titleCount = computed(() => form.title.trim().length);
const issueCount = computed(() => form.issue.trim().length);
const descriptionPlainText = computed(() => {
  const s = form.description || "";
  const withoutTags = s.replace(/<[^>]*>/g, " ");
  return withoutTags.replace(/\s+/g, " ").trim();
});
const descriptionPlainLength = computed(() => descriptionPlainText.value.length);

// media events
function onCoverSelected(v: { id: number; url: string | null } | null) {
  if (v) {
    coverMediaId.value = v.id;
    coverMediaUrl.value = v.url ?? null;
    coverCleared.value = false;
  } else {
    coverMediaId.value = null;
    coverMediaUrl.value = null;
    coverCleared.value = isEdit.value;
  }
}
function onCoverCleared() {
  coverMediaId.value = null;
  coverMediaUrl.value = null;
  coverCleared.value = isEdit.value;
}
function onCoverUpdated() { coverCleared.value = false; }
function onDocsChanged(v: Array<{ id: number; url: string | null }>) { docsLocal.value = Array.isArray(v) ? v : []; }
function onDocsUpdated() {}

const submitText = computed(() =>
  isEdit.value
    ? (t("publication.actions.update") || "Update publication")
    : (t("publication.actions.create") || "Create publication")
);

async function onSubmit() {
  validate();
  if (isDisabled.value) return;
  submitting.value = true;
  try {
    const payload = {
      title: form.title.trim(),
      issue: form.issue.trim(),
      description: form.description ?? null,
      is_published: !!form.is_published,
      publish_at: toUtcIso(form.publish_at_local),
      language: form.language || "fa",
      cover_id: undefined as number | null | undefined,
      documents: docsLocal.value.map((d, i) => ({ id: d.id, order: i })),
    };

    if (isEdit.value && props.initial?.id) {
      if (coverCleared.value) payload.cover_id = null;
      else if (coverMediaId.value) payload.cover_id = coverMediaId.value;
      const res = await updatePublication(props.initial.id, payload);
      emit("submitted", res);
    } else {
      if (coverMediaId.value) payload.cover_id = coverMediaId.value;
      const res = await createPublication(payload);
      emit("submitted", res);
    }
  } finally {
    submitting.value = false;
  }
}
</script>

<template>
  <form
    class="space-y-6"
    novalidate
    @submit.prevent="onSubmit"
  >
    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
      <div class="flex items-center justify-between gap-3">
        <span
          class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs"
          :class="publishBadgeClass"
        >
          <Icon
            :icon="publishStatus==='published' ? 'mdi:check-decagram' : (publishStatus==='scheduled' ? 'mdi:calendar-clock' : 'mdi:file-document-edit-outline')"
            class="w-4 h-4"
          />
          {{ publishBadgeText }}
        </span>

        <div class="inline-flex items-center rounded-full border p-1 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700">
          <button
            type="button"
            class="px-3 py-1.5 text-xs rounded-full"
            :class="publishMode==='now' ? 'bg-emerald-600 text-white shadow' : 'text-gray-700 dark:text-gray-200'"
            @click="publishMode='now'"
          >
            <span class="inline-flex items-center gap-1">
              <Icon
                icon="mdi:check-circle-outline"
                class="w-4 h-4"
              />
              {{ t('publication.form.publish.now') || 'Publish now' }}
            </span>
          </button>
          <button
            type="button"
            class="px-3 py-1.5 text-xs rounded-full"
            :class="publishMode==='schedule' ? 'bg-amber-500 text-white shadow' : 'text-gray-700 dark:text-gray-200'"
            @click="publishMode='schedule'"
          >
            <span class="inline-flex items-center gap-1">
              <Icon
                icon="mdi:calendar-clock"
                class="w-4 h-4"
              />
              {{ t('publication.form.publish.schedule') || 'Schedule' }}
            </span>
          </button>
          <button
            type="button"
            class="px-3 py-1.5 text-xs rounded-full"
            :class="publishMode==='draft' ? 'bg-gray-900 text-white shadow dark:bg-gray-700' : 'text-gray-700 dark:text-gray-200'"
            @click="publishMode='draft'"
          >
            <span class="inline-flex items-center gap-1">
              <Icon
                icon="mdi:file-document-edit-outline"
                class="w-4 h-4"
              />
              {{ t('publication.form.publish.draft') || 'Draft' }}
            </span>
          </button>
        </div>
      </div>

      <div
        v-if="publishMode==='schedule'"
        class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4"
      >
        <div class="space-y-1">
          <label
            for="publish_at"
            class="block text-xs font-medium"
          >
            {{ t('publication.form.publishAt.label') || 'Publish at' }}
          </label>
          <input
            id="publish_at"
            v-model="form.publish_at_local"
            type="datetime-local"
            class="mt-1 w-full rounded-xl border p-2 border-gray-300 focus:outline-none focus:ring-2 focus:ring-emerald-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
          >
          <p class="text-xs text-muted-foreground">
            {{ t('publication.form.publishAt.help') || 'Leave empty to publish immediately or schedule a time' }}
          </p>
        </div>
        <div class="space-y-1">
          <label class="block text-xs font-medium">{{ t('publication.form.publish.note') || 'Note' }}</label>
          <p class="text-xs text-gray-600 dark:text-gray-400">
            {{ t('publication.form.publish.noteText') || 'Scheduled publications become visible once the time is reached.' }}
          </p>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <div class="lg:col-span-2 space-y-5">
        <div class="space-y-1">
          <label
            for="title"
            class="block text-sm font-medium"
          >
            {{ t('publication.form.title.label') || 'Title' }}
          </label>
          <input
            id="title"
            v-model.trim="form.title"
            type="text"
            dir="auto"
            class="mt-1 w-full rounded-xl border p-2 border-gray-300 focus:outline-none focus:ring-2 focus:ring-emerald-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder-gray-400"
            :placeholder="t('publication.form.title.placeholder') || 'Enter title'"
            :aria-invalid="!!errors.title || undefined"
            :aria-describedby="errors.title ? 'title-error' : undefined"
            @input="() => { if (!touched.title) return; validate(); }"
            @blur="touch('title')"
          >
          <div class="flex items-center justify-between text-xs">
            <p
              v-if="errors.title"
              id="title-error"
              class="text-red-600"
            >
              {{ errors.title }}
            </p>
            <p class="text-muted-foreground ml-auto">
              {{ titleCount }}/120
            </p>
          </div>
        </div>

        <div class="space-y-1">
          <label
            for="issue"
            class="block text-sm font-medium"
          >
            {{ t('publication.form.issue.label') || 'Issue' }}
          </label>
          <input
            id="issue"
            v-model.trim="form.issue"
            type="text"
            dir="auto"
            class="mt-1 w-full rounded-xl border p-2 border-gray-300 focus:outline-none focus:ring-2 focus:ring-emerald-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder-gray-400"
            :placeholder="t('publication.form.issue.placeholder') || 'e.g. 160 or 1403/07'"
            :aria-invalid="!!errors.issue || undefined"
            :aria-describedby="errors.issue ? 'issue-error' : undefined"
            @input="() => { if (!touched.issue) return; validate(); }"
            @blur="touch('issue')"
          >
          <div class="flex items-center justify-between text-xs">
            <p
              v-if="errors.issue"
              id="issue-error"
              class="text-red-600"
            >
              {{ errors.issue }}
            </p>
            <p class="text-muted-foreground ml-auto">
              {{ issueCount }}/60
            </p>
          </div>
        </div>

        <div class="space-y-1">
          <label
            for="description"
            class="block text-sm font-medium"
          >
            {{ t('publication.form.description.label') || 'Description' }}
          </label>
          <RichTextEditor
            v-model="descriptionModel"
            :placeholder="t('publication.form.description.placeholder') || 'Write a short description (optional)'"
            class="rich-text-editor mt-1"
          />
          <div class="flex items-center justify-between text-xs">
            <p
              v-if="errors.description"
              id="description-error"
              class="text-red-600"
            >
              {{ errors.description }}
            </p>
            <p class="text-muted-foreground ml-auto">
              {{ descriptionPlainLength }}/9000
            </p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="space-y-1">
            <label
              for="language"
              class="block text-sm font-medium"
            >
              {{ t('publication.form.language.label') || 'Language' }}
            </label>
            <select
              id="language"
              v-model="form.language"
              class="mt-1 w-full rounded-xl border p-2 border-gray-300 focus:outline-none focus:ring-2 focus:ring-emerald-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
            >
              <option value="fa">
                فارسی (fa)
              </option>
              <option value="en">
                English (en)
              </option>
              <option value="ar">
                العربية (ar)
              </option>
              <option value="ku">
                Kurdî (ku)
              </option>
            </select>
          </div>
        </div>
      </div>

      <div class="space-y-4">
        <PublicationCoverUploader
          :publication-id="isEdit ? (props.initial?.id ?? undefined) : undefined"
          :current-cover-url="coverMediaUrl ?? undefined"
          :current-cover-id="coverMediaId ?? undefined"
          @selected="onCoverSelected"
          @cleared="onCoverCleared"
          @updated="onCoverUpdated"
        />

        <PublicationDocsUploader
          :publication-id="isEdit ? (props.initial?.id ?? undefined) : undefined"
          :initial-docs="props.initial?.documents?.map(d => ({ id: d.id, url: d.url ?? null })) ?? []"
          @changed="onDocsChanged"
          @updated="onDocsUpdated"
        />
      </div>
    </div>

    <div class="flex items-center gap-2 pt-2">
      <button
        type="submit"
        :disabled="submitting || saving || isDisabled"
        class="rounded-2xl px-4 py-2.5 text-sm text-white shadow disabled:opacity-60 disabled:cursor-not-allowed"
        :class="submitting || saving || isDisabled ? 'bg-green-500' : ( isEdit ? 'bg-primary-600 hover:bg-primary-700' : 'bg-green-600 hover:bg-green-700')"
        :aria-busy="submitting || saving || undefined"
      >
        <span v-if="!submitting && !saving">{{ submitText }}</span>
        <span
          v-else
          class="inline-flex items-center gap-2"
        >
          <svg
            class="animate-spin h-4 w-4"
            viewBox="0 0 24 24"
          >
            <circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="4"
              fill="none"
            />
            <path
              class="opacity-75"
              fill="currentColor"
              d="M4 12a 8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"
            />
          </svg>
          {{ t('publication.common.saving') || 'Saving...' }}
        </span>
      </button>
      <button
        type="button"
        class="rounded-2xl px-4 py-2.5 text-sm border text-foreground hover:bg-foreground/5 border-gray-300 dark:border-gray-700"
        @click="$emit('cancel')"
      >
        {{ t('publication.actions.cancel') || 'Cancel' }}
      </button>
    </div>
  </form>
</template>

<style scoped>
.text-muted-foreground { color: rgb(113 113 122); }
@media (prefers-color-scheme: dark) {
  .text-muted-foreground { color: rgb(161 161 170); }
}
.rich-text-editor :deep(.ProseMirror),
.rich-text-editor :deep(.ql-editor) { min-height: 100px; }
</style>
