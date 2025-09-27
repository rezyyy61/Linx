<script setup lang="ts">
import { ref, reactive, computed, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { createAnnouncement, updateAnnouncement } from "../api";
import type { Announcement } from "../types";
import RichTextEditor from "@/modules/dashboard/pages/posts/components/text/RichTextEditor.vue";
import AnnouncementCoverUploader from "@/modules/dashboard/pages/announcements/components/AnnouncementCoverUploader.vue";
import AnnouncementDocsUploader from "@/modules/dashboard/pages/announcements/components/AnnouncementDocsUploader.vue";

const props = withDefaults(defineProps<{
  mode?: "create" | "edit";
  initial?: Partial<Announcement> | null;
}>(), {
  mode: "create",
  initial: null,
});

const emit = defineEmits<{
  (e: "submitted", v: Announcement): void;
  (e: "cancel"): void;
}>();

const { t } = useI18n();
const isEdit = computed(() => props.mode === "edit");
const saving = ref(false);
const submitting = ref(false);

const bodyModel = computed<string | undefined>({
  get: () => form.body ?? undefined,
  set: (v) => { form.body = v ?? null; }
});


const form = reactive({
  title: "",
  body: "" as string | null,
  visibility: "public" as "public" | "members" | "supporters" | "private",
  is_pinned: false,
  publish_at_local: undefined as string | undefined,
});

const coverMediaId = ref<number | null>(null);
const coverMediaUrl = ref<string | null>(null);
const coverCleared = ref(false);
const docsLocal = ref<Array<{ id: number; url: string | null }>>([]);

function hydrateFromInitial(a?: Partial<Announcement> | null) {
  if (!a) return;
  form.title = a.title ?? "";
  form.body = a.body ?? null;
  form.visibility = a.visibility ?? "public";
  form.is_pinned = !!a.is_pinned;
  form.publish_at_local = a.publish_at ? toLocalInput(a.publish_at) : undefined;
  coverMediaId.value = (a as any).cover_id ?? a?.covers?.[0]?.id ?? null;
  coverMediaUrl.value = (a as any).cover_url ?? null;
  docsLocal.value = Array.isArray(a?.documents) ? a!.documents!.map(d => ({ id: d.id, url: d.url ?? null })) : [];
}

onMounted(() => hydrateFromInitial(props.initial || null));

const touched = reactive<{ [k: string]: boolean }>({});
const errors = reactive<Record<string, string | undefined>>({ title: undefined, body: undefined, visibility: undefined });

function touch(k: keyof typeof errors | "title" | "visibility" | "body") {
  touched[k] = true;
  validate();
}

function validate() {
  errors.title = !form.title.trim()
    ? t("announcement.form.title.required") || "Required"
    : form.title.trim().length > 120
      ? t("announcement.form.title.tooLong") || "Too long"
      : undefined;
  const bodyPlain = bodyPlainText.value;
  errors.body = bodyPlain.length > 9000 ? (t("announcement.form.body.tooLong") || "Too long") : undefined;
  errors.visibility = form.visibility ? undefined : (t("announcement.form.visibility.required") || "Required");
}

const isDisabled = computed(() => {
  validate();
  return !!(errors.title || errors.body || errors.visibility);
});

const titleCount = computed(() => form.title.trim().length);
const bodyPlainText = computed(() => {
  const s = form.body || "";
  const withoutTags = s.replace(/<[^>]*>/g, " ");
  return withoutTags.replace(/\s+/g, " ").trim();
});
const bodyPlainLength = computed(() => bodyPlainText.value.length);

const visibilityLabel = computed(() => {
  if (form.visibility === "members") return t("announcement.visibility.members");
  if (form.visibility === "supporters") return t("announcement.visibility.supporters");
  if (form.visibility === "private") return t("announcement.visibility.private");
  return t("announcement.visibility.public");
});
const badgeClass = computed(() => {
  if (form.visibility === "private") return "bg-red-50 text-red-700 border-red-200 dark:bg-red-950/30 dark:text-red-300 dark:border-red-900/40";
  if (form.visibility === "members") return "bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/30 dark:text-blue-300 dark:border-blue-900/40";
  if (form.visibility === "supporters") return "bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/30 dark:text-amber-300 dark:border-amber-900/40";
  return "bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-300 dark:border-emerald-900/40";
});

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

function handleTitleInput() {
  if (!touched.title) return;
  validate();
}

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
function onCoverUpdated() {
  coverCleared.value = false;
}

function onDocsChanged(v: Array<{ id: number; url: string | null }>) {
  docsLocal.value = Array.isArray(v) ? v : [];
}
function onDocsUpdated() {}

const submitText = computed(() =>
  isEdit.value
    ? (t("announcement.actions.update") || "Update announcement")
    : (t("announcement.actions.create") || "Create announcement")
);

async function onSubmit() {
  validate();
  if (isDisabled.value) return;
  submitting.value = true;
  try {
    const payload = {
      title: form.title.trim(),
      body: form.body ?? null,
      visibility: form.visibility,
      is_pinned: !!form.is_pinned,
      publish_at: toUtcIso(form.publish_at_local),
      cover_id: undefined as number | null | undefined,
      documents: docsLocal.value.map((d, i) => ({ id: d.id, order: i })),
    };

    if (isEdit.value && props.initial?.id) {
      if (coverCleared.value) payload.cover_id = null;
      else if (coverMediaId.value) payload.cover_id = coverMediaId.value;
      const res = await updateAnnouncement(props.initial.id, payload);
      emit("submitted", res);
    } else {
      if (coverMediaId.value) payload.cover_id = coverMediaId.value;
      const res = await createAnnouncement(payload);
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
    <div class="flex items-start justify-between gap-4">
      <span
        class="inline-flex items-center rounded-full border px-3 py-1 text-xs"
        :class="badgeClass"
      >
        {{ visibilityLabel }}
      </span>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <div class="lg:col-span-2 space-y-5">
        <div class="space-y-1">
          <label
            for="title"
            class="block text-sm font-medium"
          >{{ t('announcement.form.title.label') }}</label>
          <input
            id="title"
            v-model.trim="form.title"
            type="text"
            dir="auto"
            class="mt-1 w-full rounded-xl border p-2 border-gray-300 focus:outline-none focus:ring-2 focus:ring-emerald-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder-gray-400"
            :placeholder="t('announcement.form.title.placeholder')"
            :aria-invalid="!!errors.title || undefined"
            :aria-describedby="errors.title ? 'title-error' : undefined"
            @input="handleTitleInput"
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
            for="body"
            class="block text-sm font-medium"
          >{{ t('announcement.form.body.label') }}</label>
          <RichTextEditor
            v-model="bodyModel"
            :placeholder="t('announcement.form.body.placeholder')"
            class="rich-text-editor mt-1"
          />
          <div class="flex items-center justify-between text-xs">
            <p
              v-if="errors.body"
              id="body-error"
              class="text-red-600"
            >
              {{ errors.body }}
            </p>
            <p class="text-muted-foreground ml-auto">
              {{ bodyPlainLength }}/9000
            </p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="space-y-1">
            <label
              for="visibility"
              class="block text-sm font-medium"
            >{{ t('announcement.form.visibility.label') }}</label>
            <select
              id="visibility"
              v-model="form.visibility"
              class="mt-1 w-full rounded-xl border p-2 border-gray-300 focus:outline-none focus:ring-2 focus:ring-emerald-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
              @blur="touch('visibility')"
            >
              <option value="public">
                {{ t('announcement.visibility.public') }}
              </option>
              <option value="members">
                {{ t('announcement.visibility.members') }}
              </option>
              <option value="supporters">
                {{ t('announcement.visibility.supporters') }}
              </option>
              <option value="private">
                {{ t('announcement.visibility.private') }}
              </option>
            </select>
          </div>

          <div class="space-y-1">
            <label
              for="publish_at"
              class="block text-sm font-medium"
            >{{ t('announcement.form.publishAt.label') }}</label>
            <input
              id="publish_at"
              v-model="form.publish_at_local"
              type="datetime-local"
              class="mt-1 w-full rounded-xl border p-2 border-gray-300 focus:outline-none focus:ring-2 focus:ring-emerald-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
            >
            <p class="text-xs text-muted-foreground">
              {{ t('announcement.form.publishAt.help') }}
            </p>
          </div>

          <div class="space-y-1 md:pt-6">
            <div class="flex items-center gap-3">
              <input
                id="is_pinned"
                v-model="form.is_pinned"
                type="checkbox"
                class="h-4 w-4 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-emerald-500"
              >
              <label
                for="is_pinned"
                class="text-sm"
              >{{ t('announcement.form.pinned.label') }}</label>
            </div>
          </div>
        </div>
      </div>

      <div class="space-y-4">
        <AnnouncementCoverUploader
          :announcement-id="isEdit ? (props.initial?.id ?? undefined) : undefined"
          :current-cover-url="coverMediaUrl ?? undefined"
          :current-cover-id="coverMediaId ?? undefined"
          @selected="onCoverSelected"
          @cleared="onCoverCleared"
          @updated="onCoverUpdated"
        />

        <AnnouncementDocsUploader
          :announcement-id="isEdit ? (props.initial?.id ?? undefined) : undefined"
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
        :class="submitting || saving || isDisabled ? 'bg-green-500' : (isEdit ? 'bg-primary-600 hover:bg-primary-700' : 'bg-green-600 hover:bg-green-700')"
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
          {{ t('announcement.common.saving') }}
        </span>
      </button>
      <button
        type="button"
        class="rounded-2xl px-4 py-2.5 text-sm border text-foreground hover:bg-foreground/5 border-gray-300 dark:border-gray-700"
        @click="$emit('cancel')"
      >
        {{ t('announcement.actions.cancel') }}
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
