<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from "vue";
import { useRouter } from "vue-router";
import StepHeader from "./wizard/StepHeader.vue";
import StepDetails from "./wizard/StepDetails.vue";
import StepTypeSchedule from "./wizard/StepTypeSchedule.vue";
import StepMedia from "./wizard/StepMedia.vue";
import StepReview from "./wizard/StepReview.vue";
import { useEventStore } from "@/stores/event";
import type { CreateEventPayload, EventItem, EventSettings } from "@/stores/event";

type Mode = "create" | "edit";

const props = withDefaults(defineProps<{
  mode?: Mode;
  eventId?: number | null;
  initial?: Partial<EventItem>;
}>(), {
  mode: "create",
  eventId: null,
  initial: () => ({}),
});

const isEdit = computed(() => props.mode === "edit");
const router = useRouter();
const store = useEventStore();

const steps = computed(() => ([
  { key: "details",        label: "Basics" },
  { key: "type_schedule",  label: "Type & Schedule" },
  { key: "media",          label: "Media" },
  { key: "review",         label: isEdit.value ? "Review & Update" : "Review & Create" },
]));

const active = ref(0);

const form = reactive<CreateEventPayload>({
  title: "",
  starts_at: "",
  timezone: "Europe/Amsterdam",
  description: null,
  ends_at: null,
  location: null,
  capacity: null,
  is_published: false,
  publish_at: null,
  organizer_id: null,
  slug: null,
  settings: {
    type: "in_person",
    visibility: "public",
    join_visible_minutes_before: 15,
    access_code: null,
    join_url: null,
  } as Partial<EventSettings>,
});

const coverMedia = ref<{ id:number; url:string|null } | null>(null);
const docsMedia  = ref<Array<{ id:number; url:string|null }>>([]);
const initialCoverUrl = ref<string | null>(null);

const normalizedInitial = computed(() => {
  const s: any = (props as any)?.initial;
  const data = s?.data ?? s;
  if (!data || typeof data !== "object") return null;
  return Object.keys(data).length ? data : null;
});

const hydratedOnce = ref(false);

function hydrateFromInitial(src?: Partial<EventItem>) {
  if (!src) return;
  form.title        = src.title ?? "";
  form.description  = src.description ?? null;
  form.timezone     = (src as any).timezone ?? "Europe/Amsterdam";
  form.starts_at    = (src as any).starts_at_local ?? src.starts_at ?? "";
  form.ends_at      = (src as any).ends_at_local ?? src.ends_at ?? null;
  form.location     = src.location ?? null;
  form.capacity     = src.capacity ?? null;
  form.is_published = !!src.is_published;
  (form as any).publish_at   = (src as any).publish_at ?? null;
  (form as any).organizer_id = (src as any).organizer_id ?? null;
  (form as any).slug         = (src as any).slug ?? null;
  if ((src as any).settings) form.settings = { ...form.settings, ...(src as any).settings };

  initialCoverUrl.value = (src as any).cover_url ?? null;
  if (initialCoverUrl.value && !coverMedia.value) {
    coverMedia.value = { id: 0, url: initialCoverUrl.value };
  }

  if (Array.isArray((src as any).documents)) {
    docsMedia.value = (src as any).documents.map((d:any) => ({ id: d.id, url: d.url ?? null }));
  }
}


function maybeHydrate(src?: Partial<EventItem> | null) {
  if (hydratedOnce.value || !src) return;
  hydrateFromInitial(src);
  hydratedOnce.value = true;
}

if (isEdit.value && normalizedInitial.value) {
  maybeHydrate(normalizedInitial.value as any);
}

onMounted(() => {
  if (isEdit.value && normalizedInitial.value) {
    maybeHydrate(normalizedInitial.value as any);
  }
});

watch(normalizedInitial, (v) => {
  if (isEdit.value && v) maybeHydrate(v as any);
});

function hasTimePart(v?: string | null) {
  return !!(v && /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/.test(v));
}

const detailsValid = computed(() => {
  const t = (form.title || "").trim();
  const vis = (form.settings?.visibility as string) || "public";
  if (!t || !vis) return false;
  if (vis === "private" && (form.settings?.type || "in_person") !== "online") {
    if (!form.settings?.access_code) return false;
  }
  return true;
});

const typeScheduleValid = computed(() => {
  if (!hasTimePart(form.starts_at)) return false;
  if (!form.timezone) return false;
  if (form.ends_at && hasTimePart(form.ends_at) && new Date(form.ends_at) < new Date(form.starts_at)) return false;
  const type = (form.settings?.type as string) || "in_person";
  if ((type === "in_person" || type === "hybrid") && !form.location) return false;
  if ((type === "online" || type === "hybrid") && !form.settings?.join_url) return false;
  return true;
});

function canNext() {
  if (active.value === 0) return detailsValid.value;
  if (active.value === 1) return typeScheduleValid.value;
  if (active.value === 2) return true;
  return true;
}

function next() { if (canNext() && active.value < steps.value.length - 1) active.value += 1; }
function prev() { if (active.value > 0) active.value -= 1; }
function go(i:number) { if (i > active.value && !canNext()) return; active.value = i; }

function patchForm(v: Partial<CreateEventPayload> | any) {
  if (!v) return;
  if (v.settings) {
    const s = v.settings as any;
    if (!form.settings) (form as any).settings = {};
    for (const k in s) {
      if ((form.settings as any)[k] !== s[k]) {
        (form.settings as any)[k] = s[k];
      }
    }
  }
  for (const k in v) {
    if (k === "settings") continue;
    const nv = (v as any)[k];
    if ((form as any)[k] !== nv) {
      (form as any)[k] = nv;
    }
  }
}

const submitting = computed(() => isEdit.value ? store.updating : store.creating);
const submitError = ref<string | null>(null);
const serverErrors = ref<Record<string, string[]>>({});

async function submit() {
  submitError.value = null;
  serverErrors.value = {};
  try {
    const payload: any = {
      ...form,
      cover_id: coverMedia.value?.id ?? null,
      documents: docsMedia.value.map((d, i) => ({ id: d.id, order: i })),
    };
    if (isEdit.value && props.eventId) {
      await store.update(props.eventId, payload);
      router.push("/dashboard/events");
    } else {
      const created = await store.create(payload);
      router.push(`/dashboard/pages/events/${created.id}/edit`);
    }
  } catch (e: any) {
    const data = e?.response?.data;
    submitError.value = data?.message || "event.form.errors.submitFailed";
    if (data?.errors && typeof data.errors === "object") serverErrors.value = data.errors;
    active.value = steps.value.length - 1;
  }
}

function onCoverUpdate(v: { id:number; url:string|null } | null) { coverMedia.value = v ?? null; }
function onDocsUpdate(v: Array<{ id:number; url:string|null }>)   { docsMedia.value  = Array.isArray(v) ? v : []; }
</script>

<template>
  <div class="space-y-6">
    <StepHeader
      :steps="steps"
      :active="active"
      @go="go"
    />

    <div
      v-if="submitError || Object.keys(serverErrors).length"
      class="rounded border border-red-200 bg-red-50 text-red-700 dark:border-red-900/40 dark:bg-red-950/40 dark:text-red-300 px-4 py-3"
    >
      <p class="font-medium mb-1">
        {{ $t(submitError || 'event.form.errors.submitFailed') }}
      </p>
      <ul
        v-if="Object.keys(serverErrors).length"
        class="text-sm list-disc pl-5"
      >
        <li
          v-for="(errs, f) in serverErrors"
          :key="f"
        >
          {{ f }}: {{ errs.join(' | ') }}
        </li>
      </ul>
    </div>

    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5">
      <div v-show="active===0">
        <StepDetails
          :model-value="form"
          :current-type="form.settings?.type || 'in_person'"
          @update:model-value="patchForm"
        />
      </div>

      <div v-show="active===1">
        <StepTypeSchedule
          :model-value="form"
          @update:model-value="patchForm"
        />
      </div>

      <div v-show="active===2">
        <StepMedia
          :event-id="isEdit ? (props.eventId ?? null) : null"
          :current-cover-url="initialCoverUrl"
          :initial-docs="(props.initial?.documents || [])"
          @update:cover="onCoverUpdate"
          @update:docs="onDocsUpdate"
        />
      </div>

      <div v-show="active===3">
        <StepReview
          :mode="isEdit ? 'edit' : 'create'"
          :payload="form"
          :cover="coverMedia"
          :docs="docsMedia"
          :submitting="submitting"
          @submit="submit"
        />
      </div>
    </div>

    <div class="flex items-center justify-between">
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded border px-4 py-2 dark:border-gray-700"
        :disabled="active===0"
        @click="prev"
      >
        <span>{{ $t("common.back") }}</span>
      </button>
      <div class="flex items-center gap-3">
        <button
          v-if="active<steps.length-1"
          type="button"
          class="mt-2 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
          :disabled="!canNext()"
          @click="next"
        >
          <span>{{ $t("common.next") || 'Next' }}</span>
        </button>
      </div>
    </div>
  </div>
</template>
