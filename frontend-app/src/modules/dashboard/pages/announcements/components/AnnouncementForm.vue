<template>
  <form
    class="space-y-4"
    @submit.prevent="submit"
  >
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm mb-1">{{ t('announcement.form.title.label') }}</label>
        <input
          v-model="form.title"
          type="text"
          class="w-full rounded-xl border px-3 py-2"
          :placeholder="t('announcement.form.title.placeholder')"
        >
      </div>
      <div>
        <label class="block text-sm mb-1">{{ t('announcement.form.slug.label') }}</label>
        <input
          v-model="form.slug"
          type="text"
          class="w-full rounded-xl border px-3 py-2"
          :placeholder="t('announcement.form.slug.placeholder')"
        >
      </div>
    </div>

    <div>
      <label class="block text-sm mb-1">{{ t('announcement.form.body.label') }}</label>
      <textarea
        v-model="form.body"
        rows="5"
        class="w-full rounded-xl border px-3 py-2"
        :placeholder="t('announcement.form.body.placeholder')"
      />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div>
        <label class="block text-sm mb-1">{{ t('announcement.form.visibility.label') }}</label>
        <select
          v-model="form.visibility"
          class="w-full rounded-xl border px-3 py-2"
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
      <div>
        <label class="block text-sm mb-1">{{ t('announcement.form.publishAt.label') }}</label>
        <input
          v-model="form.publish_at_local"
          type="datetime-local"
          class="w-full rounded-xl border px-3 py-2"
        >
      </div>
      <div class="flex items-center gap-3 mt-6 md:mt-0">
        <input
          id="is_pinned"
          v-model="form.is_pinned"
          type="checkbox"
          class="h-4 w-4 rounded border"
        >
        <label
          for="is_pinned"
          class="text-sm"
        >{{ t('announcement.form.pinned.label') }}</label>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <AnnouncementCoverUploader
        :announcement-id="isEdit ? props.initial?.id : undefined"
        :current-cover-url="initialCoverUrl"
        @selected="onCoverSelected"
        @cleared="onCoverCleared"
        @updated="onCoverUpdated"
      />
      <AnnouncementDocsUploader
        :announcement-id="isEdit ? props.initial?.id : undefined"
        :initial-docs="initialDocs"
        @changed="onDocsChanged"
        @updated="onDocsUpdated"
      />
    </div>

    <div class="flex items-center gap-2">
      <button
        type="submit"
        :disabled="saving"
        class="rounded-xl px-3 py-2 text-sm bg-primary-600 text-white"
      >
        {{ submitText }}
      </button>
      <button
        type="button"
        class="rounded-xl px-3 py-2 text-sm border"
        @click="$emit('cancel')"
      >
        {{ t('announcement.actions.cancel') }}
      </button>
    </div>
  </form>
</template>

<script setup lang="ts">
import { reactive, computed, watchEffect } from "vue";
import { useI18n } from "vue-i18n";
import AnnouncementCoverUploader from "@/modules/dashboard/pages/announcements/components/AnnouncementCoverUploader.vue";
import AnnouncementDocsUploader from "@/modules/dashboard/pages/announcements/components/AnnouncementDocsUploader.vue";
import type { Announcement } from "@/modules/dashboard/pages/announcements/types";

const { t } = useI18n();
const props = defineProps<{ initial?: Announcement | null; saving?: boolean; mode: "create" | "edit" }>();
const emit = defineEmits<{ (e:"submit", payload: any): void; (e:"cancel"): void }>();

const isEdit = computed(() => props.mode === "edit");

const form = reactive({
  title: "",
  body: "",
  slug: "",
  visibility: "public",
  is_pinned: false,
  publish_at_local: ""
});

const initialCoverUrl = computed(() => props.initial?.cover_url ?? props.initial?.covers?.[0]?.url ?? null);
const initialDocs = computed(() => (props.initial?.documents ?? []).map(d => ({ id: d.id, url: d.url ?? null })));

let pendingCoverId: number | null = null;
let pendingDocs: Array<{ id:number; url:string|null }> = [];

function onCoverSelected(v:{ id:number; url:string|null }){ pendingCoverId = v.id; }
function onCoverCleared(){ pendingCoverId = null; }
function onCoverUpdated(){}

function onDocsChanged(v:Array<{ id:number; url:string|null }>){ pendingDocs = v.slice(); }
function onDocsUpdated(){}

watchEffect(() => {
  const a = props.initial;
  if (!a) return;
  form.title = a.title || "";
  form.body = a.body || "";
  form.slug = a.slug || "";
  form.visibility = a.visibility || "public";
  form.is_pinned = !!a.is_pinned;
  form.publish_at_local = a.publish_at ? toLocalInput(a.publish_at) : "";
});

const submitText = computed(() => props.mode === "create" ? t("announcement.create.submit") : t("announcement.actions.save"));

function toLocalInput(iso: string){
  const d = new Date(iso);
  const pad = (n:number) => `${n}`.padStart(2, "0");
  return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

function submit(){
  const payload:any = {
    title: form.title.trim(),
    body: form.body?.trim() || null,
    slug: form.slug?.trim() || null,
    visibility: form.visibility,
    is_pinned: !!form.is_pinned,
    publish_at: form.publish_at_local ? new Date(form.publish_at_local).toISOString() : null
  };
  if (!isEdit.value) {
    if (pendingCoverId && pendingCoverId > 0) payload.cover_id = pendingCoverId;
    if (pendingDocs.length) payload.documents = pendingDocs.map((d, i) => ({ id: d.id, order: i }));
  }
  emit("submit", payload);
}
</script>
