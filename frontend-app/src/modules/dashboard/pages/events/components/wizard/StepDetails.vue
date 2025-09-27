<script setup lang="ts">
import { computed } from "vue";
import type { CreateEventPayload } from "@/stores/event";

const props = defineProps<{ modelValue: Partial<CreateEventPayload>; currentType: string }>();
const emit  = defineEmits<{ (e:"update:modelValue", v: Partial<CreateEventPayload>): void }>();

const baseInput = "peer w-full px-4 pt-6 pb-2 border rounded-xl bg-white dark:bg-gray-800/80 dark:border-gray-600 font-mono focus:outline-none focus:ring-2 focus:ring-emerald-500/30";

const title = computed({
  get: () => props.modelValue.title ?? "",
  set: (v: string) => {
    const nv = v ?? "";
    if (props.modelValue.title === nv) return;
    emit("update:modelValue", { title: nv });
  },
});

const description = computed({
  get: () => props.modelValue.description ?? null,
  set: (v: string | null) => {
    const nv = v ?? null;
    if (props.modelValue.description === nv) return;
    emit("update:modelValue", { description: nv });
  },
});

const is_published = computed({
  get: () => !!props.modelValue.is_published,
  set: (v: boolean) => {
    const nv = !!v;
    if (!!props.modelValue.is_published === nv) return;
    emit("update:modelValue", { is_published: nv });
  },
});

const publish_at = computed({
  get: () => (props.modelValue as any).publish_at ?? null,
  set: (v: string | null) => {
    const nv = v ?? null;
    if ((props.modelValue as any).publish_at === nv) return;
    emit("update:modelValue", { publish_at: nv } as any);
  },
});

const visibility = computed({
  get: () => props.modelValue.settings?.visibility ?? "public",
  set: (v: any) => {
    if (props.modelValue.settings?.visibility === v) return;
    emit("update:modelValue", { settings: { visibility: v } });
  },
});

const access_code = computed({
  get: () => props.modelValue.settings?.access_code ?? null,
  set: (v: string | null) => {
    const nv = v ?? null;
    if (props.modelValue.settings?.access_code === nv) return;
    emit("update:modelValue", { settings: { access_code: nv } });
  },
});

const errors = computed(() => {
  const e: Record<string,string> = {};
  if (!String(title.value || "").trim()) e.title = "event.form.errors.titleRequired";
  if (!visibility.value) e.visibility = "Visibility is required";
  if (visibility.value === "private" && props.currentType !== "online" && !String(access_code.value || "").trim()) e.access_code = "Access code is required";
  return e;
});

const options = [
  { value: "public", label: "Public" },
  { value: "unlisted", label: "Unlisted" },
  { value: "private", label: "Private" },
];

function segClass(active: boolean) {
  return [
    "px-4 py-2 text-sm border",
    active
      ? "bg-emerald-50 text-emerald-700 border-emerald-300 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-700"
      : "bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800/80 dark:text-gray-300 dark:border-gray-600",
  ].join(" ");
}
</script>

<template>
  <div class="space-y-6">
    <div>
      <label class="block text-sm font-medium mb-1">{{ $t("event.form.fields.title") }}</label>
      <input
        v-model="title"
        :class="[baseInput, errors.title ? 'border-red-300 focus:ring-red-400/40' : 'border-gray-300 dark:border-gray-600']"
        type="text"
        dir="auto"
      >
      <p
        v-if="errors.title"
        class="text-xs text-red-600 mt-1"
      >
        {{ $t(errors.title) }}
      </p>
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">{{ $t("event.form.fields.description") }}</label>
      <textarea
        v-model="description"
        dir="auto"
        :class="[baseInput, 'min-h-[100px]', 'border-gray-300 dark:border-gray-600']"
      />
    </div>

    <div>
      <label class="block text-sm font-medium mb-2">Visibility</label>
      <div class="inline-flex rounded-xl overflow-hidden border border-gray-300 dark:border-gray-600">
        <button
          v-for="o in options"
          :key="o.value"
          type="button"
          :class="segClass(visibility===o.value)"
          @click="visibility = o.value as any"
        >
          {{ o.label }}
        </button>
      </div>
      <p
        v-if="errors.visibility"
        class="text-xs text-red-600 mt-1"
      >
        {{ errors.visibility }}
      </p>
    </div>

    <div v-if="visibility==='private' && currentType!=='online'">
      <label class="block text-sm font-medium mb-1">Access code</label>
      <input
        v-model="access_code"
        type="text"
        :class="[baseInput, errors.access_code ? 'border-red-300 focus:ring-red-400/40' : 'border-gray-300 dark:border-gray-600']"
        placeholder="Required for private (non-online) events"
      >
      <p
        v-if="errors.access_code"
        class="text-xs text-red-600 mt-1"
      >
        {{ errors.access_code }}
      </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="flex items-center gap-2">
        <input
          id="is_published"
          v-model="is_published"
          class="h-4 w-4"
          type="checkbox"
        >
        <label
          for="is_published"
          class="text-sm"
        >{{ $t("event.form.fields.isPublished") }}</label>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Publish at</label>
        <input
          v-model="publish_at"
          type="datetime-local"
          :class="[baseInput, 'border-gray-300 dark:border-gray-600']"
        >
      </div>
    </div>
  </div>
</template>
