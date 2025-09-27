<script setup lang="ts">
import { computed } from "vue";
import DateTimeField from "../DateTimeField.vue";
import { TIMEZONES } from "@/modules/dashboard/constants/timezones";
import type { CreateEventPayload } from "@/stores/event";

const props = defineProps<{ modelValue: Partial<CreateEventPayload> }>();
const emit  = defineEmits<{ (e:"update:modelValue", v: Partial<CreateEventPayload>): void }>();

function patch(v: Partial<CreateEventPayload>) {
  let touched = false;
  for (const k in v) {
    if (k === "settings") continue;
    if ((props.modelValue as any)[k] !== (v as any)[k]) { touched = true; break; }
  }
  if (v.settings && !touched) {
    const s = v.settings as any;
    const cur = props.modelValue.settings || {};
    const keys = Object.keys(s);
    for (const k of keys) {
      if ((cur as any)[k] !== s[k]) { touched = true; break; }
    }
  }
  if (touched) emit("update:modelValue", v);
}

function patchSettings(s: Partial<NonNullable<CreateEventPayload["settings"]>>) {
  let touched = false;
  const cur = props.modelValue.settings || {};
  for (const k in s) {
    if ((cur as any)[k] !== (s as any)[k]) { touched = true; break; }
  }
  if (touched) emit("update:modelValue", { settings: s } as any);
}

const starts_at = computed({
  get: () => props.modelValue.starts_at ?? "",
  set: (v: string | null) => {
    const nv = v ?? "";
    if ((props.modelValue.starts_at ?? "") === nv) return;
    patch({ starts_at: nv });
  },
});
const ends_at = computed({
  get: () => props.modelValue.ends_at ?? null,
  set: (v: string | null) => {
    const nv = v ?? null;
    if ((props.modelValue.ends_at ?? null) === nv) return;
    patch({ ends_at: nv });
  },
});
const timezone = computed({
  get: () => props.modelValue.timezone ?? "Europe/Amsterdam",
  set: (v: string) => {
    const nv = v || "Europe/Amsterdam";
    if ((props.modelValue.timezone ?? "Europe/Amsterdam") === nv) return;
    patch({ timezone: nv });
  },
});
const location = computed({
  get: () => props.modelValue.location ?? null,
  set: (v: string | null) => {
    const nv = v ?? null;
    if ((props.modelValue.location ?? null) === nv) return;
    patch({ location: nv });
  },
});

const type = computed({
  get: () => props.modelValue.settings?.type ?? "in_person",
  set: (v: any) => {
    const nv = v as any;
    if ((props.modelValue.settings?.type ?? "in_person") === nv) return;
    patchSettings({ type: nv });
  },
});
const join_url = computed({
  get: () => props.modelValue.settings?.join_url ?? null,
  set: (v: string | null) => {
    const nv = v ?? null;
    if ((props.modelValue.settings?.join_url ?? null) === nv) return;
    patchSettings({ join_url: nv as any });
  },
});
const join_platform = computed({
  get: () => props.modelValue.settings?.join_platform ?? "",
  set: (v: string | null) => {
    const nv = v ?? "";
    if ((props.modelValue.settings?.join_platform ?? "") === nv) return;
    patchSettings({ join_platform: nv as any });
  },
});
const visibility = computed({
  get: () => props.modelValue.settings?.visibility ?? "public",
  set: (v: any) => {
    const nv = v as any;
    if ((props.modelValue.settings?.visibility ?? "public") === nv) return;
    patchSettings({ visibility: nv });
  },
});
const access_code = computed({
  get: () => props.modelValue.settings?.access_code ?? null,
  set: (v: string | null) => {
    const nv = v ?? null;
    if ((props.modelValue.settings?.access_code ?? null) === nv) return;
    patchSettings({ access_code: nv });
  },
});

function hasTimePart(v?: string | null) {
  return !!(v && /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/.test(v));
}

const needsLocation = computed(() => type.value === "in_person" || type.value === "hybrid");
const needsJoinUrl  = computed(() => type.value === "online" || type.value === "hybrid");
const needsPlatform = needsJoinUrl;
const isPrivate     = computed(() => visibility.value === "private");

const errors = computed(() => {
  const e: Record<string,string> = {};
  if (!hasTimePart(starts_at.value || "")) e.starts_at = "event.form.errors.startsAtRequired";
  if (starts_at.value && ends_at.value && new Date(ends_at.value) < new Date(starts_at.value)) e.ends_at = "event.form.errors.endsAfterStart";
  if (!timezone.value) e.timezone = "Timezone is required";
  if (needsLocation.value && !location.value) e.location = "Location is required";
  if (needsJoinUrl.value && !join_url.value) e.join_url = "Join URL is required";
  if (isPrivate.value && !(access_code.value || "").toString().trim()) e.access_code = "Access code is required";
  return e;
});

const baseInput = "peer w-full px-4 pt-6 pb-2 border rounded-xl bg-white dark:bg-gray-800/80 dark:border-gray-600 font-mono focus:outline-none focus:ring-2 focus:ring-emerald-500/30";

const typeCards = [
  { value: "in_person", title: "In-person", desc: "Attendees join on site" },
  { value: "online",    title: "Online",    desc: "Attendees join via link" },
  { value: "hybrid",    title: "Hybrid",    desc: "On site + online link" },
];

const tzList = computed(() => {
  const list = [...TIMEZONES];
  const tz = String(timezone.value || "");
  if (tz && !list.includes(tz)) list.unshift(tz);
  return list;
});
</script>

<template>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="space-y-4 p-6">
      <DateTimeField
        v-model="starts_at"
        label-key="event.form.fields.startsAt"
        :error-key="errors.starts_at || null"
        :minute-step="5"
      />
      <DateTimeField
        v-model="ends_at"
        label-key="event.form.fields.endsAt"
        :error-key="errors.ends_at || null"
        :minute-step="5"
      />
      <div>
        <label class="block text-sm font-medium mb-1">Timezone</label>
        <select
          v-model="timezone"
          :class="[baseInput, errors.timezone ? 'border-red-300 focus:ring-red-400/40' : 'border-gray-300 dark:border-gray-600']"
        >
          <option
            v-for="z in tzList"
            :key="z"
            :value="z"
          >
            {{ z }}
          </option>
        </select>
        <p
          v-if="errors.timezone"
          class="text-xs text-red-600 mt-1"
        >
          {{ errors.timezone }}
        </p>
      </div>
    </div>

    <div class="space-y-5 p-6 mt-6">
      <div class="grid grid-cols-3 gap-3">
        <button
          v-for="c in typeCards"
          :key="c.value"
          type="button"
          class="text-left rounded-2xl border p-4 transition-colors"
          :class="[
            type === c.value
              ? 'border-emerald-400 bg-emerald-50 dark:border-emerald-600 dark:bg-emerald-900/20'
              : 'border-gray-300 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-800/60'
          ]"
          @click="type = c.value as any"
        >
          <div class="font-semibold">
            {{ c.title }}
          </div>
          <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            {{ c.desc }}
          </div>
        </button>
      </div>

      <div v-if="needsLocation">
        <label class="block text-sm font-medium mb-1">{{ $t('event.form.fields.location') }}</label>
        <input
          v-model="location"
          type="text"
          :class="[baseInput, errors.location ? 'border-red-300 focus:ring-red-400/40' : 'border-gray-300 dark:border-gray-600']"
          :placeholder="$t('event.form.placeholders.location') as string"
        >
        <p
          v-if="errors.location"
          class="text-xs text-red-600 mt-1"
        >
          {{ errors.location }}
        </p>
      </div>

      <div
        v-if="needsJoinUrl"
        class="grid grid-cols-1 md:grid-cols-2 gap-3"
      >
        <div>
          <label class="block text-sm font-medium mb-1">Join URL</label>
          <input
            v-model="join_url"
            type="url"
            :class="[baseInput, errors.join_url ? 'border-red-300 focus:ring-red-400/40' : 'border-gray-300 dark:border-gray-600']"
            placeholder="https://…"
          >
          <p
            v-if="errors.join_url"
            class="text-xs text-red-600 mt-1"
          >
            {{ errors.join_url }}
          </p>
        </div>
        <div v-if="needsPlatform">
          <label class="block text-sm font-medium mb-1">Platform</label>
          <select
            v-model="join_platform"
            :class="[baseInput, 'border-gray-300 dark:border-gray-600']"
          >
            <option value="">
              Select
            </option>
            <option value="zoom">
              Zoom
            </option>
            <option value="google_meet">
              Google Meet
            </option>
            <option value="youtube">
              YouTube
            </option>
            <option value="other">
              Other
            </option>
          </select>
        </div>
      </div>

      <div v-if="isPrivate">
        <label class="block text-sm font-medium mb-1">Access code</label>
        <input
          v-model="access_code"
          type="text"
          :class="[baseInput, errors.access_code ? 'border-red-300 focus:ring-red-400/40' : 'border-gray-300 dark:border-gray-600']"
          placeholder="Required for private events"
        >
        <p
          v-if="errors.access_code"
          class="text-xs text-red-600 mt-1"
        >
          {{ errors.access_code }}
        </p>
      </div>
    </div>
  </div>
</template>
