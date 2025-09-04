<script setup lang="ts">
import { reactive, computed, watch } from "vue";
import type { CreateEventPayload } from "@/stores/event";

const props = defineProps<{ modelValue: Partial<CreateEventPayload>; currentType: string }>();
const emit = defineEmits<{ (e:"update:modelValue", v: Partial<CreateEventPayload>): void }>();

const m = reactive<Partial<CreateEventPayload>>({
  title: props.modelValue.title ?? "",
  description: props.modelValue.description ?? null,
  is_published: props.modelValue.is_published ?? false,
  publish_at: props.modelValue.publish_at ?? null,
  settings: { visibility: props.modelValue.settings?.visibility ?? "public", access_code: props.modelValue.settings?.access_code ?? null, ...(props.modelValue.settings||{}) },
});

watch(
  () => props.modelValue,
  (v) => {
    m.title        = v.title ?? "";
    m.description  = v.description ?? null;
    m.is_published = v.is_published ?? false;
    m.publish_at   = v.publish_at ?? null;
    m.settings = {
      ...(m.settings || {}),
      ...(v.settings || {}),
      visibility: v.settings?.visibility ?? "public",
      access_code: v.settings?.access_code ?? null,
    };
  },
  { deep: true, immediate: true }
);


watch(m, () => emit("update:modelValue", { ...props.modelValue, ...m, settings: { ...(props.modelValue.settings||{}), ...(m.settings||{}) } }), { deep: true });

const baseInput = "peer w-full px-4 pt-6 pb-2 border rounded-xl bg-white dark:bg-gray-800/80 dark:border-gray-600 font-mono focus:outline-none focus:ring-2 focus:ring-emerald-500/30";

const errors = computed(() => {
  const e: Record<string,string> = {};
  if (!m.title || !String(m.title).trim()) e.title = "event.form.errors.titleRequired";
  if (!m.settings?.visibility) e.visibility = "Visibility is required";
  if (m.settings?.visibility === "private" && props.currentType !== "online" && !m.settings?.access_code) e.access_code = "Access code is required";
  return e;
});

const visibilityOptions = [
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
        v-model="m.title"
        :class="[baseInput, errors.title ? 'border-red-300 focus:ring-red-400/40' : 'border-gray-300 dark:border-gray-600']"
        type="text"
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
        v-model="m.description"
        :class="[baseInput, 'min-h-[100px]', 'border-gray-300 dark:border-gray-600']"
      />
    </div>

    <div>
      <label class="block text-sm font-medium mb-2">Visibility</label>
      <div class="inline-flex rounded-xl overflow-hidden border border-gray-300 dark:border-gray-600">
        <button
          v-for="o in visibilityOptions"
          :key="o.value"
          type="button"
          :class="segClass(m.settings?.visibility === o.value)"
          @click="m.settings = { ...(m.settings||{}), visibility: o.value as any }"
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

    <div v-if="m.settings?.visibility==='private' && currentType!=='online'">
      <label class="block text-sm font-medium mb-1">Access code</label>
      <input
        v-model="m.settings.access_code"
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
          v-model="m.is_published"
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
          v-model="m.publish_at"
          type="datetime-local"
          :class="[baseInput, 'border-gray-300 dark:border-gray-600']"
        >
      </div>
    </div>
  </div>
</template>
