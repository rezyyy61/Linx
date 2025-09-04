<script setup lang="ts">
import { computed, ref } from "vue";
import { Icon } from "@iconify/vue";
import type { CreateEventPayload } from "@/stores/event";

const props = withDefaults(defineProps<{
  mode?: "create" | "edit";
  payload: CreateEventPayload;
  cover: { id: number; url: string | null } | null;
  docs: Array<{ id: number; url: string | null }>;
  submitting?: boolean;
}>(), { mode: "create", submitting: false });

const emit = defineEmits<{ (e: "submit"): void }>();

const fmtDate = (v: string | null | undefined) => {
  if (!v) return "-";
  const d = new Date(v);
  if (isNaN(d.getTime())) return v;
  return d.toLocaleString();
};

const typeLabel = computed(() => {
  const t = props.payload.settings?.type || "in_person";
  if (t === "online") return "Online";
  if (t === "hybrid") return "Hybrid";
  return "In-person";
});

const visibilityLabel = computed(() => {
  const v = props.payload.settings?.visibility || "public";
  if (v === "private") return "Private";
  if (v === "unlisted") return "Unlisted";
  return "Public";
});

const hasJoin = computed(() => {
  const t = props.payload.settings?.type || "in_person";
  return t === "online" || t === "hybrid";
});

const hasLocation = computed(() => {
  const t = props.payload.settings?.type || "in_person";
  return t === "in_person" || t === "hybrid";
});

const joinPlatformLabel = computed(() => {
  const p = props.payload.settings?.join_platform || "";
  if (p === "zoom") return "Zoom";
  if (p === "google_meet") return "Google Meet";
  if (p === "youtube") return "YouTube";
  if (p === "other") return "Other";
  return "-";
});

const coverUrl = computed(() => props.cover?.url || null);

const filenameFromUrl = (u?: string | null) => {
  if (!u) return "";
  try {
    const p = new URL(u).pathname;
    const base = p.split("/").filter(Boolean).pop() || "";
    return decodeURIComponent(base);
  } catch {
    const base = (u.split("?")[0] || "").split("/").pop() || "";
    return decodeURIComponent(base);
  }
};
const extFromName = (n: string) => {
  const i = n.lastIndexOf(".");
  return i >= 0 ? n.slice(i + 1).toLowerCase() : "";
};
const isImageExt = (e: string) => ["jpg","jpeg","png","webp","gif","bmp","avif"].includes(e);

const docsView = computed(() => {
  return (props.docs || []).map(d => {
    const name = filenameFromUrl(d.url) || `#${d.id}`;
    const ext = extFromName(name);
    const image = isImageExt(ext) && !!d.url;
    return { ...d, name, ext, image };
  });
});

const iconForExt = (e: string) => {
  if (["pdf"].includes(e)) return "mdi:file-pdf-box";
  if (["doc","docx","rtf"].includes(e)) return "mdi:file-word-box";
  if (["xls","xlsx","csv"].includes(e)) return "mdi:file-excel-box";
  if (["ppt","pptx","key"].includes(e)) return "mdi:file-powerpoint";
  if (["zip","rar","7z","gz","tar"].includes(e)) return "mdi:folder-zip";
  if (["txt","md"].includes(e)) return "mdi:file-document-outline";
  return "mdi:file-document";
};

const copiedId = ref<number | null>(null);
async function copyLink(u?: string | null, id?: number) {
  if (!u) return;
  try {
    await navigator.clipboard.writeText(u);
    copiedId.value = id ?? null;
    setTimeout(() => { if (copiedId.value === id) copiedId.value = null; }, 1200);
  } catch { /* empty */ }
}
</script>

<template>
  <div class="space-y-8">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="rounded-2xl border border-gray-200 dark:border-gray-700 p-5 space-y-4 bg-white dark:bg-gray-900">
        <div class="text-sm font-semibold">
          Basics
        </div>
        <dl class="grid grid-cols-3 gap-3 text-sm">
          <dt class="text-gray-500 dark:text-gray-400">
            Title
          </dt>
          <dd class="col-span-2 font-medium">
            {{ props.payload.title || "-" }}
          </dd>

          <dt class="text-gray-500 dark:text-gray-400">
            Description
          </dt>
          <dd class="col-span-2 whitespace-pre-line">
            {{ props.payload.description || "-" }}
          </dd>

          <dt class="text-gray-500 dark:text-gray-400">
            Visibility
          </dt>
          <dd class="col-span-2">
            {{ visibilityLabel }}
          </dd>

          <dt class="text-gray-500 dark:text-gray-400">
            Published
          </dt>
          <dd class="col-span-2">
            {{ props.payload.is_published ? "Yes" : "No" }}
          </dd>

          <dt class="text-gray-500 dark:text-gray-400">
            Publish at
          </dt>
          <dd class="col-span-2">
            {{ fmtDate(props.payload.publish_at as any) }}
          </dd>
        </dl>
      </div>

      <div class="rounded-2xl border border-gray-200 dark:border-gray-700 p-5 space-y-4 bg-white dark:bg-gray-900">
        <div class="text-sm font-semibold">
          Type & Schedule
        </div>
        <dl class="grid grid-cols-3 gap-3 text-sm">
          <dt class="text-gray-500 dark:text-gray-400">
            Type
          </dt>
          <dd class="col-span-2">
            {{ typeLabel }}
          </dd>

          <dt class="text-gray-500 dark:text-gray-400">
            Starts at
          </dt>
          <dd class="col-span-2">
            {{ fmtDate(props.payload.starts_at) }}
          </dd>

          <dt class="text-gray-500 dark:text-gray-400">
            Ends at
          </dt>
          <dd class="col-span-2">
            {{ fmtDate(props.payload.ends_at) }}
          </dd>

          <dt class="text-gray-500 dark:text-gray-400">
            Timezone
          </dt>
          <dd class="col-span-2">
            {{ props.payload.timezone || "-" }}
          </dd>

          <template v-if="hasLocation">
            <dt class="text-gray-500 dark:text-gray-400">
              Location
            </dt>
            <dd class="col-span-2">
              {{ props.payload.location || "-" }}
            </dd>
          </template>

          <template v-if="hasJoin">
            <dt class="text-gray-500 dark:text-gray-400">
              Join URL
            </dt>
            <dd class="col-span-2">
              <a
                v-if="props.payload.settings?.join_url"
                :href="props.payload.settings?.join_url || '#'"
                target="_blank"
                class="text-blue-600 hover:underline"
              >
                {{ props.payload.settings?.join_url }}
              </a>
              <span v-else>-</span>
            </dd>

            <dt class="text-gray-500 dark:text-gray-400">
              Platform
            </dt>
            <dd class="col-span-2">
              {{ joinPlatformLabel }}
            </dd>
          </template>

          <template v-if="props.payload.settings?.visibility === 'private'">
            <dt class="text-gray-500 dark:text-gray-400">
              Access code
            </dt>
            <dd class="col-span-2">
              {{ props.payload.settings?.access_code || "-" }}
            </dd>
          </template>
        </dl>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="rounded-2xl border border-gray-200 dark:border-gray-700 p-5 space-y-4 bg-white dark:bg-gray-900">
        <div class="text-sm font-semibold flex items-center gap-2">
          <span>Cover</span>
        </div>
        <div class="aspect-video w-full rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
          <img
            v-if="coverUrl"
            :src="coverUrl"
            alt="cover"
            class="w-full h-full object-cover"
          >
          <div
            v-else
            class="text-sm text-gray-500"
          >
            No cover
          </div>
        </div>
      </div>

      <div class="rounded-2xl border border-gray-200 dark:border-gray-700 p-5 space-y-4 bg-white dark:bg-gray-900">
        <div class="flex items-center justify-between">
          <div class="text-sm font-semibold">
            Documents
          </div>
          <span
            v-if="docsView.length"
            class="text-xs inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300"
          >
            <Icon
              icon="mdi:file-document"
              class="w-4 h-4"
            />
            {{ docsView.length }}
          </span>
        </div>

        <div
          v-if="docsView.length"
          class="grid grid-cols-1 sm:grid-cols-2 gap-3"
        >
          <div
            v-for="d in docsView"
            :key="d.id"
            class="rounded-xl border border-gray-200 dark:border-gray-700 p-3 bg-white/60 dark:bg-gray-900/60"
          >
            <div class="flex items-start gap-3">
              <div class="h-12 w-12 flex-shrink-0 rounded-lg bg-gray-100 dark:bg-gray-800 overflow-hidden flex items-center justify-center">
                <img
                  v-if="d.image"
                  :src="d.url || ''"
                  alt=""
                  class="h-full w-full object-cover"
                >
                <Icon
                  v-else
                  :icon="iconForExt(d.ext)"
                  class="w-7 h-7 text-gray-500"
                />
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium truncate">
                  {{ d.name }}
                </div>
                <div class="mt-1 text-[11px] inline-flex items-center gap-2">
                  <span class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 uppercase">{{ d.ext || "file" }}</span>
                  <span class="text-gray-500 dark:text-gray-400">#{{ d.id }}</span>
                </div>
                <div class="mt-2 flex items-center gap-2">
                  <a
                    v-if="d.url"
                    :href="d.url"
                    target="_blank"
                    class="inline-flex items-center gap-1.5 text-xs px-2 py-1 rounded border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800"
                  >
                    <Icon
                      icon="mdi:open-in-new"
                      class="w-4 h-4"
                    />
                    Open
                  </a>
                  <button
                    v-if="d.url"
                    type="button"
                    class="inline-flex items-center gap-1.5 text-xs px-2 py-1 rounded border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800"
                    @click="copyLink(d.url, d.id)"
                  >
                    <Icon
                      :icon="copiedId===d.id ? 'mdi:check' : 'mdi:link-variant'"
                      class="w-4 h-4"
                    />
                    {{ copiedId===d.id ? 'Copied' : 'Copy link' }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div
          v-else
          class="text-sm text-gray-500"
        >
          No documents
        </div>
      </div>
    </div>

    <div class="flex justify-end">
      <button
        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 disabled:opacity-50 dark:bg-blue-500 dark:hover:bg-blue-600"
        :disabled="submitting"
        @click="emit('submit')"
      >
        <span>
          {{ props.mode === 'edit'
            ? ($t("event.form.actions.submitEdit") || 'Update event')
            : ($t("event.form.actions.submitCreate") || 'Create event') }}
        </span>
      </button>
    </div>
  </div>
</template>
