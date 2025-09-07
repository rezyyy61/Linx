<template>
  <form
    class="space-y-4"
    @submit.prevent="submit"
  >
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm mb-1 text-slate-700 dark:text-slate-200">{{ t('campaign.form.title.label') }}</label>
        <input
          v-model="form.title"
          type="text"
          class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700"
          :placeholder="t('campaign.form.title.placeholder')"
        >
      </div>
      <div>
        <label class="block text-sm mb-1 text-slate-700 dark:text-slate-200">{{ t('campaign.form.goal.label') }}</label>
        <input
          v-model="form.goal"
          type="text"
          class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700"
          :placeholder="t('campaign.form.goal.placeholder')"
        >
      </div>
    </div>

    <div>
      <label class="block text-sm mb-1 text-slate-700 dark:text-slate-200">{{ t('campaign.form.description.label') }}</label>
      <textarea
        v-model="form.description"
        rows="4"
        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700"
        :placeholder="t('campaign.form.description.placeholder')"
      />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm mb-1 text-slate-700 dark:text-slate-200">{{ t('campaign.form.startsAt.label') }}</label>
        <input
          v-model="form.starts_at_local"
          type="datetime-local"
          class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700"
        >
      </div>
      <div>
        <label class="block text-sm mb-1 text-slate-700 dark:text-slate-200">{{ t('campaign.form.endsAt.label') }}</label>
        <input
          v-model="form.ends_at_local"
          type="datetime-local"
          class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700"
        >
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm mb-1 text-slate-700 dark:text-slate-200">{{ t('campaign.form.status.label') }}</label>
        <select
          v-model="form.status"
          class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700"
        >
          <option value="draft">
            {{ t('campaign.status.draft') }}
          </option>
          <option value="running">
            {{ t('campaign.status.running') }}
          </option>
          <option value="paused">
            {{ t('campaign.status.paused') }}
          </option>
          <option value="ended">
            {{ t('campaign.status.ended') }}
          </option>
        </select>
      </div>
      <div class="flex items-center gap-3 mt-6 md:mt-0">
        <input
          id="donation_enabled"
          v-model="form.donation_enabled"
          type="checkbox"
          class="h-4 w-4 rounded border-slate-300 dark:border-slate-600"
        >
        <label
          for="donation_enabled"
          class="text-sm text-slate-700 dark:text-slate-200"
        >{{ t('campaign.form.donationEnabled.label') }}</label>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <CampaignCoverUploader
        :campaign-id="isEdit ? props.initial?.id : undefined"
        :current-cover-url="initialCoverUrl"
        @selected="onCoverSelected"
        @cleared="onCoverCleared"
        @updated="onCoverUpdated"
      />
      <CampaignDocumentsUploader
        :campaign-id="isEdit ? props.initial?.id : undefined"
        :initial-docs="initialDocs"
        @changed="onDocsChanged"
        @updated="onDocsUpdated"
      />
    </div>

    <div class="flex items-center gap-2">
      <button
        type="submit"
        :disabled="saving"
        class="inline-flex items-center gap-1 rounded-xl px-3 py-2 text-sm bg-primary-600 text-white hover:bg-primary-700 disabled:opacity-60"
      >
        <span>{{ submitText }}</span>
      </button>
      <button
        type="button"
        class="inline-flex items-center gap-1 rounded-xl px-3 py-2 text-sm border border-slate-200 bg-white hover:bg-slate-50 dark:bg-slate-900 dark:border-slate-700 dark:hover:bg-slate-800"
        @click="$emit('cancel')"
      >
        <span>{{ t('campaign.content.actions.cancel') }}</span>
      </button>
    </div>
  </form>
</template>

<script setup lang="ts">
import { reactive, computed, watchEffect } from "vue";
import { useI18n } from "vue-i18n";
import CampaignCoverUploader from "@/modules/dashboard/pages/campaigns/components/CampaignCoverUploader.vue";
import CampaignDocumentsUploader from "@/modules/dashboard/pages/campaigns/components/CampaignDocumentsUploader.vue";
import type { Campaign, CampaignStatus } from "@/modules/dashboard/pages/campaigns/types";

const { t } = useI18n();
const props = defineProps<{ initial?: Campaign | null; saving?: boolean; mode: "create" | "edit" }>();
const emit = defineEmits<{ (e:"submit", payload: any): void; (e:"cancel"): void }>();

const isEdit = computed(() => props.mode === "edit");

const form = reactive({
  title: "",
  goal: "",
  description: "",
  starts_at_local: "",
  ends_at_local: "",
  status: "draft" as CampaignStatus,
  donation_enabled: false
});

const initialCoverUrl = computed(() => {
  if (!props.initial) return null;
  return (props.initial as any).cover_url || (props.initial as any).covers?.[0]?.url || null;
});

const initialDocs = computed(() => {
  if (!props.initial || !(props.initial as any).documents) return [];
  return (props.initial as any).documents.map((d: any) => ({ id: d.id, url: d.url ?? null }));
});

let pendingCoverId: number | null = null;
let pendingDocs: Array<{ id:number; url:string|null }> = [];

function onCoverSelected(v: { id:number; url:string|null }) {
  pendingCoverId = v.id;
}
function onCoverCleared() {
  pendingCoverId = null;
}
function onCoverUpdated() {}

function onDocsChanged(v: Array<{ id:number; url:string|null }>) {
  pendingDocs = v.slice();
}
function onDocsUpdated() {}

watchEffect(() => {
  const c = props.initial;
  if (!c) return;
  form.title = c.title || "";
  form.goal = (c as any).goal || "";
  form.description = c.description || "";
  form.status = c.status as CampaignStatus;
  form.donation_enabled = !!(c as any).donation_enabled;
  form.starts_at_local = c.starts_at ? toLocalInput(c.starts_at as any) : "";
  form.ends_at_local = c.ends_at ? toLocalInput(c.ends_at as any) : "";
});

const submitText = computed(() => props.mode === "create" ? t("campaign.create.submit") : t("campaign.actions.save"));

function toLocalInput(iso: string) {
  const d = new Date(iso);
  const pad = (n: number) => n.toString().padStart(2, "0");
  const yyyy = d.getFullYear();
  const mm = pad(d.getMonth() + 1);
  const dd = pad(d.getDate());
  const hh = pad(d.getHours());
  const mi = pad(d.getMinutes());
  return `${yyyy}-${mm}-${dd}T${hh}:${mi}`;
}

function submit() {
  const payload: any = {
    title: form.title.trim(),
    goal: form.goal || null,
    description: form.description || null,
    starts_at: form.starts_at_local ? new Date(form.starts_at_local).toISOString() : null,
    ends_at: form.ends_at_local ? new Date(form.ends_at_local).toISOString() : null,
    status: form.status,
    donation_enabled: !!form.donation_enabled
  };
  if (!isEdit.value) {
    if (pendingCoverId && pendingCoverId > 0) payload.cover_id = pendingCoverId;
    if (pendingDocs.length) payload.documents = pendingDocs.map((d, i) => ({ id: d.id, order: i }));
  }
  emit("submit", payload);
}
</script>
